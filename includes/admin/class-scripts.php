<?php
if (!defined('ABSPATH')) exit;

final class BRLGPD_Scripts
{
    public static function init(): void
    {
        add_action('admin_post_brlgpd_save_script', [__CLASS__, 'handle_save']);
        add_action('admin_post_brlgpd_delete_script', [__CLASS__, 'handle_delete']);
    }

    private static function admin_url_page(): string
    {
        return admin_url('admin.php?page=brlgpd-scripts');
    }

    private static function require_manage(): void
    {
        if (!BRLGPD_Utils::current_user_can_manage()) {
            wp_die(esc_html__('Sem permissão.', 'br-lgpd-consent'));
        }
    }

    private static function get_categories_for_select(): array
    {
        $cats = BRLGPD_Utils::get_categories();
        if (!is_array($cats)) $cats = [];

        $out = [];
        foreach ($cats as $key => $c) {
            $key = (string)$key;
            if ($key === '') continue;

            if (empty($c['enabled'])) continue;

            $out[$key] = [
                'name' => (string)($c['name'] ?? $key),
                'always_active' => !empty($c['always_active']),
            ];
        }

        if (empty($out)) {
            $out = [
                'stats' => ['name' => __('Estatística', 'br-lgpd-consent'), 'always_active' => false],
                'marketing' => ['name' => __('Marketing', 'br-lgpd-consent'), 'always_active' => false],
            ];
        }

        return $out;
    }

    private static function normalize_category(string $cat): string
    {
        $cat = BRLGPD_Utils::sanitize_text($cat);
        $cats = self::get_categories_for_select();

        if (!isset($cats[$cat])) {
            if (isset($cats['stats'])) return 'stats';
            return (string)array_key_first($cats);
        }

        return $cat;
    }

    public static function handle_save(): void
    {
        self::require_manage();
        check_admin_referer('brlgpd_save_script');

        $scripts = BRLGPD_Utils::get_scripts();

        $id = isset($_POST['script_id']) ? BRLGPD_Utils::sanitize_text($_POST['script_id']) : '';
        if ($id === '') {
            $id = 'sc_' . wp_generate_password(10, false, false);
        }

        $name = BRLGPD_Utils::sanitize_text($_POST['name'] ?? '');
        $category = self::normalize_category((string)($_POST['category'] ?? 'stats'));
        $position = BRLGPD_Utils::sanitize_text($_POST['position'] ?? 'footer');
        $code_raw = isset($_POST['code']) ? (string)wp_unslash($_POST['code']) : '';

        if (!in_array($position, ['head', 'footer'], true)) {
            $position = 'footer';
        }

        $code = BRLGPD_Utils::kses_allow_script($code_raw);

        $scripts[$id] = [
            'id' => $id,
            'name' => $name,
            'category' => $category,
            'position' => $position,
            'code' => $code,
            'updated_at' => time(),
        ];

        BRLGPD_Utils::update_scripts($scripts);

        wp_safe_redirect(add_query_arg(['updated' => 1], self::admin_url_page()));
        exit;
    }

    public static function handle_delete(): void
    {
        self::require_manage();
        check_admin_referer('brlgpd_delete_script');

        $id = BRLGPD_Utils::sanitize_text($_POST['script_id'] ?? '');
        $scripts = BRLGPD_Utils::get_scripts();

        if ($id !== '' && isset($scripts[$id])) {
            unset($scripts[$id]);
            BRLGPD_Utils::update_scripts($scripts);
        }

        wp_safe_redirect(add_query_arg(['deleted' => 1], self::admin_url_page()));
        exit;
    }

    public static function render_page(): void
    {
        self::require_manage();

        $scripts = BRLGPD_Utils::get_scripts();
        $cats = self::get_categories_for_select();

        $editing = null;
        if (!empty($_GET['edit'])) {
            $edit_id = BRLGPD_Utils::sanitize_text($_GET['edit']);
            if (isset($scripts[$edit_id])) $editing = $scripts[$edit_id];
        }

        echo '<div class="wrap brlgpd-admin-wrap">';
        echo '<h1>' . esc_html__('BR LGPD Consent — Scripts', 'br-lgpd-consent') . '</h1>';

        if (!empty($_GET['updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Script salvo.', 'br-lgpd-consent') . '</p></div>';
        }
        if (!empty($_GET['deleted'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Script removido.', 'br-lgpd-consent') . '</p></div>';
        }

        $id = (string)($editing['id'] ?? '');
        $name = (string)($editing['name'] ?? '');
        $category = self::normalize_category((string)($editing['category'] ?? 'stats'));
        $position = (string)($editing['position'] ?? 'footer');
        $code = (string)($editing['code'] ?? '');

        echo '<div class="brlgpd-card">';
        echo '<h2>' . esc_html($editing ? __('Editar script', 'br-lgpd-consent') : __('Adicionar script', 'br-lgpd-consent')) . '</h2>';
        echo '<p class="brlgpd-muted">' . esc_html__('Cole scripts (Analytics/Pixel/etc.) e vincule a uma categoria. Eles só serão executados quando permitido.', 'br-lgpd-consent') . '</p>';

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('brlgpd_save_script');
        echo '<input type="hidden" name="action" value="brlgpd_save_script" />';
        echo '<input type="hidden" name="script_id" value="' . esc_attr($id) . '" />';

        echo '<div class="brlgpd-grid">';
        echo '<div>';
        echo '<label><strong>' . esc_html__('Nome', 'br-lgpd-consent') . '</strong><br />';
        echo '<input type="text" class="regular-text" name="name" value="' . esc_attr($name) . '" /></label>';
        echo '</div>';

        echo '<div>';
        echo '<label><strong>' . esc_html__('Categoria', 'br-lgpd-consent') . '</strong><br />';
        echo '<select name="category">';
        foreach ($cats as $key => $c) {
            $label = $c['name'];
            if (!empty($c['always_active'])) {
                $label .= ' — ' . __('(sempre ativo)', 'br-lgpd-consent');
            }
            echo '<option value="' . esc_attr($key) . '" ' . selected($key, $category, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></label>';
        echo '</div>';

        echo '<div>';
        echo '<label><strong>' . esc_html__('Posição', 'br-lgpd-consent') . '</strong><br />';
        echo '<select name="position">';
        echo '<option value="head" ' . selected('head', $position, false) . '>' . esc_html__('Head', 'br-lgpd-consent') . '</option>';
        echo '<option value="footer" ' . selected('footer', $position, false) . '>' . esc_html__('Footer', 'br-lgpd-consent') . '</option>';
        echo '</select></label>';
        echo '</div>';
        echo '<div></div>';
        echo '</div>';

        echo '<p><label><strong>' . esc_html__('Código', 'br-lgpd-consent') . '</strong><br />';
        echo '<textarea name="code" rows="8" class="large-text code">' . esc_textarea($code) . '</textarea>';
        echo '</label></p>';

        submit_button($editing ? __('Atualizar', 'br-lgpd-consent') : __('Salvar', 'br-lgpd-consent'));
        echo '</form>';
        echo '</div>';

        echo '<div class="brlgpd-card">';
        echo '<h2>' . esc_html__('Scripts cadastrados', 'br-lgpd-consent') . '</h2>';

        if (empty($scripts)) {
            echo '<p class="brlgpd-muted">' . esc_html__('Nenhum script cadastrado ainda.', 'br-lgpd-consent') . '</p>';
        } else {
            echo '<div class="brlgpd-table-scroll">';
            echo '<table class="widefat striped">';
            echo '<thead><tr>';
            echo '<th>' . esc_html__('Nome', 'br-lgpd-consent') . '</th>';
            echo '<th>' . esc_html__('Categoria', 'br-lgpd-consent') . '</th>';
            echo '<th>' . esc_html__('Posição', 'br-lgpd-consent') . '</th>';
            echo '<th>' . esc_html__('Ações', 'br-lgpd-consent') . '</th>';
            echo '</tr></thead><tbody>';

            foreach ($scripts as $sc) {
                $sc_id = (string)($sc['id'] ?? '');
                $sc_name = (string)($sc['name'] ?? $sc_id);
                $sc_cat = (string)($sc['category'] ?? '');
                $sc_pos = (string)($sc['position'] ?? '');

                $cat_label = $cats[$sc_cat]['name'] ?? $sc_cat;

                echo '<tr>';
                echo '<td>' . esc_html($sc_name) . '</td>';
                echo '<td>' . esc_html($cat_label) . '</td>';
                echo '<td>' . esc_html($sc_pos) . '</td>';

                $edit_url = add_query_arg(['edit' => $sc_id], self::admin_url_page());

                echo '<td><div class="brlgpd-table-actions">';
                echo '<a class="button button-secondary" href="' . esc_url($edit_url) . '">' . esc_html__('Editar', 'br-lgpd-consent') . '</a>';

                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline;">';
                wp_nonce_field('brlgpd_delete_script');
                echo '<input type="hidden" name="action" value="brlgpd_delete_script" />';
                echo '<input type="hidden" name="script_id" value="' . esc_attr($sc_id) . '" />';
                echo '<button class="button button-link-delete" type="submit" onclick="return confirm(\'' . esc_js(__('Remover este script?', 'br-lgpd-consent')) . '\');">' . esc_html__('Remover', 'br-lgpd-consent') . '</button>';
                echo '</form>';

                echo '</div></td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';
    }
}

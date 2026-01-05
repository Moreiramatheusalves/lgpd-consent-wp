<?php
if (!defined('ABSPATH')) exit;

final class BRLGPD_Categories
{
    public static function init(): void
    {
        add_action('admin_post_brlgpd_save_category', [__CLASS__, 'handle_save']);
        add_action('admin_post_brlgpd_delete_category', [__CLASS__, 'handle_delete']);
    }

    private static function require_manage(): void
    {
        if (!BRLGPD_Utils::current_user_can_manage()) {
            wp_die(esc_html__('Sem permissão.', 'br-lgpd-consent'));
        }
    }

    private static function admin_url_page(): string
    {
        return admin_url('admin.php?page=brlgpd-categories');
    }

    private static function get_locked_default_keys(): array
    {
        $defaults = BRLGPD_Constants::defaults_categories();
        return array_keys($defaults);
    }

    public static function handle_save(): void
    {
        self::require_manage();
        check_admin_referer('brlgpd_save_category');

        $cats = BRLGPD_Utils::get_categories();

        $is_edit = !empty($_POST['is_edit']);
        $orig_key = BRLGPD_Utils::normalize_category_key((string)($_POST['orig_key'] ?? ''));

        $key = BRLGPD_Utils::normalize_category_key((string)($_POST['key'] ?? ''));
        $name = BRLGPD_Utils::sanitize_text($_POST['name'] ?? '');
        $desc = BRLGPD_Utils::sanitize_text($_POST['desc'] ?? '');

        $enabled = !empty($_POST['enabled']) ? 1 : 0;
        $always_active = !empty($_POST['always_active']) ? 1 : 0;

        if (!$enabled) {
            $always_active = 0;
        }

        if ($is_edit && $orig_key !== '' && isset($cats[$orig_key])) {
            $key = $orig_key;
        }

        if ($key === '') {
            wp_safe_redirect(add_query_arg(['error' => 'invalid_key'], self::admin_url_page()));
            exit;
        }

        $locked = !empty($cats[$key]['locked']) ? 1 : 0;

        if ($locked) {
            $always_active = !empty($cats[$key]['always_active']) ? 1 : $always_active;
            $enabled = 1;
        }

        $cats[$key] = array_merge($cats[$key] ?? [], [
            'name' => $name !== '' ? $name : $key,
            'desc' => $desc,
            'enabled' => $enabled,
            'always_active' => $always_active,
            'locked' => $locked,
        ]);

        BRLGPD_Utils::update_categories($cats);

        wp_safe_redirect(add_query_arg(['updated' => 1], self::admin_url_page()));
        exit;
    }

    public static function handle_delete(): void
    {
        self::require_manage();
        check_admin_referer('brlgpd_delete_category');

        $key = BRLGPD_Utils::normalize_category_key((string)($_POST['key'] ?? ''));
        if ($key === '') {
            wp_safe_redirect(self::admin_url_page());
            exit;
        }

        $cats = BRLGPD_Utils::get_categories();
        if (empty($cats[$key])) {
            wp_safe_redirect(self::admin_url_page());
            exit;
        }

        if (!empty($cats[$key]['locked'])) {
            wp_safe_redirect(add_query_arg(['error' => 'locked'], self::admin_url_page()));
            exit;
        }

        unset($cats[$key]);
        BRLGPD_Utils::update_categories($cats);

        $scripts = BRLGPD_Utils::get_scripts();
        if (is_array($scripts)) {
            foreach ($scripts as $id => $sc) {
                if (!is_array($sc)) continue;
                if (($sc['category'] ?? '') === $key) {
                    $scripts[$id]['category'] = 'stats';
                }
            }
            BRLGPD_Utils::update_scripts($scripts);
        }

        wp_safe_redirect(add_query_arg(['deleted' => 1], self::admin_url_page()));
        exit;
    }

    public static function render_page(): void
    {
        self::require_manage();

        $cats = BRLGPD_Utils::get_categories();

        $editing_key = '';
        $editing = null;
        if (!empty($_GET['edit'])) {
            $editing_key = BRLGPD_Utils::normalize_category_key((string)$_GET['edit']);
            if ($editing_key !== '' && isset($cats[$editing_key])) {
                $editing = $cats[$editing_key];
            }
        }

        echo '<div class="wrap brlgpd-admin-wrap">';
        echo '<h1>' . esc_html__('BR LGPD Consent — Categorias', 'br-lgpd-consent') . '</h1>';

        if (!empty($_GET['updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Categoria salva.', 'br-lgpd-consent') . '</p></div>';
        }
        if (!empty($_GET['deleted'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Categoria removida.', 'br-lgpd-consent') . '</p></div>';
        }
        if (!empty($_GET['error'])) {
            $err = (string)$_GET['error'];
            $msg = __('Ocorreu um erro.', 'br-lgpd-consent');
            if ($err === 'invalid_key') $msg = __('Slug inválido. Use apenas letras/números, "-" ou "_".', 'br-lgpd-consent');
            if ($err === 'locked') $msg = __('Esta categoria é padrão do plugin e não pode ser removida.', 'br-lgpd-consent');
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($msg) . '</p></div>';
        }

        echo '<div class="brlgpd-card">';
        echo '<h2>' . esc_html($editing ? __('Editar categoria', 'br-lgpd-consent') : __('Adicionar categoria', 'br-lgpd-consent')) . '</h2>';
        echo '<p class="brlgpd-muted">' . esc_html__('As categorias aparecem no modal de preferências. Categorias marcadas como "Sempre ativo" não dependem de consentimento e são úteis para scripts essenciais.', 'br-lgpd-consent') . '</p>';

        $key_val = $editing_key;
        $name_val = $editing['name'] ?? '';
        $desc_val = $editing['desc'] ?? '';
        $enabled_val = !empty($editing['enabled']);
        $always_val = !empty($editing['always_active']);
        $locked_val = !empty($editing['locked']);

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('brlgpd_save_category');
        echo '<input type="hidden" name="action" value="brlgpd_save_category" />';
        echo '<input type="hidden" name="is_edit" value="' . esc_attr($editing ? '1' : '') . '" />';
        echo '<input type="hidden" name="orig_key" value="' . esc_attr($editing_key) . '" />';

        echo '<div class="brlgpd-grid">';

        echo '<div>';
        echo '<label><strong>' . esc_html__('Slug (chave)', 'br-lgpd-consent') . '</strong><br />';
        echo '<input type="text" class="regular-text" name="key" value="' . esc_attr($key_val) . '" ' . ($editing ? 'readonly' : '') . ' placeholder="ex: functional" /></label>';
        if ($editing) {
            echo '<p class="brlgpd-muted" style="margin:6px 0 0 0;">' . esc_html__('O slug não pode ser alterado após criado.', 'br-lgpd-consent') . '</p>';
        }
        echo '</div>';

        echo '<div>';
        echo '<label><strong>' . esc_html__('Nome', 'br-lgpd-consent') . '</strong><br />';
        echo '<input type="text" class="regular-text" name="name" value="' . esc_attr($name_val) . '" placeholder="ex: Funcionais" /></label>';
        echo '</div>';

        echo '<div>';
        echo '<label><strong>' . esc_html__('Descrição', 'br-lgpd-consent') . '</strong><br />';
        echo '<input type="text" class="regular-text" name="desc" value="' . esc_attr($desc_val) . '" placeholder="ex: Lembrar preferências do usuário" /></label>';
        echo '</div>';

        echo '<div>';
        echo '<label><input type="checkbox" name="enabled" value="1" ' . checked(true, $enabled_val || $locked_val, false) . ' ' . ($locked_val ? 'disabled' : '') . ' /> ' . esc_html__('Ativa', 'br-lgpd-consent') . '</label><br />';
        echo '<label><input type="checkbox" name="always_active" value="1" ' . checked(true, $always_val, false) . ' ' . ($locked_val ? 'disabled' : '') . ' /> ' . esc_html__('Sempre ativo', 'br-lgpd-consent') . '</label>';
        echo '</div>';

        echo '</div>';

        submit_button($editing ? __('Atualizar', 'br-lgpd-consent') : __('Adicionar', 'br-lgpd-consent'));
        echo '</form>';
        echo '</div>';

        echo '<div class="brlgpd-card">';
        echo '<h2>' . esc_html__('Categorias cadastradas', 'br-lgpd-consent') . '</h2>';

        echo '<div class="brlgpd-table-scroll">';
        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Slug', 'br-lgpd-consent') . '</th>';
        echo '<th>' . esc_html__('Nome', 'br-lgpd-consent') . '</th>';
        echo '<th>' . esc_html__('Descrição', 'br-lgpd-consent') . '</th>';
        echo '<th>' . esc_html__('Status', 'br-lgpd-consent') . '</th>';
        echo '<th>' . esc_html__('Ações', 'br-lgpd-consent') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($cats as $key => $c) {
            $key = (string)$key;
            $name = (string)($c['name'] ?? $key);
            $desc = (string)($c['desc'] ?? '');
            $enabled = !empty($c['enabled']);
            $always = !empty($c['always_active']);
            $locked = !empty($c['locked']);

            $status = [];
            $status[] = $enabled ? __('Ativa', 'br-lgpd-consent') : __('Inativa', 'br-lgpd-consent');
            if ($always) $status[] = __('Sempre ativo', 'br-lgpd-consent');
            if ($locked) $status[] = __('Padrão', 'br-lgpd-consent');

            echo '<tr>';
            echo '<td><code>' . esc_html($key) . '</code></td>';
            echo '<td>' . esc_html($name) . '</td>';
            echo '<td>' . esc_html($desc) . '</td>';
            echo '<td>' . esc_html(implode(' • ', $status)) . '</td>';

            $edit_url = add_query_arg(['edit' => $key], self::admin_url_page());

            echo '<td><div class="brlgpd-table-actions">';
            echo '<a class="button button-secondary" href="' . esc_url($edit_url) . '">' . esc_html__('Editar', 'br-lgpd-consent') . '</a>';

            if (!$locked) {
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline;">';
                wp_nonce_field('brlgpd_delete_category');
                echo '<input type="hidden" name="action" value="brlgpd_delete_category" />';
                echo '<input type="hidden" name="key" value="' . esc_attr($key) . '" />';
                echo '<button class="button button-link-delete" type="submit" onclick="return confirm(\'' . esc_js(__('Remover esta categoria?', 'br-lgpd-consent')) . '\');">' . esc_html__('Remover', 'br-lgpd-consent') . '</button>';
                echo '</form>';
            }

            echo '</div></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';

        echo '</div>';
        echo '</div>';
    }
}

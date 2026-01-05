<?php
if (!defined('ABSPATH')) exit;

final class BRLGPD_Policy
{
    public static function init(): void
    {
        add_action('admin_post_brlgpd_save_policy', [__CLASS__, 'handle_save']);
    }

    private static function require_manage(): void
    {
        if (!BRLGPD_Utils::current_user_can_manage()) {
            wp_die(esc_html__('Sem permissão.', 'br-lgpd-consent'));
        }
    }

    public static function render_page(): void
    {
        self::require_manage();

        $page_id = BRLGPD_Utils::ensure_policy_page();
        $page_url = $page_id ? get_permalink($page_id) : '';
        $content = BRLGPD_Utils::get_policy_content();

        echo '<div class="wrap brlgpd-admin-wrap">';
        echo '<h1>' . esc_html__('BR LGPD Consent — Política de Cookies', 'br-lgpd-consent') . '</h1>';

        if (!empty($_GET['updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Política atualizada e sincronizada com a página.', 'br-lgpd-consent') . '</p></div>';
        }

        echo '<div class="brlgpd-card">';
        echo '<p class="brlgpd-muted">' . esc_html__('Cole/edite abaixo a sua Política de Cookies. O botão para gerenciar preferências será sempre mantido no final da página.', 'br-lgpd-consent') . '</p>';

        if ($page_url) {
            echo '<p><strong>' . esc_html__('Página pública:', 'br-lgpd-consent') . '</strong> <a href="' . esc_url($page_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($page_url) . '</a></p>';
        }

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('brlgpd_save_policy');
        echo '<input type="hidden" name="action" value="brlgpd_save_policy" />';

        wp_editor(
            $content,
            'brlgpd_policy_content',
            [
                'textarea_name' => 'policy_content',
                'textarea_rows' => 16,
                'media_buttons' => false,
                'teeny' => false,
                'tinymce' => true,
                'quicktags' => true,
            ]
        );

        echo '<p class="brlgpd-muted" style="margin-top:10px;">' .
            esc_html__('Obs.: o shortcode [brlgpd_manage_button] é adicionado automaticamente ao final da página (fixo), mesmo que você não inclua no texto.', 'br-lgpd-consent') .
            '<br>' .
            esc_html__('Dica Elementor: para personalizar essa página, edite a "Política de Cookies" com o Elementor e aplique um container/seção principal com padding (ex.: 24–32px) para melhorar a leitura.', 'br-lgpd-consent') .
            '<br>' .
            esc_html__('No modo responsivo (tablet/celular), reduza o padding (ex.: 14–18px) e ajuste a largura do container para 100% para evitar estouro de layout.', 'br-lgpd-consent') .
            '<br>' .
            esc_html__('Você pode estilizar o botão de consentimento diretamente no Elementor (CSS do widget/página), usando a classe ".brlgpd-manage-btn".', 'br-lgpd-consent') .
            '</p>';

        submit_button(__('Salvar política', 'br-lgpd-consent'));
        echo '</form>';

        echo '</div>';
        echo '</div>';
    }

    public static function handle_save(): void
    {
        self::require_manage();
        check_admin_referer('brlgpd_save_policy');

        $raw = isset($_POST['policy_content']) ? wp_unslash($_POST['policy_content']) : '';
        $raw = is_string($raw) ? $raw : '';

        BRLGPD_Utils::set_policy_content($raw);
        BRLGPD_Utils::sync_policy_page_content();

        wp_safe_redirect(add_query_arg(['page' => 'brlgpd-policy', 'updated' => 1], admin_url('admin.php')));
        exit;
    }
    public static function redirect_to_editor(): void
    {
        self::require_manage();

        $page_id = BRLGPD_Utils::ensure_policy_page();
        if (!$page_id) {
            wp_safe_redirect(add_query_arg(['page' => 'brlgpd-policy'], admin_url('admin.php')));
            exit;
        }

        $use_elementor = defined('ELEMENTOR_VERSION') || did_action('elementor/loaded') || class_exists('\Elementor\Plugin');

        $url = $use_elementor
            ? admin_url('post.php?post=' . (int)$page_id . '&action=elementor')
            : admin_url('post.php?post=' . (int)$page_id . '&action=edit');

        wp_safe_redirect($url);
        exit;
    }

}

<?php
if (!defined('ABSPATH')) exit;

final class BRLGPD_Export
{
    public static function init(): void
    {
        add_action('admin_post_brlgpd_export_csv', [__CLASS__, 'handle_export']);
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

        $settings = BRLGPD_Utils::get_settings();

        echo '<div class="wrap brlgpd-admin-wrap">';
        echo '<h1>' . esc_html__('BR LGPD Consent — Exportar Logs', 'br-lgpd-consent') . '</h1>';

        if (empty($settings['enable_logs'])) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('Logs estão desativados. Ative em Configurações para coletar e exportar.', 'br-lgpd-consent') . '</p></div>';
        }

        echo '<div class="brlgpd-card">';
        echo '<p class="brlgpd-muted">' . esc_html__('Exporta os registros de consentimento em CSV (dados mínimos).', 'br-lgpd-consent') . '</p>';

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('brlgpd_export_csv');
        echo '<input type="hidden" name="action" value="brlgpd_export_csv" />';
        submit_button(__('Exportar CSV', 'br-lgpd-consent'));
        echo '</form>';

        echo '</div>';
        echo '</div>';
    }

    public static function handle_export(): void
    {
        self::require_manage();
        check_admin_referer('brlgpd_export_csv');

        $settings = BRLGPD_Utils::get_settings();
        if (empty($settings['enable_logs'])) {
            wp_die(esc_html__('Logs estão desativados.', 'br-lgpd-consent'));
        }

        $rows = BRLGPD_Consent_Log::fetch_all(50000);

        $filename = 'brlgpd-consent-logs-' . gmdate('Y-m-d') . '.csv';

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $out = fopen('php://output', 'w');

        fputcsv($out, ['id', 'created_at', 'consent_hash', 'policy_version', 'choices']);

        foreach ($rows as $r) {
            $line = [
                BRLGPD_Utils::csv_safe_cell((string)($r['id'] ?? '')),
                BRLGPD_Utils::csv_safe_cell((string)($r['created_at'] ?? '')),
                BRLGPD_Utils::csv_safe_cell((string)($r['consent_hash'] ?? '')),
                BRLGPD_Utils::csv_safe_cell((string)($r['policy_version'] ?? '')),
                BRLGPD_Utils::csv_safe_cell((string)($r['choices'] ?? '')),
            ];
            fputcsv($out, $line);
        }

        fclose($out);
        exit;
    }
}

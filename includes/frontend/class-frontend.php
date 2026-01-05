<?php
if (!defined('ABSPATH')) exit;

final class BRLGPD_Frontend
{
    public static function init(): void
    {
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue']);
        add_action('wp_footer', [__CLASS__, 'render_banner'], 5);

        add_shortcode('brlgpd_manage_button', [__CLASS__, 'shortcode_manage_button']);
    }

    public static function enqueue(): void
    {
        $settings = BRLGPD_Utils::get_settings();
        if (empty($settings['enabled'])) return;

        wp_enqueue_style('brlgpd-frontend', BRLGPD_URL . 'assets/css/frontend.css', [], BRLGPD_VERSION);
        wp_enqueue_script('brlgpd-frontend', BRLGPD_URL . 'assets/js/frontend.js', [], BRLGPD_VERSION, true);

        $localized = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('brlgpd_public'),
            'policy' => (string)$settings['policy_version'],
            'renew_days' => (int)$settings['renew_days'],
            'optional_keys' => BRLGPD_Utils::get_optional_category_keys(),
        ];
        wp_localize_script('brlgpd-frontend', 'BRLGPD', $localized);
    }

    public static function render_banner(): void
    {
        $settings = BRLGPD_Utils::get_settings();
        if (empty($settings['enabled'])) return;

        if (BRLGPD_Consent_Cookie::has_valid_consent() && !BRLGPD_Consent_Cookie::should_renew((string)$settings['policy_version'])) {
            BRLGPD_Banner::render_modal_only($settings);
            return;
        }

        BRLGPD_Banner::render($settings);
    }

    public static function shortcode_manage_button($atts): string
    {
        $settings = BRLGPD_Utils::get_settings();
        if (empty($settings['enabled'])) return '';

        $atts = shortcode_atts([
            'text' => __('Gerenciar cookies', 'br-lgpd-consent'),
            'class' => '',
        ], (array)$atts, 'brlgpd_manage_button');

        ob_start();
        $text = (string)$atts['text'];
        $class = (string)$atts['class'];

        include BRLGPD_DIR . 'templates/manage-consent-button.php';
        return (string)ob_get_clean();
    }
}

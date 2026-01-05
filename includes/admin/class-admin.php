<?php
if (!defined('ABSPATH')) exit;

final class BRLGPD_Admin
{
    public static function init(): void
    {
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue']);
    }

    public static function enqueue(string $hook): void
    {
        if (strpos($hook, 'brlgpd') === false) return;

        wp_enqueue_style('brlgpd-admin', BRLGPD_URL . 'assets/css/admin.css', [], BRLGPD_VERSION);
        wp_enqueue_script('brlgpd-admin', BRLGPD_URL . 'assets/js/admin.js', [], BRLGPD_VERSION, true);
    }

    public static function menu(): void
    {
        add_menu_page(
            __('BR LGPD Consent', 'br-lgpd-consent'),
            __('BR LGPD Consent', 'br-lgpd-consent'),
            'manage_options',
            'brlgpd-settings',
            [BRLGPD_Settings::class, 'render_page'],
            'dashicons-shield-alt',
            56
        );

        add_submenu_page(
            'brlgpd-settings',
            __('Configurações', 'br-lgpd-consent'),
            __('Configurações', 'br-lgpd-consent'),
            'manage_options',
            'brlgpd-settings',
            [BRLGPD_Settings::class, 'render_page']
        );

        add_submenu_page(
            'brlgpd-settings',
            __('Categorias', 'br-lgpd-consent'),
            __('Categorias', 'br-lgpd-consent'),
            'manage_options',
            'brlgpd-categories',
            [BRLGPD_Categories::class, 'render_page']
        );

        add_submenu_page(
            'brlgpd-settings',
            __('Scripts', 'br-lgpd-consent'),
            __('Scripts', 'br-lgpd-consent'),
            'manage_options',
            'brlgpd-scripts',
            [BRLGPD_Scripts::class, 'render_page']
        );

        add_submenu_page(
            'brlgpd-settings',
            __('Política de Cookies', 'br-lgpd-consent'),
            __('Política de Cookies', 'br-lgpd-consent'),
            'manage_options',
            'brlgpd-policy',
            [BRLGPD_Policy::class, 'render_page']
        );

        add_submenu_page(
            'brlgpd-settings',
            __('Exportar Logs', 'br-lgpd-consent'),
            __('Exportar Logs', 'br-lgpd-consent'),
            'manage_options',
            'brlgpd-export',
            [BRLGPD_Export::class, 'render_page']
        );
    }
}

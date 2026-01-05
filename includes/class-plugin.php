<?php
if (!defined('ABSPATH')) exit;

final class BRLGPD_Plugin
{
    private static $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->boot();
        }
        return self::$instance;
    }

    private function boot(): void
    {
        BRLGPD_I18n::load_textdomain();

        if (is_admin()) {
            BRLGPD_Settings::init();
            BRLGPD_Categories::init();
            BRLGPD_Scripts::init();
            BRLGPD_Export::init();
            BRLGPD_Policy::init();
            BRLGPD_Admin::init();
        }

        BRLGPD_Script_Runner::init();
        BRLGPD_Frontend::init();

        BRLGPD_AJAX::init();
        BRLGPD_REST::init();
    }
}

<?php
if (!defined('ABSPATH')) exit;

final class BRLGPD_I18n
{
    public static function load_textdomain(): void
    {
        load_plugin_textdomain(
            'br-lgpd-consent',
            false,
            dirname(plugin_basename(BRLGPD_FILE)) . '/languages'
        );
    }
}

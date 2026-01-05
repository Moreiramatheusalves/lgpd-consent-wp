<?php
if (!defined('ABSPATH')) exit;

final class BRLGPD_Script_Runner
{
    public static function init(): void
    {
        add_action('wp_head', [__CLASS__, 'output_head'], 99);
        add_action('wp_footer', [__CLASS__, 'output_footer'], 99);
    }

    public static function output_head(): void
    {
        self::output_by_position('head');
    }

    public static function output_footer(): void
    {
        self::output_by_position('footer');
    }

    private static function output_by_position(string $position): void
    {
        $settings = BRLGPD_Utils::get_settings();
        if (empty($settings['enabled'])) return;

        $scripts = BRLGPD_Utils::get_scripts();
        if (empty($scripts)) return;

        $categories = BRLGPD_Utils::get_categories();
        if (!is_array($categories)) $categories = [];

        $cat_map = [];
        foreach ($categories as $k => $c) {
            $nk = BRLGPD_Utils::normalize_category_key((string)$k);
            if ($nk === '') continue;
            $cat_map[$nk] = $c;
        }


        $policy_changed = BRLGPD_Consent_Cookie::should_renew((string)$settings['policy_version']);

        $out = [];

        foreach ($scripts as $sc) {
            $cat = BRLGPD_Utils::normalize_category_key((string)($sc['category'] ?? ''));

            $pos = (string)($sc['position'] ?? '');
            $code = (string)($sc['code'] ?? '');

            if ($pos !== $position) continue;
            if ($code === '') continue;

            if ($cat === '' || empty($cat_map[$cat]) || empty($cat_map[$cat]['enabled'])) {
                continue;
            }

            $always_active = !empty($cat_map[$cat]['always_active']);

            if ($policy_changed && !$always_active) {
                continue;
            }

            if (!$always_active && !BRLGPD_Consent_Cookie::is_allowed($cat)) {
                continue;
            }

            $out[] = $code;
        }

        if (empty($out)) return;

        echo "\n<!-- BRLGPD: scripts ({$position}) -->\n";
        echo implode("\n", $out);
        echo "\n<!-- /BRLGPD -->\n";
    }
}

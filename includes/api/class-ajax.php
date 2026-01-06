<?php
if (!defined('ABSPATH')) exit;

final class BRLGPD_AJAX
{
    public static function init(): void
    {
        add_action('wp_ajax_brlgpd_save_consent', [__CLASS__, 'save_consent']);
        add_action('wp_ajax_nopriv_brlgpd_save_consent', [__CLASS__, 'save_consent']);

        add_action('wp_ajax_brlgpd_get_consent', [__CLASS__, 'get_consent']);
        add_action('wp_ajax_nopriv_brlgpd_get_consent', [__CLASS__, 'get_consent']);

        add_action('wp_ajax_brlgpd_get_nonce', [__CLASS__, 'get_nonce']);
        add_action('wp_ajax_nopriv_brlgpd_get_nonce', [__CLASS__, 'get_nonce']);
    }

    public static function get_consent(): void
    {
        $nonce = isset($_POST['nonce']) ? (string)$_POST['nonce'] : '';
        if (!wp_verify_nonce($nonce, 'brlgpd_public')) {
            wp_send_json_error(['message' => 'invalid_nonce'], 403);
        }

        $settings = BRLGPD_Utils::get_settings();
        if (empty($settings['enabled'])) {
            wp_send_json_error(['message' => 'disabled'], 400);
        }

        $policy = (string)($settings['policy_version'] ?? '');
        $payload = BRLGPD_Consent_Cookie::get();
        $choices = (isset($payload['choices']) && is_array($payload['choices'])) ? $payload['choices'] : [];

        $choices = BRLGPD_Utils::filter_choices_by_categories($choices);

        $has = BRLGPD_Consent_Cookie::has_valid_consent();
        $renew = BRLGPD_Consent_Cookie::should_renew($policy);

        if ($renew) {
            $has = false;
            $choices = BRLGPD_Utils::filter_choices_by_categories([]);
        }

        wp_send_json_success([
            'has_consent' => $has,
            'should_renew' => $renew,
            'policy' => $policy,
            'choices' => $choices,
            'optional_keys' => BRLGPD_Utils::get_optional_category_keys(),
        ]);
    }

    public static function save_consent(): void
    {
        $nonce = isset($_POST['nonce']) ? (string)$_POST['nonce'] : '';
        if (!wp_verify_nonce($nonce, 'brlgpd_public')) {
            wp_send_json_error(['message' => 'invalid_nonce'], 403);
        }

        $settings = BRLGPD_Utils::get_settings();
        if (empty($settings['enabled'])) {
            wp_send_json_error(['message' => 'disabled'], 400);
        }

        $choices_json = isset($_POST['choices']) ? (string)wp_unslash($_POST['choices']) : '{}';
        $incoming_raw = BRLGPD_Utils::json_decode_array($choices_json);

        $incoming_norm = [];
        foreach ($incoming_raw as $k => $v) {
            $nk = BRLGPD_Utils::normalize_category_key((string)$k);
            if ($nk === '') continue;
            $incoming_norm[$nk] = (bool)$v;
        }

        $current = BRLGPD_Consent_Cookie::get();
        $current_choices = (isset($current['choices']) && is_array($current['choices'])) ? $current['choices'] : [];
        $current_filtered = BRLGPD_Utils::filter_choices_by_categories($current_choices);

        $optional = BRLGPD_Utils::get_optional_category_keys();
        $final = $current_filtered;

        foreach ($optional as $key) {
            $key = BRLGPD_Utils::normalize_category_key((string)$key);
            if ($key === '') continue;

            if (array_key_exists($key, $incoming_norm)) {
                $final[$key] = (bool)$incoming_norm[$key];
            } elseif (!array_key_exists($key, $final)) {
                $final[$key] = false;
            }
        }

        $choices = $final;

        $policy = (string)$settings['policy_version'];
        $renew_days = (int)$settings['renew_days'];
        $renew_days = max(1, min(3650, $renew_days));

        $ok = BRLGPD_Consent_Cookie::set($choices, $policy, $renew_days);
        if (!$ok) {
            wp_send_json_error(['message' => 'cookie_failed'], 500);
        }

        BRLGPD_Consent_Log::maybe_log(['choices' => $choices], $policy);

        wp_send_json_success([
            'message' => 'saved',
            'choices' => $choices,
            'policy' => $policy,
        ]);
    }

    public static function get_nonce(): void
    {
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? (string)$_SERVER['HTTP_ORIGIN'] : '';
        if ($origin) {
            $originHost = parse_url($origin, PHP_URL_HOST);
            $siteHost   = parse_url(home_url(), PHP_URL_HOST);
            if ($originHost && $siteHost && strcasecmp($originHost, $siteHost) !== 0) {
                wp_send_json_error(['message' => 'bad_origin'], 403);
            }
        }

        wp_send_json_success([
            'nonce' => wp_create_nonce('brlgpd_public'),
        ]);
    }
}

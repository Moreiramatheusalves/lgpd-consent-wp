<?php
if (!defined('ABSPATH')) exit;

final class BRLGPD_Consent_Cookie
{
    private static function b64url_encode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private static function b64_any_decode(string $raw): string|false
    {
        $raw = (string)$raw;
        if ($raw === '') return false;

        $try = str_replace(' ', '+', $raw);
        $json = base64_decode($try, true);
        if ($json !== false) return $json;

        $b64 = strtr($raw, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad) $b64 .= str_repeat('=', 4 - $pad);

        $json = base64_decode($b64, true);
        return ($json === false) ? false : $json;
    }

    public static function get(): array
    {
        if (empty($_COOKIE[BRLGPD_Constants::COOKIE_NAME])) return [];

        $raw = (string)$_COOKIE[BRLGPD_Constants::COOKIE_NAME];
        $json = self::b64_any_decode($raw);
        if ($json === false) return [];

        $data = BRLGPD_Utils::json_decode_array($json);
        if (!isset($data['choices']) || !is_array($data['choices'])) return [];

        $now = time();
        $exp = isset($data['exp']) ? (int)$data['exp'] : 0;
        if ($exp > 0 && $exp < $now) return [];

        return $data;
    }

    public static function is_allowed(string $category): bool
    {
        $category = BRLGPD_Utils::normalize_category_key((string)$category);
        if ($category === '') return false;

        $cats = BRLGPD_Utils::get_categories();

        if (!empty($cats[$category]) && !empty($cats[$category]['always_active'])) {
            return true;
        }

        foreach ($cats as $k => $cat) {
            $nk = BRLGPD_Utils::normalize_category_key((string)$k);
            if ($nk === $category && !empty($cat['always_active'])) {
                return true;
            }
        }

        $data = self::get();
        if (empty($data['choices']) || !is_array($data['choices'])) return false;

        if (isset($data['choices'][$category])) {
            return (bool)$data['choices'][$category];
        }

        foreach ($data['choices'] as $k => $v) {
            $nk = BRLGPD_Utils::normalize_category_key((string)$k);
            if ($nk === $category) return (bool)$v;
        }

        return false;
    }

    public static function has_valid_consent(): bool
    {
        $data = self::get();
        return !empty($data) && isset($data['choices']) && is_array($data['choices']) && isset($data['policy']);
    }

    public static function should_renew(string $current_policy_version): bool
    {
        $data = self::get();
        if (empty($data)) return true;

        $policy = (string)($data['policy'] ?? '');
        return $policy !== (string)$current_policy_version;
    }

    public static function set(array $choices, string $policy_version, int $renew_days): bool
    {
        $renew_days = max(1, (int)$renew_days);
        $now = time();
        $exp = $now + (DAY_IN_SECONDS * $renew_days);

        $choices = BRLGPD_Utils::filter_choices_by_categories($choices);

        $payload = [
            'v' => 2,
            'ts' => $now,
            'exp' => $exp,
            'policy' => $policy_version,
            'choices' => $choices,
        ];

        $json = wp_json_encode($payload);
        if (!$json) return false;

        $cookie = self::b64url_encode($json);

        $secure = is_ssl();

        $path = '/';
        if (defined('SITECOOKIEPATH') && is_string(SITECOOKIEPATH) && SITECOOKIEPATH !== '') {
            $path = SITECOOKIEPATH;
        } elseif (defined('COOKIEPATH') && is_string(COOKIEPATH) && COOKIEPATH !== '') {
            $path = COOKIEPATH;
        }

        $args = [
            'expires'  => $exp,
            'path'     => $path,
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ];

        if (defined('COOKIE_DOMAIN') && is_string(COOKIE_DOMAIN) && COOKIE_DOMAIN !== '') {
            $args['domain'] = COOKIE_DOMAIN;
        }

        $ok = setcookie(BRLGPD_Constants::COOKIE_NAME, $cookie, $args);

        if ($ok) {
            $_COOKIE[BRLGPD_Constants::COOKIE_NAME] = $cookie;
        }

        return $ok;
    }

    public static function consent_hash_from_cookie_value(): string
    {
        $raw = (string)($_COOKIE[BRLGPD_Constants::COOKIE_NAME] ?? '');
        if ($raw === '') return BRLGPD_Utils::safe_hash('empty');
        return BRLGPD_Utils::safe_hash($raw);
    }
}

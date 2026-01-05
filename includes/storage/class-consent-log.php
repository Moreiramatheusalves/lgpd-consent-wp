<?php
if (!defined('ABSPATH')) exit;

final class BRLGPD_Consent_Log
{
    public static function maybe_log(array $cookie_payload, string $policy_version): void
    {
        $settings = BRLGPD_Utils::get_settings();
        if (empty($settings['enable_logs'])) return;

        global $wpdb;
        $table = BRLGPD_DB::table_name();

        $consent_hash = BRLGPD_Consent_Cookie::consent_hash_from_cookie_value();

        $choices = isset($cookie_payload['choices']) && is_array($cookie_payload['choices'])
            ? $cookie_payload['choices']
            : [];

        $choices_json = wp_json_encode($choices);
        if (!$choices_json) $choices_json = '{}';

        $last = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT consent_hash, policy_version, choices FROM {$table} WHERE consent_hash = %s ORDER BY id DESC LIMIT 1",
                $consent_hash
            ),
            ARRAY_A
        );

        if (is_array($last)) {
            $last_policy = (string)($last['policy_version'] ?? '');
            $last_choices = (string)($last['choices'] ?? '');
            if ($last_policy === (string)$policy_version && $last_choices === (string)$choices_json) {
                return;
            }
        }

        $data = [
            'created_at' => gmdate('Y-m-d H:i:s'),
            'consent_hash' => $consent_hash,
            'policy_version' => (string)$policy_version,
            'choices' => $choices_json,
        ];

        $format = ['%s', '%s', '%s', '%s'];

        $wpdb->insert($table, $data, $format);
    }

    public static function fetch_all(int $limit = 5000): array
    {
        global $wpdb;
        $table = BRLGPD_DB::table_name();
        $limit = max(1, min(50000, $limit));

        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limit),
            ARRAY_A
        );

        return is_array($rows) ? $rows : [];
    }
}

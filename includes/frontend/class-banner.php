<?php
if (!defined('ABSPATH')) exit;

final class BRLGPD_Banner
{
    public static function render(array $settings): void
    {
        $data = self::template_data($settings);
        include BRLGPD_DIR . 'templates/banner.php';
    }

    public static function render_modal_only(array $settings): void
    {
        $data = self::template_data($settings);
        $data['hide_banner'] = true;
        include BRLGPD_DIR . 'templates/banner.php';
    }

    private static function template_data(array $settings): array
    {
        $payload = BRLGPD_Consent_Cookie::get();
        $choices = isset($payload['choices']) && is_array($payload['choices']) ? $payload['choices'] : [];

        $categories = BRLGPD_Utils::get_categories();
        $cats_for_ui = [];

        foreach ($categories as $key => $cat) {
            if (!is_array($cat)) continue;
            if (empty($cat['enabled'])) continue;

            $raw_key = (string)$key;
            $norm_key = BRLGPD_Utils::normalize_category_key($raw_key);
            if ($norm_key === '') continue;

            $cats_for_ui[$norm_key] = [
                'key' => $norm_key,
                'name' => (string)($cat['name'] ?? $raw_key),
                'desc' => (string)($cat['desc'] ?? ''),
                'always_active' => !empty($cat['always_active']),
            ];
        }

        $normalized_choices = BRLGPD_Utils::filter_choices_by_categories($choices);

        return [
            'title' => (string)$settings['title'],
            'message' => (string)$settings['message'],
            'btn_accept' => (string)$settings['btn_accept'],
            'btn_reject' => (string)$settings['btn_reject'],
            'btn_prefs'  => (string)$settings['btn_prefs'],
            'modal_title' => (string)$settings['modal_title'],
            'modal_desc'  => (string)$settings['modal_desc'],

            'categories' => $cats_for_ui,
            'choices' => $normalized_choices,

            'policy_url' => BRLGPD_Utils::get_policy_page_url(),
            'policy_link_text' => (string)($settings['policy_link_text'] ?? __('Política de Cookies', 'br-lgpd-consent')),
            'show_policy_link' => !empty($settings['show_policy_link']),

            'hide_banner' => false,
        ];
    }
}

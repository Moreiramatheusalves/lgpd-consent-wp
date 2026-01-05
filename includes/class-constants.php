<?php
if (!defined('ABSPATH')) exit;

final class BRLGPD_Constants
{
    public const OPT_SETTINGS   = 'brlgpd_settings';
    public const OPT_SCRIPTS    = 'brlgpd_scripts';
    public const OPT_CATEGORIES = 'brlgpd_categories';
    public const OPT_POLICY_CONTENT = 'brlgpd_policy_content';


    public const COOKIE_NAME  = 'brlgpd_consent';

    public static function defaults_settings(): array
    {
        return [
            'enabled' => 1,
            'title'   => __('Privacidade e Cookies', 'br-lgpd-consent'),
            'message' => __('Utilizamos cookies e tecnologias semelhantes para garantir o funcionamento do site, melhorar sua experiência e analisar o uso da plataforma. Você pode aceitar todos, rejeitar os não essenciais ou ajustar suas preferências a qualquer momento.', 'br-lgpd-consent'),
            'btn_accept' => __('Aceitar', 'br-lgpd-consent'),
            'btn_reject' => __('Rejeitar', 'br-lgpd-consent'),
            'btn_prefs'  => __('Preferências', 'br-lgpd-consent'),
            'modal_title' => __('Gerenciar preferências', 'br-lgpd-consent'),
            'modal_desc'  => __('Escolha quais categorias você permite. Cookies necessários sempre estarão ativos.', 'br-lgpd-consent'),

            'renew_days' => 180,
            'policy_version' => '1.0.0',

            'show_manage_fab' => 0,
            'manage_text' => __('Gerenciar cookies', 'br-lgpd-consent'),

            'show_policy_link' => 1,
            'policy_link_text' => __('Política de Cookies', 'br-lgpd-consent'),
            'policy_page_id' => 0,

            'enable_logs' => 0,
            'delete_data_on_uninstall' => 0,
        ];
    }

    public static function defaults_categories(): array
    {
        return [
            'necessary' => [
                'name' => __('Necessários', 'br-lgpd-consent'),
                'desc' => __('Essenciais para o funcionamento do site e recursos básicos.', 'br-lgpd-consent'),
                'enabled' => 1,
                'always_active' => 1,
                'locked' => 1,
            ],
            'stats' => [
                'name' => __('Estatística', 'br-lgpd-consent'),
                'desc' => __('Ajuda a entender como o site é usado (ex.: analytics).', 'br-lgpd-consent'),
                'enabled' => 1,
                'always_active' => 0,
                'locked' => 1,
            ],
            'marketing' => [
                'name' => __('Marketing', 'br-lgpd-consent'),
                'desc' => __('Usado para anúncios, remarketing e personalização.', 'br-lgpd-consent'),
                'enabled' => 1,
                'always_active' => 0,
                'locked' => 1,
            ],
        ];
    }

    public static function defaults_scripts(): array
    {
        return [];
    }

    public static function ensure_defaults(): void
    {
        $s = get_option(self::OPT_SETTINGS, null);
        if (!is_array($s)) {
            add_option(self::OPT_SETTINGS, self::defaults_settings(), '', false);
        } else {
            $merged = array_merge(self::defaults_settings(), $s);
            update_option(self::OPT_SETTINGS, $merged, false);
        }

        $sc = get_option(self::OPT_SCRIPTS, null);
        if (!is_array($sc)) {
            add_option(self::OPT_SCRIPTS, self::defaults_scripts(), '', false);
        }

        $pc = get_option(self::OPT_POLICY_CONTENT, null);
        if (!is_string($pc)) {
            add_option(self::OPT_POLICY_CONTENT, '', '', false);
        }

        $cats = get_option(self::OPT_CATEGORIES, null);
        if (!is_array($cats)) {
            add_option(self::OPT_CATEGORIES, self::defaults_categories(), '', false);
        } else {
            $defaults = self::defaults_categories();
            $merged = $defaults;

            foreach ($cats as $key => $cat) {
                if (!is_array($cat)) continue;
                $key = (string)$key;
                if ($key === '') continue;

                $merged[$key] = array_merge($merged[$key] ?? [], $cat);
            }

            update_option(self::OPT_CATEGORIES, $merged, false);
        }
    }
}

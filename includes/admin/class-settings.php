<?php
if (!defined('ABSPATH')) exit;

final class BRLGPD_Settings
{
    public static function init(): void
    {
        add_action('admin_init', [__CLASS__, 'register']);
    }

    public static function register(): void
    {
        register_setting(
            'brlgpd_settings_group',
            BRLGPD_Constants::OPT_SETTINGS,
            [
                'type' => 'array',
                'sanitize_callback' => [__CLASS__, 'sanitize_settings'],
                'default' => BRLGPD_Constants::defaults_settings(),
            ]
        );

        add_settings_section(
            'brlgpd_main',
            __('Configurações principais', 'br-lgpd-consent'),
            function () {
                echo '<p class="brlgpd-muted">' . esc_html__('Configure o banner, textos e políticas de renovação.', 'br-lgpd-consent') . '</p>';
            },
            'brlgpd_settings'
        );

        self::add_field('enabled', __('Ativar banner', 'br-lgpd-consent'), 'checkbox');
        self::add_field('title', __('Título do banner', 'br-lgpd-consent'), 'text');
        self::add_field('message', __('Mensagem do banner', 'br-lgpd-consent'), 'textarea');
        self::add_field('btn_accept', __('Texto: Aceitar', 'br-lgpd-consent'), 'text');
        self::add_field('btn_reject', __('Texto: Rejeitar', 'br-lgpd-consent'), 'text');
        self::add_field('btn_prefs', __('Texto: Preferências', 'br-lgpd-consent'), 'text');

        self::add_field('modal_title', __('Título do modal', 'br-lgpd-consent'), 'text');
        self::add_field('modal_desc', __('Descrição do modal', 'br-lgpd-consent'), 'textarea');

        self::add_field('renew_days', __('Renovar consentimento (dias)', 'br-lgpd-consent'), 'number');
        self::add_field('policy_version', __('Versão da política', 'br-lgpd-consent'), 'text');

        self::add_field('show_manage_fab', __('Mostrar botão flutuante "Gerenciar cookies"', 'br-lgpd-consent'), 'checkbox');
        self::add_field('manage_text', __('Texto do botão "Gerenciar cookies"', 'br-lgpd-consent'), 'text');

        add_settings_section(
            'brlgpd_privacy',
            __('Privacidade e dados', 'br-lgpd-consent'),
            function () {
                echo '<p class="brlgpd-muted">' . esc_html__('Logs são opcionais e guardam apenas informações mínimas (sem IP por padrão).', 'br-lgpd-consent') . '</p>';
            },
            'brlgpd_settings'
        );

        add_settings_field(
            'enable_logs',
            __('Ativar logs (opcional)', 'br-lgpd-consent'),
            [__CLASS__, 'field_checkbox'],
            'brlgpd_settings',
            'brlgpd_privacy',
            ['key' => 'enable_logs']
        );

        add_settings_field(
            'delete_data_on_uninstall',
            __('Apagar dados ao desinstalar', 'br-lgpd-consent'),
            [__CLASS__, 'field_checkbox'],
            'brlgpd_settings',
            'brlgpd_privacy',
            ['key' => 'delete_data_on_uninstall']
        );
    }

    private static function add_field(string $key, string $label, string $type): void
    {
        add_settings_field(
            $key,
            $label,
            [__CLASS__, 'render_field'],
            'brlgpd_settings',
            'brlgpd_main',
            ['key' => $key, 'type' => $type]
        );
    }

    public static function sanitize_settings($input): array
    {
        $defaults = BRLGPD_Constants::defaults_settings();
        $input = is_array($input) ? $input : [];

        $out = $defaults;

        $out['enabled'] = BRLGPD_Utils::sanitize_bool($input['enabled'] ?? 0);

        $out['title'] = BRLGPD_Utils::sanitize_text($input['title'] ?? $defaults['title']);
        $out['message'] = BRLGPD_Utils::sanitize_html_basic($input['message'] ?? $defaults['message']);

        $out['btn_accept'] = BRLGPD_Utils::sanitize_text($input['btn_accept'] ?? $defaults['btn_accept']);
        $out['btn_reject'] = BRLGPD_Utils::sanitize_text($input['btn_reject'] ?? $defaults['btn_reject']);
        $out['btn_prefs']  = BRLGPD_Utils::sanitize_text($input['btn_prefs'] ?? $defaults['btn_prefs']);

        $out['modal_title'] = BRLGPD_Utils::sanitize_text($input['modal_title'] ?? $defaults['modal_title']);
        $out['modal_desc']  = BRLGPD_Utils::sanitize_html_basic($input['modal_desc'] ?? $defaults['modal_desc']);

        $out['renew_days'] = BRLGPD_Utils::sanitize_int($input['renew_days'] ?? $defaults['renew_days'], (int)$defaults['renew_days']);
        $out['renew_days'] = max(1, min(3650, $out['renew_days']));

        $out['policy_version'] = BRLGPD_Utils::sanitize_text($input['policy_version'] ?? $defaults['policy_version']);
        if ($out['policy_version'] === '') $out['policy_version'] = $defaults['policy_version'];

        $out['show_manage_fab'] = BRLGPD_Utils::sanitize_bool($input['show_manage_fab'] ?? 0);
        $out['manage_text'] = BRLGPD_Utils::sanitize_text($input['manage_text'] ?? $defaults['manage_text']);

        $out['enable_logs'] = BRLGPD_Utils::sanitize_bool($input['enable_logs'] ?? 0);
        $out['delete_data_on_uninstall'] = BRLGPD_Utils::sanitize_bool($input['delete_data_on_uninstall'] ?? 0);

        if (!empty($out['enable_logs'])) {
            BRLGPD_DB::maybe_create_tables();
        }

        return $out;
    }

    public static function render_page(): void
    {
        if (!BRLGPD_Utils::current_user_can_manage()) {
            wp_die(esc_html__('Sem permissão.', 'br-lgpd-consent'));
        }

        echo '<div class="wrap brlgpd-admin-wrap">';
        echo '<h1>' . esc_html__('BR LGPD Consent — Configurações', 'br-lgpd-consent') . '</h1>';

        echo '<form method="post" action="options.php" class="brlgpd-card">';
        settings_fields('brlgpd_settings_group');
        do_settings_sections('brlgpd_settings');
        submit_button();
        echo '</form>';

        echo '<div class="brlgpd-card">';
        echo '<p class="brlgpd-muted">' . esc_html__('Dica: depois de configurar, teste em uma janela anônima para ver o banner e o bloqueio/liberação de scripts.', 'br-lgpd-consent') . '</p>';
        echo '</div>';

        echo '</div>';
    }

    public static function render_field(array $args): void
    {
        $key = (string)($args['key'] ?? '');
        $type = (string)($args['type'] ?? 'text');

        $settings = BRLGPD_Utils::get_settings();
        $name = BRLGPD_Constants::OPT_SETTINGS . '[' . $key . ']';
        $value = $settings[$key] ?? '';

        if ($type === 'checkbox') {
            $val = !empty($value) ? 1 : 0;
            printf(
                '<label><input type="checkbox" name="%s" value="1" %s /> %s</label>',
                esc_attr($name),
                checked(1, $val, false),
                esc_html__('Ativo', 'br-lgpd-consent')
            );
            return;
        }

        if ($type === 'number') {
            printf(
                '<input type="number" name="%s" value="%s" class="regular-text" min="1" max="3650" />',
                esc_attr($name),
                esc_attr((string)$value)
            );
            return;
        }

        if ($type === 'textarea') {
            printf(
                '<textarea name="%s" rows="4" class="large-text">%s</textarea>',
                esc_attr($name),
                esc_textarea((string)$value)
            );
            return;
        }

        printf(
            '<input type="text" name="%s" value="%s" class="regular-text" />',
            esc_attr($name),
            esc_attr((string)$value)
        );
    }


    public static function field_checkbox(array $args): void
    {
        $key = (string)($args['key'] ?? '');
        $settings = BRLGPD_Utils::get_settings();
        $val = !empty($settings[$key]) ? 1 : 0;

        printf(
            '<label><input type="checkbox" name="%s[%s]" value="1" %s /> %s</label>',
            esc_attr(BRLGPD_Constants::OPT_SETTINGS),
            esc_attr($key),
            checked(1, $val, false),
            esc_html__('Ativo', 'br-lgpd-consent')
        );
    }
}

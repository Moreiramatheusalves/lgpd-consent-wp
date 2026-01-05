<?php
if (!defined('ABSPATH')) exit;

final class BRLGPD_Utils
{
    public static function current_user_can_manage(): bool
    {
        return current_user_can('manage_options');
    }

    public static function get_settings(): array
    {
        $s = get_option(BRLGPD_Constants::OPT_SETTINGS, []);
        if (!is_array($s)) $s = [];
        return array_merge(BRLGPD_Constants::defaults_settings(), $s);
    }

    public static function get_scripts(): array
    {
        $sc = get_option(BRLGPD_Constants::OPT_SCRIPTS, []);
        return is_array($sc) ? $sc : [];
    }

    public static function update_scripts(array $scripts): void
    {
        update_option(BRLGPD_Constants::OPT_SCRIPTS, $scripts, false);
    }

    public static function get_categories(): array
    {
        $cats = get_option(BRLGPD_Constants::OPT_CATEGORIES, []);
        if (!is_array($cats)) $cats = [];

        $defaults = BRLGPD_Constants::defaults_categories();
        $merged = $defaults;

        foreach ($cats as $key => $cat) {
            if (!is_array($cat)) continue;
            $key = (string)$key;
            if ($key === '') continue;

            $merged[$key] = array_merge($merged[$key] ?? [], $cat);
        }

        foreach ($merged as $key => $cat) {
            if (!is_array($cat)) continue;
            $merged[$key]['name'] = isset($cat['name']) ? (string)$cat['name'] : $key;
            $merged[$key]['desc'] = isset($cat['desc']) ? (string)$cat['desc'] : '';
            $merged[$key]['enabled'] = !empty($cat['enabled']) ? 1 : 0;
            $merged[$key]['always_active'] = !empty($cat['always_active']) ? 1 : 0;
            $merged[$key]['locked'] = !empty($cat['locked']) ? 1 : 0;
        }

        return $merged;
    }

    public static function update_categories(array $categories): void
    {
        update_option(BRLGPD_Constants::OPT_CATEGORIES, $categories, false);
    }

    public static function normalize_category_key(string $key): string
    {
        $key = strtolower(trim($key));
        $key = preg_replace('/[^a-z0-9_\-]/', '', $key);
        $key = substr($key, 0, 32);
        return (string)$key;
    }

    public static function get_optional_category_keys(): array
    {
        $cats = self::get_categories();
        $out = [];

        foreach ($cats as $key => $c) {
            if (empty($c['enabled'])) continue;
            if (!empty($c['always_active'])) continue;

            $nk = self::normalize_category_key((string)$key);
            if ($nk === '') continue;

            $out[] = $nk;
        }

        $out = array_values(array_unique($out));

        return $out;
    }


    public static function filter_choices_by_categories(array $choices): array
    {
        $choices = is_array($choices) ? $choices : [];

        $normalized = [];
        foreach ($choices as $k => $v) {
            $nk = self::normalize_category_key((string)$k);
            if ($nk === '') continue;
            $normalized[$nk] = (bool)$v;
        }

        $optional = self::get_optional_category_keys();
        $out = [];

        foreach ($optional as $key) {
            $key = self::normalize_category_key((string)$key);
            if ($key === '') continue;
            $out[$key] = (bool)($normalized[$key] ?? false);
        }

        return $out;
    }


    public static function sanitize_bool($v): int
    {
        return !empty($v) ? 1 : 0;
    }

    public static function sanitize_int($v, int $default = 0): int
    {
        $n = is_numeric($v) ? (int)$v : $default;
        return $n < 0 ? $default : $n;
    }

    public static function sanitize_text($v): string
    {
        return sanitize_text_field((string)$v);
    }

    public static function sanitize_html_basic($v): string
    {
        return wp_kses_post((string)$v);
    }

    public static function kses_allow_script(string $html): string
    {
        $allowed = [
            'script' => [
                'type' => true,
                'src' => true,
                'async' => true,
                'defer' => true,
                'id' => true,
                'crossorigin' => true,
                'referrerpolicy' => true,
                'nonce' => true,
                'data-*' => true,
            ],
            'noscript' => [],
            'img' => [
                'src' => true,
                'alt' => true,
                'width' => true,
                'height' => true,
                'style' => true,
            ],
            'iframe' => [
                'src' => true,
                'width' => true,
                'height' => true,
                'frameborder' => true,
                'allow' => true,
                'allowfullscreen' => true,
                'referrerpolicy' => true,
            ],
            'div' => [
                'id' => true,
                'class' => true,
                'style' => true,
                'data-*' => true,
            ],
            'span' => [
                'id' => true,
                'class' => true,
                'style' => true,
                'data-*' => true,
            ],
        ];

        return wp_kses($html, $allowed);
    }

    public static function json_decode_array(string $json): array
    {
        $d = json_decode($json, true);
        return is_array($d) ? $d : [];
    }

    public static function safe_hash(string $input): string
    {
        $salt = defined('AUTH_SALT') ? AUTH_SALT : wp_salt('auth');
        return hash('sha256', $input . '|' . $salt);
    }

    public static function csv_safe_cell(string $v): string
    {
        $v = (string)$v;
        if ($v !== '' && preg_match('/^[=\+\-\@]/', $v)) {
            return "'" . $v;
        }
        return $v;
    }

    public static function ensure_policy_page(): int
    {
        $settings = self::get_settings();
        $page_id = isset($settings['policy_page_id']) ? (int)$settings['policy_page_id'] : 0;

        if ($page_id > 0) {
            $p = get_post($page_id);
            if ($p && $p->post_type === 'page' && $p->post_status !== 'trash') {
                return $page_id;
            }
        }

        $found = get_page_by_path('politica-de-cookies', OBJECT, 'page');
        if ($found && !empty($found->ID)) {
            $settings['policy_page_id'] = (int)$found->ID;
            update_option(BRLGPD_Constants::OPT_SETTINGS, $settings, false);
            return (int)$found->ID;
        }

        $content = self::get_policy_content();


        $new_id = wp_insert_post([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => 'Política de Cookies',
            'post_name' => 'politica-de-cookies',
            'post_content' => $content,
            'post_author' => get_current_user_id(),
        ], true);

        if (is_wp_error($new_id) || !$new_id) {
            return 0;
        }

        $settings['policy_page_id'] = (int)$new_id;
        update_option(BRLGPD_Constants::OPT_SETTINGS, $settings, false);

        return (int)$new_id;
    }

    public static function get_policy_page_url(): string
    {
        $id = self::ensure_policy_page();
        if ($id > 0) {
            $url = get_permalink($id);
            return is_string($url) ? $url : '';
        }
        return '';
    }

    public static function get_policy_content(): string
    {
        $stored = get_option(BRLGPD_Constants::OPT_POLICY_CONTENT, '');
        $stored = is_string($stored) ? $stored : '';

        if (trim($stored) === '') {
            $stored = "<h2>Política de Cookies</h2>\n"
                . "<p>Esta Política de Cookies explica o que são cookies, como e por que os utilizamos, e como você pode gerenciar suas preferências. Ao continuar navegando, você pode aceitar, rejeitar ou personalizar o uso de cookies não essenciais, conforme disponível.</p>\n"

                . "<h3>1. O que são cookies?</h3>\n"
                . "<p>Cookies são pequenos arquivos de texto armazenados no seu navegador ou dispositivo quando você visita um site. Eles ajudam a reconhecer seu dispositivo, lembrar preferências, melhorar a experiência de navegação e permitir funcionalidades essenciais.</p>\n"

                . "<h3>2. Como utilizamos cookies</h3>\n"
                . "<p>Podemos utilizar cookies e tecnologias semelhantes para:</p>\n"
                . "<ul>\n"
                . "<li><strong>Garantir o funcionamento do site</strong> (cookies necessários);</li>\n"
                . "<li><strong>Salvar preferências</strong> e melhorar sua experiência de uso;</li>\n"
                . "<li><strong>Medir e analisar</strong> como o site é utilizado (cookies de estatística);</li>\n"
                . "<li><strong>Entregar conteúdos e anúncios</strong> mais relevantes (cookies de marketing), quando aplicável.</li>\n"
                . "</ul>\n"

                . "<h3>3. Categorias de cookies</h3>\n"
                . "<p>Nosso site pode utilizar as seguintes categorias:</p>\n"
                . "<ul>\n"
                . "<li><strong>Cookies Necessários:</strong> essenciais para o funcionamento do site e para recursos básicos (ex.: segurança, navegação, autenticação e preferências indispensáveis). Estes cookies não podem ser desativados.</li>\n"
                . "<li><strong>Cookies de Estatística:</strong> ajudam a entender como os visitantes interagem com o site, permitindo melhorias (ex.: páginas mais acessadas, tempo de permanência, erros). Utilizamos essas informações de forma agregada sempre que possível.</li>\n"
                . "<li><strong>Cookies de Marketing:</strong> usados para exibir conteúdos e anúncios personalizados, medir campanhas e construir audiências (quando aplicável). Podem ser definidos por nós ou por serviços de terceiros.</li>\n"
                . "</ul>\n"

                . "<h3>4. Cookies de terceiros</h3>\n"
                . "<p>Em alguns casos, podemos utilizar serviços de terceiros para estatísticas, mídia incorporada (ex.: vídeos) ou marketing. Esses terceiros podem definir cookies no seu dispositivo. As práticas desses serviços são regidas por suas próprias políticas de privacidade e cookies.</p>\n"

                . "<h3>5. Como gerenciar suas preferências</h3>\n"
                . "<p>Você pode alterar suas preferências a qualquer momento por meio do botão abaixo. Ao ajustar as opções, cookies não essenciais podem deixar de ser utilizados. Observe que desativar determinadas categorias pode afetar algumas funcionalidades e sua experiência no site.</p>\n"
                . "[brlgpd_manage_button]\n"

                . "<h3>6. Como desativar cookies no navegador</h3>\n"
                . "<p>Além do controle oferecido pelo site, você pode configurar seu navegador para bloquear ou excluir cookies. Os procedimentos variam conforme o navegador. Em geral, essas opções ficam em <em>Configurações</em> &gt; <em>Privacidade</em> &gt; <em>Cookies</em>. Ao bloquear cookies necessários, algumas áreas do site podem não funcionar corretamente.</p>\n"

                . "<h3>7. Base legal e privacidade</h3>\n"
                . "<p>O tratamento de dados pessoais pode ocorrer para cumprimento de obrigações legais/regulatórias, execução de contrato, legítimo interesse (quando aplicável e observados os requisitos), e/ou mediante consentimento, especialmente para cookies não essenciais. Para mais detalhes, consulte nossa Política de Privacidade (quando disponível).</p>\n"

                . "<h3>8. Alterações nesta Política</h3>\n"
                . "<p>Podemos atualizar esta Política de Cookies periodicamente para refletir mudanças tecnológicas, legais ou operacionais. Recomendamos que você revise esta página de tempos em tempos. Quando necessário, um novo consentimento poderá ser solicitado.</p>\n"

                . "<h3>9. Contato</h3>\n"
                . "<p>Se você tiver dúvidas sobre esta Política de Cookies ou sobre privacidade no site, entre em contato pelos canais informados na página de contato ou na Política de Privacidade.</p>\n";
        }

        return self::normalize_policy_content($stored);
    }

    public static function set_policy_content(string $content): void
    {
        $content = wp_kses_post($content);
        update_option(BRLGPD_Constants::OPT_POLICY_CONTENT, $content, false);
    }

    private static function normalize_policy_content(string $content): string
    {
        $content = (string)$content;

        $content = preg_replace('/\s*\[brlgpd_manage_button[^\]]*\]\s*/i', "\n", $content);

        $content = trim($content);

        $content .= "\n\n" . '[brlgpd_manage_button]' . "\n";

        return $content;
    }

    public static function sync_policy_page_content(): void
    {
        $page_id = self::ensure_policy_page();
        if (!$page_id) return;

        $content = self::get_policy_content();

        wp_update_post([
            'ID' => $page_id,
            'post_content' => $content,
        ]);
    }
}

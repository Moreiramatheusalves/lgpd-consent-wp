<?php

/**
 * Plugin Name:       LGPD Consent BRENIAC
 * Plugin URI:        https://pluginswp.breniacsoftec.com/
 * Description:       Banner de consentimento LGPD com categorias, bloqueio de scripts e logs opcionais.
 * Version:           1.0.0
 * Author:            BR Eniac SofTec
 * Author URI:        https://breniacsoftec.com
 * Text Domain:       br-lgpd-consent
 * Domain Path:       /languages
 * Update URI:        https://github.com/Moreiramatheusalves/lgpd-consent-wp
 */

if (!defined('ABSPATH')) exit;

define('BRLGPD_FILE', __FILE__);
define('BRLGPD_DIR', plugin_dir_path(__FILE__));
define('BRLGPD_URL', plugin_dir_url(__FILE__));
define('BRLGPD_VERSION', '1.0.0');

require_once BRLGPD_DIR . 'includes/class-constants.php';
require_once BRLGPD_DIR . 'includes/class-utils.php';
require_once BRLGPD_DIR . 'includes/class-i18n.php';
require_once BRLGPD_DIR . 'includes/storage/class-db.php';
require_once BRLGPD_DIR . 'includes/storage/class-consent-cookie.php';
require_once BRLGPD_DIR . 'includes/storage/class-consent-log.php';
require_once BRLGPD_DIR . 'includes/admin/class-settings.php';
require_once BRLGPD_DIR . 'includes/admin/class-categories.php';
require_once BRLGPD_DIR . 'includes/admin/class-scripts.php';
require_once BRLGPD_DIR . 'includes/admin/class-export.php';
require_once BRLGPD_DIR . 'includes/admin/class-policy.php';
require_once BRLGPD_DIR . 'includes/admin/class-admin.php';
require_once BRLGPD_DIR . 'includes/frontend/class-script-runner.php';
require_once BRLGPD_DIR . 'includes/frontend/class-banner.php';
require_once BRLGPD_DIR . 'includes/frontend/class-frontend.php';
require_once BRLGPD_DIR . 'includes/api/class-ajax.php';
require_once BRLGPD_DIR . 'includes/api/class-rest.php';
require_once BRLGPD_DIR . 'includes/class-plugin.php';

register_activation_hook(__FILE__, function () {
    BRLGPD_DB::maybe_create_tables();
    BRLGPD_Constants::ensure_defaults();
    BRLGPD_Utils::ensure_policy_page();
});

add_action('plugins_loaded', function () {
    BRLGPD_Plugin::instance();
});

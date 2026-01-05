<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

require_once plugin_dir_path(__FILE__) . 'includes/class-constants.php';
require_once plugin_dir_path(__FILE__) . 'includes/storage/class-db.php';

$settings = get_option(BRLGPD_Constants::OPT_SETTINGS, []);
$delete = !empty($settings['delete_data_on_uninstall']);

if ($delete) {
    delete_option(BRLGPD_Constants::OPT_SETTINGS);
    delete_option(BRLGPD_Constants::OPT_SCRIPTS);
    delete_option(BRLGPD_Constants::OPT_CATEGORIES);
    delete_option(BRLGPD_Constants::OPT_POLICY_CONTENT);

    $page_id = isset($settings['policy_page_id']) ? (int)$settings['policy_page_id'] : 0;
    if ($page_id > 0) {
        $p = get_post($page_id);
        if ($p && $p->post_type === 'page' && $p->post_status !== 'trash') {
            if ((string)$p->post_name === 'politica-de-cookies') {
                wp_delete_post($page_id, true);
            }
        }
    }

    BRLGPD_DB::drop_tables();
}

<?php

/**
 * Fired when the plugin is uninstalled.
 *
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$registered_options = array(
    'paypecker_plugin_options',
    'paypecker_plugin_product_sync_options',
);

foreach ($registered_options as $option) {
    delete_option($option);
}

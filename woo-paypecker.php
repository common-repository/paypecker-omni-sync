<?php

use PayPecker\WooPaypecker\Api\Setup as ApiSetUp;
use PayPecker\WooPaypecker\Setup;

/**
 * Plugin Name: PayPecker Omni Sync
 * Description: Connect paypecker and a woocommerce shop, it syncs the orders, payment and inventory
 * Version: 1.0.0
 * Requires at least: 5.3
 * Requires PHP: 5.6
 * Author: Paypecker
 * Author URI: https://paypecker.co
 **/

// If this file is accessed directory, then abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Check if WooCommerce is active
 **/
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    define('WOO_PAYPECKER_BASE_PATH', realpath(plugin_dir_path(__FILE__)));

    $autoloadpath = WOO_PAYPECKER_BASE_PATH . DIRECTORY_SEPARATOR  . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

    if (is_readable($autoloadpath)) {
        require $autoloadpath;
    }

    $setup = new Setup();

    add_action('admin_menu', function () use ($setup) {
        $setup->init();
    });

    add_action('rest_api_init', function () {
        $api = new ApiSetUp();
        $api->init();
    });

    add_action('plugins_loaded', function () use ($setup) {
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$setup, 'action_links']);
    }, 99);


    function filter_off_out_of_stock_variation_options($html, $args)
    {
        $product = $args['product'];
        $product_variations = $product->get_available_variations();

        $attribute = $args['attribute'];
        $options = [];

        foreach ($product_variations as $variation) {
            if (isset($variation['attributes'])) {
                $key = "attribute_{$attribute}";
                if (isset($variation['attributes'][$key]) && ($variation['max_qty'] === "" || $variation['availability_html'] === "")) {
                    array_push($options, $variation['attributes'][$key]);
                }
            }
        }
        $terms = wc_get_product_terms($product->get_id(), $attribute, array('fields' => 'all'));

        foreach ($terms as $term) {
            if (in_array($term->slug, $options)) {
                $html = str_replace('<option value="' . esc_attr($term->slug) . '" >' . esc_attr($term->name) . '</option>', '', $html);
                $html = str_replace('<option value="' . esc_attr($term->slug) . '" ', '<option hidden disabled="disabled" style="display:none" value="' . esc_attr($term->slug) . '" ', $html);
            }
        }

        return $html;
    }
    add_filter('woocommerce_dropdown_variation_attribute_options_html', 'filter_off_out_of_stock_variation_options', 10, 2);
}

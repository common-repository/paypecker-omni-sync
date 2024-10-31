<?php

namespace PayPecker\WooPaypecker\Api;

use PayPecker\WooPaypecker\Api\Product;

class Setup
{
    const REST_NAMESPACE = 'paypecker/v1';

    /**
     * bootstrap all custom rest api route explosed by paypecker
     */
    public function init()
    {
        register_rest_route(
            self::REST_NAMESPACE,
            '/products',
            array(
                'methods' => 'POST',
                'callback' => [new Product(), 'handleProductUpdate'],
                'permission_callback' => __return_false(),
            )
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/products/(?P<sku>[a-zA-Z0-9_-]+)',
            array(
                'methods' => 'PUT',
                'callback' => [new Product(), 'handleSingleProductUpdate'],
                'permission_callback' => __return_false(),
            )
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/products/(?P<sku>[a-zA-Z0-9_-]+)',
            array(
                'methods' => 'DELETE',
                'callback' => [new Product(), 'handleDeleteProduct'],
                'permission_callback' => __return_false(),
            )
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/ping',
            array(
                'methods' => 'GET',
                'callback' => [new Ping(), 'confirmCredentials'],
                'permission_callback' => __return_false(),
            )
        );
    }
}

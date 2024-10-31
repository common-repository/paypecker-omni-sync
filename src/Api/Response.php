<?php

namespace PayPecker\WooPaypecker\Api;

use WP_REST_Response;

class Response
{
    const HTTP_OK = 200;
    const HTTP_UNPROCESSED_ENTITY = 422;
    const HTTP_CREATED = 201;
    const HTTP_SERVER_ERROR = 500;
    const HTTP_AUTHENTICATED = 401;
    const HTTP_NOT_FOUND = 404;

    public static function send($message, $status_code, $data = null, $error = null): WP_REST_Response
    {
        $response_data = [
            'message' => $message,
        ];

        if ($data) {
            $response_data['data'] = $data;
        }

        if ($error) {
            $response_data['error'] = $error;
        }
        return new WP_REST_Response($response_data, $status_code);
    }
}

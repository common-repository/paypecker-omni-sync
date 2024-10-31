<?php

namespace PayPecker\WooPaypecker\Api;

use Exception;
use WP_REST_Request;
use WP_REST_Response;

class Ping
{
    /**
     * Handles a ping request
     * 
     * @param WP_RESR_Request $request
     * 
     * @return WP_REST_Response
     */
    public function confirmCredentials(WP_REST_Request $request): WP_REST_Response
    {
        try {
            $header_params = $request->get_headers();
            $authorization_token = "";
            if (isset($header_params['authorization'])) {
                $authorization_token =  is_array($header_params['authorization']) ? $header_params['authorization'][0] : $header_params['authorization'];
            }
            if (!Validator::tokenIsValid($authorization_token)) {
                return Response::send('Invalid token', Response::HTTP_AUTHENTICATED);
            }

            return Response::send('ping!', Response::HTTP_OK);
        } catch (Exception $e) {
            return Response::send('something went wrong', Response::HTTP_SERVER_ERROR);
        }
    }
}

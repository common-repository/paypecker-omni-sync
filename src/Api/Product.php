<?php

namespace PayPecker\WooPaypecker\Api;

use Exception;
use PayPecker\WooPaypecker\Repository\ProductRepository;
use WP_REST_Request;
use WP_REST_Response;

class Product
{
    /**
     * Handles product creation and update
     * 
     * it creates a product if the product don't exist and update it if it can be found
     * 
     * @param WP_RESR_Request $request
     * 
     * @return WP_REST_Response
     */
    public function handleProductUpdate(WP_REST_Request $request): WP_REST_Response
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

            if (!Validator::userIsSet()) {
                return Response::send('user is not set', Response::HTTP_AUTHENTICATED);
            }

            $request_params = $request->get_json_params();
            if (!$request_params) {
                $request_params = [];
            }
            $validator = Validator::make($request_params, [
                'sku' => 'required|string',
                'short_description' => 'required|string',
                'description' => 'sometimes| required|string',
                'regular_price' => 'required|numeric',
                'sale_price' => 'sometimes|required|numeric',
                'regular_price' => 'sometimes|required|numeric',
                'quantity' => 'required|numeric',
                'tags' => 'sometimes|required|array|min:1',
                'tags.*' => 'string',
                'categories' => 'sometimes|required|array',
                'product_name' => 'required|string',
                'variations' => 'sometimes|array',
                'variations.*.attributes' => 'required|array|min:1',
                'variations.*.sku' => 'required|string',
                'variations.*.sale_price' => 'required|numeric',
                'variations.*.regular_price' => 'required|numeric',
                'variations.*.stock_quantity' => 'required|numeric',
            ]);

            $validator->after(function ($validator) use ($request_params) {
                if (!isset($request_params['categories']) || !is_array($request_params['categories'])) {
                    return;
                }

                if (!Validator::categoryIsValid($request_params['categories'])) {
                    $validator->errors()->add("categories", 'Invalid category format');
                    return;
                }
            });

            if ($validator->fails()) {
                return Response::send('validation failed', Response::HTTP_UNPROCESSED_ENTITY, null, $validator->errors());
            }
            $request_params = $validator->valid();
            $product = ProductRepository::persistProductInfoToInventory($request_params);

            return Response::send('product was processed successfully', Response::HTTP_OK, $product);
        } catch (Exception $e) {
            return Response::send('something went wrong', Response::HTTP_SERVER_ERROR);
        }
    }


    /**
     * Handles single product update
     * 
     * it update the properties of an existing product
     * 
     * @param WP_RESR_Request $request
     * 
     * @return WP_REST_Response
     */
    public function handleSingleProductUpdate(WP_REST_Request $request): WP_REST_Response
    {
        try {
            $header_params = $request->get_headers();
            $authorization_token = "";
            if (isset($header_params['authorization'])) {
                $authorization_token =  is_array($header_params['authorization']) ? $header_params['authorization'][0] : $header_params['authorization'];
            }
            $urlParams = $request->get_url_params();
            $sku = $urlParams['sku'];

            if (!Validator::tokenIsValid($authorization_token)) {
                return Response::send('Invalid token', Response::HTTP_AUTHENTICATED);
            }

            if (!Validator::userIsSet()) {
                return Response::send('user is not set', Response::HTTP_AUTHENTICATED);
            }

            $request_params = $request->get_json_params();
            $request_params['sku'] = $sku;
            $validator = Validator::make($request_params, [
                'sku' => 'required|string',
                'short_description' => 'sometimes|required|string',
                'regular_price' => 'sometimes|required|numeric',
                'quantity' => 'sometimes|required|numeric',
                'tags' => 'sometimes|required|array|min:1',
                'categories' => 'sometimes|required|array',
                'product_name' => 'sometimes|required|string'
            ]);

            $validator->after(function ($validator) use ($request_params) {
                if (!isset($request_params['categories']) || !is_array($request_params['categories'])) {
                    return;
                }

                if (!Validator::categoryIsValid($request_params['categories'])) {
                    $validator->errors()->add("categories", 'Invalid category format');
                    return;
                }
            });

            if ($validator->fails()) {
                return Response::send('validation failed', Response::HTTP_UNPROCESSED_ENTITY, null, $validator->errors());
            }

            $request_params = $validator->valid();
            if (!ProductRepository::doesProductExist($request_params['sku'])) {
                return Response::send('Resorce not found', Response::HTTP_NOT_FOUND);
            }
            $product = ProductRepository::persistProductInfoToInventoryForAProduct($request_params);

            return Response::send('product was processed successfully', Response::HTTP_OK, $product);
        } catch (Exception $e) {
            return Response::send('something went wrong', Response::HTTP_SERVER_ERROR);
        }
    }

    /**
     * Handles deleting of product
     * 
     * it update the properties of an existing product
     * 
     * @param WP_RESR_Request $request
     * 
     * @return WP_REST_Response
     */
    public function handleDeleteProduct(WP_REST_Request $request): WP_REST_Response
    {
        try {
            $header_params = $request->get_headers();
            $authorization_token = "";
            if (isset($header_params['authorization'])) {
                $authorization_token =  is_array($header_params['authorization']) ? $header_params['authorization'][0] : $header_params['authorization'];
            }

            $urlParams = $request->get_url_params();
            $sku = sanitize_text_field($urlParams['sku']);

            if (!Validator::tokenIsValid($authorization_token)) {
                return Response::send('Invalid token', Response::HTTP_AUTHENTICATED);
            }

            if (!Validator::userIsSet()) {
                return Response::send('user is not set', Response::HTTP_AUTHENTICATED);
            }

            $request_params = $request->get_json_params();
            $request_params['sku'] = $sku;

            if (!ProductRepository::doesProductExist($request_params['sku'])) {
                return Response::send('Resorce not found', Response::HTTP_NOT_FOUND);
            }
            ProductRepository::deleteProduct($sku);

            return Response::send('product was deleted successfully', Response::HTTP_OK);
        } catch (Exception $e) {
            return Response::send('something went wrong', Response::HTTP_SERVER_ERROR);
        }
    }
}

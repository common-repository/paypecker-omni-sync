<?php

namespace PayPecker\WooPaypecker\Repository;

use PayPecker\Setup;
use PayPecker\WooPaypecker\Setup as WooPaypeckerSetup;
use WC_REST_Exception;

class ProductRepository
{
    const POST_TYPE_PRODUCT = 'product';
    const DEFAULT_POST_STATUS = 'publish';
    const TAXONOMY_CATEGORY = 'product_cat';
    const POST_TYPE_VARIABLE = 'product_variation';
    const TAXONOMY_TAG = 'product_tag';
    const STATUS_DRAFT = 'draft';

    public static function persistProductInfoToInventory(array $attributes)
    {
        $product_id = self::getProductId($attributes);

        if (!empty($product_id) && function_exists('wc_get_product')) {
            $product = wc_get_product($product_id);
            $product->set_sku(sanitize_text_field($attributes['sku']));

            if (self::shouldShowInProductName()) {
                if (isset($attributes['product_name'])) {
                    $product->set_name(sanitize_text_field($attributes['product_name']));
                }
            }

            if (self::shouldSyncRegularPrice()) {
                if (isset($attributes['regular_price'])) {
                    $product->set_regular_price(sanitize_text_field($attributes['regular_price']));
                }
            }

            if (self::shouldSyncSalesPrice()) {
                if (isset($attributes['sale_price'])) {
                    $product->set_sale_price(sanitize_text_field($attributes['sale_price']));
                }
            }

            if (self::shouldSyncPrice()) {
                if (isset($attributes['price'])) {
                    $product->set_price(sanitize_text_field($attributes['price']));
                }
            }

            if (self::shouldSyncCategories()) {
                if (isset($attributes['categories'])) {
                    $product->set_category_ids(self::getCategoryIds($attributes['categories']));
                }
            }

            if (self::shouldSyncTag()) {
                if (isset($attributes['tags'])) {
                    $product->set_tag_ids(self::getTagIds($attributes['tags']));
                }
            }

            if (self::shouldSyncShortDescription()) {
                if (isset($attributes['short_description'])) {
                    $product->set_short_description(sanitize_text_field($attributes['short_description']));
                }
            }

            if (self::shouldSyncDescription()) {
                if (isset($attributes['description'])) {
                    $product->set_description(sanitize_text_field($attributes['description']));
                }
            }

            if (empty($attributes['variations'])) {
                if (self::shouldSyncStockQuantity()) {
                    if (isset($attributes['quantity'])) {
                        $product->set_stock_quantity((intval($attributes['quantity'])));
                        if (intval($attributes['quantity']) < 1) {
                            $product->set_catalog_visibility('hidden');
                        } else {
                            $product->set_catalog_visibility('visible');
                        }
                    }
                }
                $product->set_backorders(self::getBackOrderSetting($attributes));
                $product->set_manage_stock(self::getManageStockSetting($attributes));
            }

            if (self::shouldUpdateImages()) {

                if (isset($attributes['images'])) {
                    $product = self::add_image_to_product($attributes['images'], $product);
                }
            }
            if (!self::shouldShowInCatalog()) {
                $product->set_catalog_visibility('hidden');
            }

            if (!empty($attributes['variations'])) {
                $totalVariationStockQuantity = 0;
                foreach ($attributes['variations'] as $variation) {
                    $totalVariationStockQuantity += $variation['stock_quantity'];
                }

                if (intval($totalVariationStockQuantity) < 1) {
                    $product->set_catalog_visibility('hidden');
                } else {
                    $product->set_catalog_visibility('visible');
                }
            }

            $product->save();

            if (!empty($attributes['variations'])) {
                wp_set_object_terms($product_id, 'variable', 'product_type');

                foreach ($attributes['variations'] as $variation) {
                    self::createOrUpdateProductVariation($product_id, $variation);
                }
            }
        }

        return $product->get_data();
    }

    public static function add_image_to_product($images, $product)
    {
        $gallery = [];
        if (is_array($images)) {
            foreach ($images as $image) {
                $upload = wc_rest_upload_image_from_url(esc_url_raw($image['url']));
                if (is_wp_error($upload)) {
                    throw new WC_REST_Exception('woocommerce_product_image_upload_error', $upload->get_error_message(), 400);
                }
                $attachment_id = wc_rest_set_uploaded_image_as_attachment($upload, $product->get_id());

                if (isset($image['use_as_featured']) && true === $image['use_as_featured']) {
                    $product->set_image_id($attachment_id);
                } else {
                    $gallery[] = $attachment_id;
                }

                if (!empty($image['alt'])) {
                    update_post_meta($attachment_id, '_wp_attachment_image_alt', wc_clean($image['alt']));
                }

                if (!empty($image['name'])) {
                    wp_update_post(array('ID' => $attachment_id, 'post_title' => $image['name']));
                }
            }

            if (!empty($gallery)) {
                $product->set_gallery_image_ids($gallery);
            }
        } else {
            $product->set_image_id('');
            $product->set_gallery_image_ids(array());
        }

        return $product;
    }

    public static function persistProductInfoToInventoryForAProduct(array $attributes)
    {
        $product_id = self::getProductId($attributes);

        if (!empty($product_id) && function_exists('wc_get_product')) {
            $product = wc_get_product($product_id);

            if (self::shouldSyncRegularPrice()) {
                if (isset($attributes['regular_price'])) {
                    $product->set_regular_price(sanitize_text_field($attributes['regular_price']));
                };
            }

            if (self::shouldSyncSalesPrice()) {
                if (isset($attributes['sale_price'])) {
                    $product->set_sale_price(sanitize_text_field($attributes['sale_price']));
                };
            }

            if (self::shouldSyncPrice()) {
                if (isset($attributes['price'])) {
                    $product->set_price(sanitize_text_field($attributes['price']));
                };
            }

            if (self::shouldSyncCategories()) {
                if (isset($attributes['categories'])) {
                    $product->set_category_ids(self::getCategoryIds($attributes['categories']));
                };
            }


            if (self::shouldSyncTag()) {
                if (isset($attributes['tags'])) {
                    $product->set_tag_ids(self::getTagIds($attributes['tags']));
                };
            }


            if (self::shouldSyncDescription()) {
                if (isset($attributes['description'])) {
                    $product->set_description(sanitize_text_field($attributes['description']));
                };
            }

            if (self::shouldSyncShortDescription()) {
                if (isset($attributes['short_description'])) {
                    $product->set_short_description(sanitize_text_field($attributes['short_description']));
                };
            }


            if (self::shouldSyncStockQuantity()) {
                if (isset($attributes['quantity'])) {
                    $product->set_stock_quantity(($attributes['quantity']));
                    if (intval($attributes['quantity']) < 1) {
                        $product->set_catalog_visibility('hidden');
                    } else {
                        $product->set_catalog_visibility('visible');
                    }
                };
            }

            $product->set_backorders(self::getBackOrderSetting($attributes));
            $product->set_manage_stock(self::getManageStockSetting($attributes));


            $product->save();
            return $product->get_data();
        }
    }

    public static function shouldSyncRegularPrice(): bool
    {
        $options = get_option(WooPaypeckerSetup::SYNC_OPTIONS_IDENTIFIER);
        return isset($options['sync_regular_price']) ? $options['sync_regular_price'] : false;
    }

    public static function shouldUpdateImages(): bool
    {
        $options = get_option(WooPaypeckerSetup::SYNC_OPTIONS_IDENTIFIER);
        return isset($options['updateImages']) ? $options['updateImages'] : false;
    }

    public static function shouldSyncSalesPrice(): bool
    {
        $options = get_option(WooPaypeckerSetup::SYNC_OPTIONS_IDENTIFIER);
        return isset($options['sync_sales_price']) ? $options['sync_sales_price'] : false;
    }

    public static function shouldSyncPrice(): bool
    {
        $options = get_option(WooPaypeckerSetup::SYNC_OPTIONS_IDENTIFIER);
        return isset($options['sync_price']) ? $options['sync_price'] : false;
    }

    public static function shouldSyncCategories(): bool
    {
        $options = get_option(WooPaypeckerSetup::SYNC_OPTIONS_IDENTIFIER);
        return isset($options['sync_categories']) ? $options['sync_categories'] : false;
    }

    public static function shouldSyncDescription(): bool
    {
        $options = get_option(WooPaypeckerSetup::SYNC_OPTIONS_IDENTIFIER);
        return isset($options['sync_description']) ? $options['sync_description'] : false;
    }

    public static function shouldShowInProductName(): bool
    {
        $options = get_option(WooPaypeckerSetup::SYNC_OPTIONS_IDENTIFIER);
        return isset($options['sync_product_name']) ? $options['sync_product_name'] : false;
    }

    public static function shouldSyncShortDescription(): bool
    {
        $options = get_option(WooPaypeckerSetup::SYNC_OPTIONS_IDENTIFIER);
        return isset($options['sync_short_description']) ? $options['sync_short_description'] : false;
    }

    public static function shouldSyncStockQuantity(): bool
    {
        $options = get_option(WooPaypeckerSetup::SYNC_OPTIONS_IDENTIFIER);
        return isset($options['sync_stock_quantity']) ? $options['sync_stock_quantity'] : false;
    }

    public static function shouldSyncTag(): bool
    {
        $options = get_option(WooPaypeckerSetup::SYNC_OPTIONS_IDENTIFIER);
        return isset($options['sync_tag']) ? $options['sync_tag'] : false;
    }

    public static function getManageStockSetting(array $attributes): bool
    {
        $options = get_option(WooPaypeckerSetup::OPTIONS_IDENTIFIER);
        $manageStock = isset($options['update_inventory']) ? $options['update_inventory'] : false;
        return isset($attributes['update_inventory']) ? $attributes['update_inventory'] :  $manageStock;
    }

    public static function shouldShowInCatalog()
    {
        $options = get_option(WooPaypeckerSetup::OPTIONS_IDENTIFIER);
        return isset($options['show_catalog_visibility']) ? $options['show_catalog_visibility'] : false;
    }

    public static function getBackOrderSetting(array $attributes): string
    {
        $options = get_option(WooPaypeckerSetup::OPTIONS_IDENTIFIER);
        $backOrder = isset($options['allow_back_order']) ? $options['allow_back_order'] : false;
        $isBackOrderAllowed = isset($attributes['allow_back_order']) ? $attributes['allow_back_order'] :  $backOrder;
        return $isBackOrderAllowed ? 'yes' : 'no';
    }

    public static function getTagIds(array $tags)
    {
        $tag_ids = [];
        foreach ($tags as $tag) {
            $termObj =  get_term_by('name', sanitize_text_field($tag), self::TAXONOMY_TAG);
            if (!$termObj) {
                $termObj = (object) wp_insert_term($tag, self::TAXONOMY_TAG);
            }
            $tag_id = $termObj->term_id;
            array_push($tag_ids, intval($tag_id));
        }


        return $tag_ids;
    }

    public static function getProductId(array $attributes)
    {
        if (function_exists('wc_get_product_id_by_sku')) {
            $product_id = null;
            if (!empty($attributes['sku'])) {
                $product_id = wc_get_product_id_by_sku($attributes['sku']);
            }

            if (!$product_id) {
                $options = get_option(WooPaypeckerSetup::OPTIONS_IDENTIFIER);
                $post_args = array(
                    'post_author' => intval($options['user_id']),
                    'post_title' => sanitize_text_field($attributes['product_name']),
                    'post_type' => self::POST_TYPE_PRODUCT,
                    'post_status' => self::DEFAULT_POST_STATUS
                );

                $product_id = wp_insert_post($post_args);
            }

            return $product_id;
        }
    }

    public static function doesProductExist($sku)
    {
        $product_id = wc_get_product_id_by_sku($sku);
        if ($product_id) {
            return true;
        }
        return false;
    }

    public static function getCategoryIds($categories)
    {
        $category_ids = [];
        foreach ($categories as $category) {
            if (is_array($category)) {
                if (!isset($category['value'])) {
                    continue;
                }
                $category_id = self::getCategoryIdAndCreateParentIfTheyDontExist($category);
            } else {
                $termObj =  get_term_by('name', sanitize_text_field($category), self::TAXONOMY_CATEGORY);
                if (!$termObj) {
                    $termObj = (object) wp_insert_term($category, self::TAXONOMY_CATEGORY);
                }
                $category_id = $termObj->term_id;
            }

            array_push($category_ids, intval($category_id));
        }
        return $category_ids;
    }

    public static function getCategoryIdAndCreateParentIfTheyDontExist($category, $termObjParentId = null)
    {
        if (isset($category['parent'])) {
            $termObjParentId = self::getCategoryIdAndCreateParentIfTheyDontExist($category['parent'], $termObjParentId);
        }
        $termObj =  get_term_by('name', sanitize_text_field($category['value']), self::TAXONOMY_CATEGORY);
        if (!$termObj) {
            $termObj =  (object) wp_insert_term($category['value'], self::TAXONOMY_CATEGORY, ['parent' => $termObjParentId]);
        }

        return  $termObj->term_id;
    }

    public static function createOrUpdateProductVariation(int $product_id, $variation_data, $position = 0)
    {

        $product = wc_get_product($product_id);

        $variation = self::getVariableProductInstance($product, $variation_data);

        foreach ($variation_data['attributes'] as $attribute => $term_name) {
            $taxonomy = 'pa_' . $attribute;

            self::insertTaxonomyIfItDoesNotExist($attribute);

            self::insertTermIfItDoesNotExist($taxonomy, $term_name);

            $attributes = (array) $product->get_attributes();
            $term    = get_term_by('name', $term_name, $taxonomy);
            $term_id = $term->term_id;
            $term_slug = $term->slug;

            $attributes = self::populateProductAttribute($attributes, $taxonomy, $term_id);

            $product->set_attributes($attributes);

            $product->save();

            if (!has_term($term_name, $taxonomy, $product->get_id())) {
                wp_set_object_terms($product->get_id(), $term_slug, $taxonomy, true);
            }
            update_post_meta($variation->get_id(), 'attribute_' . $taxonomy, $term_slug);
        }

        if (!empty($variation_data['sku'])) {
            $variation->set_sku(sanitize_text_field($variation_data['sku']));
        }

        if (self::shouldSyncSalesPrice()) {
            if (empty($variation_data['sale_price'])) {
                $variation->set_price(sanitize_text_field($variation_data['regular_price']));
            } else {
                $variation->set_price(sanitize_text_field($variation_data['sale_price']));
                $variation->set_sale_price(sanitize_text_field($variation_data['sale_price']));
            }
        }


        if (self::shouldSyncRegularPrice()) {
            $variation->set_regular_price(sanitize_text_field($variation_data['regular_price']));
        }

        if (self::shouldSyncStockQuantity()) {
            if (!empty($variation_data['stock_quantity'])) {
                $variation->set_stock_quantity(intval($variation_data['stock_quantity']));
                $variation->set_manage_stock(true);
            } else {
                $variation->set_manage_stock(false);
            }
        }

        if (self::shouldUpdateImages()) {
            if (isset($variation_data['images'])) {
                $variation = self::add_image_to_product($variation_data['images'], $variation);
            }
        }

        $variation->save();
    }

    public static function insertTaxonomyIfItDoesNotExist($attribute)
    {
        $availableAttributesKey = array();
        $attributes = wc_get_attribute_taxonomies();
        foreach ($attributes as $key => $value) {
            array_push($availableAttributesKey, $attributes[$key]->attribute_name);
        }
        if (!in_array($attribute, $availableAttributesKey)) {
            $args = array(
                'id' => '',
                'slug'    => $attribute,
                'name'   => __($attribute, 'woocommerce'),
                'type'    => 'select',
                'orderby' => 'menu_order',
                'has_archives'  => false,
                'limit' => 1,
                'is_in_stock' => 1
            );
            wc_create_attribute($args);
        }
    }

    public static function insertTermIfItDoesNotExist($taxonomy, $term_name)
    {
        if (!term_exists($term_name, $taxonomy)) {
            wp_insert_term($term_name, $taxonomy);
        }
    }

    public static function populateProductAttribute($attributes, $taxonomy, $term_id)
    {
        if (array_key_exists($taxonomy, $attributes)) {
            foreach ($attributes as $key => $attribute) {
                if ($key == $taxonomy) {
                    $attribute->set_options(array($term_id));
                    $attributes[$key] = $attribute;
                }
            }
        } else {

            $attribute = new \WC_Product_Attribute();

            $attribute->set_id(sizeof($attributes) + 1);
            $attribute->set_name($taxonomy);
            $attribute->set_options(array($term_id));
            $attribute->set_position(sizeof($attributes) + 1);
            $attribute->set_visible(true);
            $attribute->set_variation(true);
            $attributes[$taxonomy] = $attribute;
        }

        return $attributes;
    }

    public static function getVariableProductInstance($product, array $variation_data)
    {
        $variation_id = wc_get_product_id_by_sku($variation_data['sku']);
        if (!$variation_id) {
            $product_id = $product->get_id();


            $variation_post = array(
                'post_title'  => $product->get_name(),
                'post_name'   => 'product-' . $product_id . '-variation',
                'post_status' => self::DEFAULT_POST_STATUS,
                'post_parent' => $product_id,
                'post_type'   => self::POST_TYPE_VARIABLE,
                'guid'        => $product->get_permalink()
            );

            $variation_id = wp_insert_post($variation_post);
        }
        return wc_get_product($variation_id);
    }

    public static function deleteProduct($sku)
    {
        $id = wc_get_product_id_by_sku($sku);
        $product = wc_get_product($id);

        if ($product->is_type('variable')) {
            foreach ($product->get_children() as $child_id) {
                $child = wc_get_product($child_id);
                $child->delete(true);
            }
        }

        $product->delete(true);

        if ($parent_id = wp_get_post_parent_id($id)) {
            wc_delete_product_transients($parent_id);
        }
    }
}

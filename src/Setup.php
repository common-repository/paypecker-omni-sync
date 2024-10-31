<?php

namespace PayPecker\WooPaypecker;

class Setup
{
    const MANAGE_ADMIN_OPTIONS = 'manage_options';
    const SETTINGS_PAGE_TITLE = 'PayPecker Settings';
    const SETTINGS_MENU_TITLE = 'PayPecker Settings';
    const PLUGIN_SLUG = 'paypecker_plugin';
    const OPTIONS_IDENTIFIER = 'paypecker_plugin_options';
    const SYNC_OPTIONS_IDENTIFIER = 'paypecker_plugin_product_sync_options';
    const SETTINGS_SECTION = 'paypecker_plugin_main';
    const AUTHENTICATION_SECTION = 'paypecker_auth';
    const AUTHENTICATION_TITLE = 'Authentication';
    const INVENTORY_SECTION = 'paypecker_inventory';
    const INVENTORY_TITLE = 'Inventory';
    const TEXT_ERROR = 'paypecker_error';
    const ERROR_CODE = 'paypecker_error_code';
    const PRODUCT_SYNC_SECTION = 'product-sync';
    const PRODUCT_SYNC_TITLE = 'Product Field Update Control';
    const OPTIONS_IDENTIFIER_PRODUCT_SYNC = 'paypecker_plugin_options_product_sync';

    /**
     * initialize the admin settings page
     */
    public function init()
    {
        add_options_page(
            self::SETTINGS_PAGE_TITLE,
            self::SETTINGS_MENU_TITLE,
            self::MANAGE_ADMIN_OPTIONS,
            self::PLUGIN_SLUG,
            [$this, 'optionPageFormSetup']
        );

        add_action('admin_init', [$this, 'registerSettingsOnAdminPage']);
    }


    /**
     * Add Settings link to the plugin entry in the plugins menu.
     *
     * @param array $links Plugin action links.
     *
     * @return array
     **/
    function action_links($links)
    {
        $settings_link = array(
            'settings' => '<a href="' . admin_url('options-general.php?page=paypecker_plugin') . '" title="' . __('View PayPecker Settings', 'woo-paypecker') . '">' . __('Settings', 'woo-paypecker') . '</a>',
        );

        return array_merge($settings_link, $links);
    }

    function optionPageFormSetup()
    {
        if (!current_user_can(self::MANAGE_ADMIN_OPTIONS)) {
            wp_die(__('You do not have sufficient permissions to access this page.', self::PLUGIN_SLUG));
        }

        $active_tab = 'general';

        if (isset($_GET['tab'])) {
            $active_tab = sanitize_key($_GET['tab']);
        }

?>
        <div class="wrap">
            <h2>PayPecker Settings</h2>

            <h2 class="nav-tab-wrapper">
                <a href="?page=<?php echo esc_attr(self::PLUGIN_SLUG) ?>&tab=general" class="nav-tab <?php echo esc_attr($active_tab) == 'general' ? 'nav-tab-active' : ''; ?>">General</a>
                <a href="?page=<?php echo esc_attr(self::PLUGIN_SLUG) ?>&tab=product_sync" class="nav-tab <?php echo esc_attr($active_tab) == 'product_sync' ? 'nav-tab-active' : ''; ?>">Product Sync</a>
            </h2>

            <form action="options.php" method="post">
                <?php
                if ($active_tab == 'product_sync') {
                    $this->displayProductSyncSettings();
                } else {
                    $this->displayGeneralSettings();
                }
                submit_button('Save Changes', 'primary');
                ?>
            </form>
        </div>
    <?php
    }

    function displayGeneralSettings()
    {
    ?>
        <?php
        settings_fields(self::OPTIONS_IDENTIFIER);
        do_settings_sections(self::PLUGIN_SLUG . '-general'); ?>
    <?php
    }

    function displayProductSyncSettings()
    {
    ?>
        <?php
        settings_fields(self::SYNC_OPTIONS_IDENTIFIER);
        do_settings_sections(self::PLUGIN_SLUG . '-product-sync'); ?>
<?php
    }

    function registerSettingsOnAdminPage()
    {
        $args = array(
            'type' => 'string',
            'sanitize_callback' => [$this, 'validateOptions'], 'default' => NULL
        );

        $argsProductSync = array(
            'type' => 'string',
            'sanitize_callback' => [$this, 'validateProductSyncOptions'], 'default' => NULL
        );

        register_setting(self::OPTIONS_IDENTIFIER, self::OPTIONS_IDENTIFIER, $args);

        register_setting(self::SYNC_OPTIONS_IDENTIFIER, self::SYNC_OPTIONS_IDENTIFIER, $argsProductSync);

        $generalTab = self::PLUGIN_SLUG . '-general';
        $productSyncTab = self::PLUGIN_SLUG . '-product-sync';

        add_settings_section(
            self::AUTHENTICATION_SECTION,
            self::AUTHENTICATION_TITLE,
            [$this, 'outputAuthTitleUiField'],
            $generalTab
        );


        add_settings_section(
            self::INVENTORY_SECTION,
            self::INVENTORY_TITLE,
            [$this, 'outputInventoryTitleUiField'],
            $generalTab
        );

        add_settings_field(
            'paypecker_plugin_token',
            'Enter API token',
            [$this, 'outputTokenUiField'],
            $generalTab,
            self::AUTHENTICATION_SECTION
        );

        add_settings_field(
            'paypecker_plugin_user_id',
            'Select a user that paypecker will act as on woocommerce',
            [$this, 'outputUserUiField'],
            $generalTab,
            self::AUTHENTICATION_SECTION
        );


        add_settings_section(
            self::PRODUCT_SYNC_SECTION,
            self::PRODUCT_SYNC_TITLE,
            [$this, 'outputProductSyncUiField'],
            $productSyncTab
        );

        add_settings_field(
            'paypecker_plugin_update_regular_price',
            'Regular Price',
            [$this, 'outputUpdateRegularPriceUiField'],
            $productSyncTab,
            self::PRODUCT_SYNC_SECTION
        );

        add_settings_field(
            'paypecker_plugin_update_sales_price',
            'Sales Price',
            [$this, 'outputUpdateSalesPriceUiField'],
            $productSyncTab,
            self::PRODUCT_SYNC_SECTION
        );

        add_settings_field(
            'paypecker_plugin_update_price',
            'Price',
            [$this, 'outputUpdatePriceUiField'],
            $productSyncTab,
            self::PRODUCT_SYNC_SECTION
        );

        add_settings_field(
            'paypecker_plugin_update_product_name',
            'Product Name',
            [$this, 'outputUpdateProductNameUiField'],
            $productSyncTab,
            self::PRODUCT_SYNC_SECTION
        );

        add_settings_field(
            'paypecker_plugin_update_description',
            'Description',
            [$this, 'outputUpdateDescriptionUiField'],
            $productSyncTab,
            self::PRODUCT_SYNC_SECTION
        );

        add_settings_field(
            'paypecker_plugin_update_categories',
            'Categories',
            [$this, 'outputUpdateCategoriesUiField'],
            $productSyncTab,
            self::PRODUCT_SYNC_SECTION
        );

        add_settings_field(
            'paypecker_plugin_update_short_description',
            'Short Description',
            [$this, 'outputUpdateShortDescriptionUiField'],
            $productSyncTab,
            self::PRODUCT_SYNC_SECTION
        );

        add_settings_field(
            'paypecker_plugin_update_stock_quantity',
            'Stock Quantity',
            [$this, 'outputUpdateStockQuantityUiField'],
            $productSyncTab,
            self::PRODUCT_SYNC_SECTION
        );

        add_settings_field(
            'paypecker_plugin_update_tag',
            'Tag',
            [$this, 'outputUpdateTagUiField'],
            $productSyncTab,
            self::PRODUCT_SYNC_SECTION
        );

        add_settings_field(
            'paypecker_plugin_update_visibility',
            'Show Product Instantly On Shopfloor',
            [$this, 'outputUpdateVisibilityUiField'],
            $generalTab,
            self::INVENTORY_SECTION
        );

        add_settings_field(
            'paypecker_plugin_update_inventory',
            'Update inventory quantity and manage inventory',
            [$this, 'outputUpdateInventoryUiField'],
            $generalTab,
            self::INVENTORY_SECTION
        );

        add_settings_field(
            'paypecker_plugin_update_images',
            'Images',
            [$this, 'outputUpdateImagesUiField'],
            $productSyncTab,
            self::PRODUCT_SYNC_SECTION
        );
    }


    function outputUpdateImagesUiField()
    {
        $options = get_option(self::SYNC_OPTIONS_IDENTIFIER);
        $updateImages = isset($options['updateImages']) ? $options['updateImages'] : false;
        echo  "<input id='updateImages' name='" . esc_attr(self::SYNC_OPTIONS_IDENTIFIER) . "[updateImages]' type='checkbox' value='1' " .  esc_attr(checked(1, $updateImages, false)) . "/>";
    }

    function outputUpdateVisibilityUiField()
    {
        $options = get_option(self::OPTIONS_IDENTIFIER);
        $show_catalog_visibility = isset($options['show_catalog_visibility']) ? $options['show_catalog_visibility'] : false;
        echo  "<input id='show_catalog_visibility' name='" . esc_attr(self::OPTIONS_IDENTIFIER) . "[show_catalog_visibility]' type='checkbox' value='1' " .  esc_attr(checked(1, $show_catalog_visibility, false)) . "/>";
    }

    function outputProductSyncUiField()
    {
        echo  '<p>Select the fields that paypecker will update during sync. Fields that are not selected will not be updated</p>';
    }

    function outputAuthTitleUiField()
    {
        echo  '<p>This section contain settings relating to security.</p>';
    }

    function outputUserUiField()
    {
        $options = get_option(self::OPTIONS_IDENTIFIER);
        $users = get_users();

        echo  "<select name='" . esc_attr(self::OPTIONS_IDENTIFIER) . "[user_id]'>";

        echo  '<option>select a user</option>';
        foreach ($users as $user) {
            echo  '<option value="' . esc_attr($user->ID) . '"' . esc_attr(selected($user->ID, isset($options['user_id']) ? $options['user_id'] : null, true)) . '>' . esc_attr($user->data->display_name) . '[' . esc_attr($user->data->user_email) . ']' . '</option>';
        }
        echo  "</select>";
    }

    function outputInventoryTitleUiField()
    {
        echo '<p>This section contain settings relating to inventory.</p>';
    }

    function outputTokenUiField()
    {
        $options = get_option(self::OPTIONS_IDENTIFIER);
        $token = isset($options['token']) ? $options['token'] : null;
        echo "<input id='name' name='" . esc_attr(self::OPTIONS_IDENTIFIER) . "[token]' class='large-text' type='text' value='" . esc_attr(esc_attr($token)) . "'/>";
    }

    function outputBackorderUiField()
    {
        $options = get_option(self::OPTIONS_IDENTIFIER);
        $backOrder = isset($options['allow_back_order']) ? $options['allow_back_order'] : false;
        echo "<input id='allow_back_order' name='" . esc_attr(self::OPTIONS_IDENTIFIER) . "[allow_back_order]' type='checkbox' value='1' " . esc_attr(checked(1, $backOrder, false)) . "/>";
    }

    function outputUpdateInventoryUiField()
    {
        $options = get_option(self::OPTIONS_IDENTIFIER);
        $update_inventory = isset($options['update_inventory']) ? $options['update_inventory'] : false;
        echo ("<input id='update_inventory' name='" . esc_attr(self::OPTIONS_IDENTIFIER) . "[update_inventory]' type='checkbox' value='1' " .  esc_attr(checked(1, $update_inventory, false)) . "/>");
    }

    function outputUpdateRegularPriceUiField()
    {
        $options = get_option(self::SYNC_OPTIONS_IDENTIFIER);
        $value = isset($options['sync_regular_price']) ? $options['sync_regular_price'] : false;
        echo "<input id='sync_regular_price' name='" . esc_attr(self::SYNC_OPTIONS_IDENTIFIER) . "[sync_regular_price]' type='checkbox' value='1' " .  esc_attr(checked(1, $value, false)) . "/>";
    }

    function outputUpdateSalesPriceUiField()
    {
        $options = get_option(self::SYNC_OPTIONS_IDENTIFIER);
        $value = isset($options['sync_sales_price']) ? $options['sync_sales_price'] : false;
        echo "<input id='sync_sales_price' name='" . esc_attr(self::SYNC_OPTIONS_IDENTIFIER) . "[sync_sales_price]' type='checkbox' value='1' " .  esc_attr(checked(1, $value, false)) . "/>";
    }

    function outputUpdatePriceUiField()
    {
        $options = get_option(self::SYNC_OPTIONS_IDENTIFIER);
        $value = isset($options['sync_price']) ? $options['sync_price'] : false;
        echo "<input id='sync_price' name='" . esc_attr(self::SYNC_OPTIONS_IDENTIFIER) . "[sync_price]' type='checkbox' value='1' " .  esc_attr(checked(1, $value, false)) . "/>";
    }

    function outputUpdateCategoriesUiField()
    {
        $options = get_option(self::SYNC_OPTIONS_IDENTIFIER);
        $value = isset($options['sync_categories']) ? $options['sync_categories'] : false;
        echo "<input id='sync_categories' name='" . esc_attr(self::SYNC_OPTIONS_IDENTIFIER) . "[sync_categories]' type='checkbox' value='1' " .  esc_attr(checked(1, $value, false)) . "/>";
    }

    function outputUpdateDescriptionUiField()
    {
        $options = get_option(self::SYNC_OPTIONS_IDENTIFIER);
        $value = isset($options['sync_description']) ? $options['sync_description'] : false;
        echo "<input id='sync_description' name='" . esc_attr(self::SYNC_OPTIONS_IDENTIFIER) . "[sync_description]' type='checkbox' value='1' " .  esc_attr(checked(1, $value, false)) . "/>";
    }

    function outputUpdateShortDescriptionUiField()
    {
        $options = get_option(self::SYNC_OPTIONS_IDENTIFIER);
        $value = isset($options['sync_short_description']) ? $options['sync_short_description'] : false;
        echo "<input id='sync_short_description' name='" . esc_attr(self::SYNC_OPTIONS_IDENTIFIER) . "[sync_short_description]' type='checkbox' value='1' " .  esc_attr(checked(1, $value, false)) . "/>";
    }

    function outputUpdateStockQuantityUiField()
    {
        $options = get_option(self::SYNC_OPTIONS_IDENTIFIER);
        $value = isset($options['sync_stock_quantity']) ? $options['sync_stock_quantity'] : false;
        echo "<input id='sync_stock_quantity' name='" . esc_attr(self::SYNC_OPTIONS_IDENTIFIER) . "[sync_stock_quantity]' type='checkbox' value='1' " .  esc_attr(checked(1, $value, false)) . "/>";
    }

    function outputUpdateProductNameUiField()
    {
        $options = get_option(self::SYNC_OPTIONS_IDENTIFIER);
        $value = isset($options['sync_product_name']) ? $options['sync_product_name'] : false;
        echo "<input id='sync_product_name' name='" . esc_attr(self::SYNC_OPTIONS_IDENTIFIER) . "[sync_product_name]' type='checkbox' value='1' " .  esc_attr(checked(1, $value, false)) . "/>";
    }

    function outputUpdateTagUiField()
    {
        $options = get_option(self::SYNC_OPTIONS_IDENTIFIER);
        $value = isset($options['sync_tag']) ? $options['sync_tag'] : false;
        echo "<input id='sync_tag' name='" . esc_attr(self::SYNC_OPTIONS_IDENTIFIER) . "[sync_tag]' type='checkbox' value='1' " .  esc_attr(checked(1, $value, false)) . "/>";
    }


    function validateOptions($input)
    {
        $previousOptions = sanitize_text_field(get_option(self::OPTIONS_IDENTIFIER));
        $token =  sanitize_text_field($input['token']);
        $valid = array();
        $validityCheck = preg_replace(
            '/[^a-zA-Z0-9]/',
            '',
            $token
        );

        if ($token !== $validityCheck || empty($token)) {
            add_settings_error(
                self::PLUGIN_SLUG,
                self::ERROR_CODE . '-token',
                'The value entered for api token is incorrect! Please use the token generated from paypecker site.',
                'error'
            );
            if (isset($previousOptions['token'])) {
                $valid['token'] = $previousOptions['token'];
            }
        } else {
            $valid['token'] = $token;
        }

        if ('select a user' === intval($input['user_id'])) {
            if (isset($previousOptions['user_id'])) {
                $input['user_id'] = intval($previousOptions['user_id']);
            }
            add_settings_error(
                self::PLUGIN_SLUG,
                self::ERROR_CODE . '-user',
                'The user you selected is invalid',
                'error'
            );
        }

        $valid['update_inventory'] = rest_sanitize_boolean($input['update_inventory']);
        $valid['allow_back_order'] =  rest_sanitize_boolean($input['allow_back_order']);
        $valid['show_catalog_visibility'] = rest_sanitize_boolean($input['show_catalog_visibility']);
        $valid['user_id'] =  intval($input['user_id']);
        return $valid;
    }

    public function validateProductSyncOptions($input)
    {
        $valid = array();
        $valid['sync_sku'] = rest_sanitize_boolean($input['sync_sku']);
        $valid['sync_regular_price'] = rest_sanitize_boolean($input['sync_regular_price']);
        $valid['sync_sales_price'] = rest_sanitize_boolean($input['sync_sales_price']);
        $valid['sync_price'] = rest_sanitize_boolean($input['sync_price']);
        $valid['sync_categories'] = rest_sanitize_boolean($input['sync_categories']);
        $valid['sync_description'] = rest_sanitize_boolean($input['sync_description']);
        $valid['sync_short_description'] = rest_sanitize_boolean($input['sync_short_description']);
        $valid['sync_stock_quantity'] = rest_sanitize_boolean($input['sync_stock_quantity']);
        $valid['sync_tag'] = rest_sanitize_boolean($input['sync_tag']);
        $valid['updateImages'] = rest_sanitize_boolean($input['updateImages']);
        $valid['sync_product_name'] = rest_sanitize_boolean($input['sync_product_name']);
        return $valid;
    }
}

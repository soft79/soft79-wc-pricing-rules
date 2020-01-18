<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class SOFT79_Meta_Box_Bulk_Rules {

    /**
     * Output the metabox
     */
    public static function output( $post ) {
        wp_nonce_field( 'soft79_save_data', 'soft79_meta_nonce' );

        $rule = new SOFT79_Bulk_Rule( $post );

        echo '<div id="j79_pricing_rules_meta" class="panel woocommerce_options_panel">';

        SOFT79_WCPR()->admin->render_admin_bulk_rules( $rule->get_bulk_rules() );
            
        // Quantity scope
        woocommerce_wp_select( array( 
            'id' => '_j79_quantity_scope', 
            'label' => __( 'Quantity scope', 'soft79-wc-pricing-rules' ), 
            'options' => array( 
                    "product" => "Single product (default)",
                    "global" => "Acumulative"
        ) ) );                
                
        //=============================
        // Product ids
        ?>
        <p class="form-field">
        <label><?php _e( 'Products', 'woocommerce' ); ?></label>
        <?php self::render_admin_product_selector( '_j79_product_ids', '_j79_product_ids', $rule->product_ids ); ?>
        <img class="help_tip" data-tip='<?php _e( 'Products which need to be in the cart to use this pricing rule.', 'soft79-wc-pricing-rules' ); ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
        </p>
        <?php

        //=============================
        // Exclude Product ids
        ?>
        <p class="form-field"><label><?php _e( 'Exclude products', 'woocommerce' ); ?></label>
        <?php self::render_admin_product_selector( '_j79_exclude_product_ids', '_j79_exclude_product_ids', $rule->exclude_product_ids ); ?>
        <img class="help_tip" data-tip='<?php 
            _e( 'Products which must not be in the cart to use this pricing rule.', 'soft79-wc-pricing-rules' );
        ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
        </p>
        <?php


        //=============================
        // Categories
        ?>
        <p class="form-field"><label for="_j79_product_categories"><?php _e( 'Product categories', 'woocommerce' ); ?></label>
        <select id="_j79_product_categories" name="_j79_product_categories[]" style="width: 50%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Any category', 'woocommerce' ); ?>">
            <?php
                $categories   = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );

                if ( $categories ) foreach ( $categories as $cat ) {
                    echo '<option value="' . esc_attr( $cat->term_id ) . '"' . selected( in_array( $cat->term_id, $rule->category_ids ), true, false ) . '>' . esc_html( $cat->name ) . '</option>';
                }
            ?>
        </select> <img class="help_tip" data-tip='<?php 
            _e( 'A product must be in this category for the pricing rule to remain valid.', 'soft79-wc-pricing-rules' ); 
        ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" /></p>
        <?php

        //=============================
        // Exclude Categories
        ?>
        <p class="form-field"><label for="_j79_exclude_product_categories"><?php _e( 'Exclude categories', 'woocommerce' ); ?></label>
        <select id="_j79_exclude_product_categories" name="_j79_exclude_product_categories[]" style="width: 50%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'No categories', 'woocommerce' ); ?>">
            <?php
                $categories   = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );

                if ( $categories ) foreach ( $categories as $cat ) {
                    echo '<option value="' . esc_attr( $cat->term_id ) . '"' . selected( in_array( $cat->term_id, $rule->exclude_category_ids ), true, false ) . '>' . esc_html( $cat->name ) . '</option>';
                }
            ?>
        </select> <img class="help_tip" data-tip='<?php 
            _e( 'Product must not be in this category for the pricing rule to remain valid.', 'soft79-wc-pricing-rules' ) 
        ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" /></p>
        <?php

        //=============================
        // User roles
        ?>
        <p class="form-field"><label for="_j79_user_roles"><?php _e( 'Allowed User Roles', 'soft79-wc-pricing-rules' ); ?></label>
        <select id="_j79_user_roles" name="_j79_user_roles[]" style="width: 50%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php _e( 'Any role', 'soft79-wc-pricing-rules' ); ?>">
            <?php            

                $available_customer_roles = array_reverse( get_editable_roles() );
                foreach ( $available_customer_roles as $role_id => $role ) {
                    $role_name = translate_user_role($role['name'] );
    
                    echo '<option value="' . esc_attr( $role_id ) . '"'
                    . selected( in_array( $role_id, $rule->user_roles ), true, false ) . '>'
                    . esc_html( $role_name ) . '</option>';
                }
            ?>
        </select> <img class="help_tip" data-tip='<?php 
            _e( 'The pricing rule only applies to these User Roles.', 'soft79-wc-pricing-rules' ); 
        ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" /></p>
        <?php    

        //=============================
        // Excluded user roles
        ?>
        <p class="form-field"><label for="_j79_exclude_user_roles"><?php _e( 'Disallowed User Roles', 'soft79-wc-pricing-rules' ); ?></label>
        <select id="_j79_exclude_user_roles" name="_j79_exclude_user_roles[]" style="width: 50%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php _e( 'Any role', 'soft79-wc-pricing-rules' ); ?>">
            <?php            

                foreach ( $available_customer_roles as $role_id => $role ) {
                    $role_name = translate_user_role($role['name'] );
    
                    echo '<option value="' . esc_attr( $role_id ) . '"'
                    . selected( in_array( $role_id, $rule->exclude_user_roles ), true, false ) . '>'
                    . esc_html( $role_name ) . '</option>';
                }
            ?>
        </select> <img class="help_tip" data-tip='<?php 
            _e( 'These User Roles will be specifically excluded from this pricing rule.', 'soft79-wc-pricing-rules' ); 
        ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" /></p>
        <?php
        
        // Quantity scope
        woocommerce_wp_select( array( 
            'id' => '_j79_display_on_prod_page', 
            'label' => __( 'Display on product page', 'soft79-wc-pricing-rules' ), 
            'value' => empty( $rule->display_on_prod_page ) ? "description,table" : $rule->display_on_prod_page, 
            'options' => array( 
                    "description,table" => "Description and table",
                    "description" => "Description",
                    "table" => "Table",
                    "nothing" => "Nothing"
        ) ) );
        
                
        echo '</div>';


    }

//VERSION

    /**
     * Check whether WooCommerce version is greater or equal than $req_version
     * @param string @req_version The version to compare to
     * @return bool true if WooCommerce is at least the given version
     */
    public static function check_woocommerce_version( $req_version ) {
        return version_compare( self::get_woocommerce_version(), $req_version, '>=' );
    }    

    private static $wc_version = null;
    
    /**
     * Get the WooCommerce version number
     * @return string|bool WC Version number or false if WC not detected
     */
    public static function get_woocommerce_version() {
        if ( isset( self::$wc_version ) ) {
            return self::$wc_version;
        }

        if ( defined( 'WC_VERSION' ) ) {
            return self::$wc_version = WC_VERSION;
        }

        // If get_plugins() isn't available, require it
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }        
        // Create the plugins folder and file variables
        $plugin_folder = get_plugins( '/woocommerce' );
        $plugin_file = 'woocommerce.php';
        
        // If the plugin version number is set, return it 
        if ( isset( $plugin_folder[$plugin_file]['Version'] ) ) {
            return self::$wc_version = $plugin_folder[$plugin_file]['Version'];
        }

        return self::$wc_version = false; // Not found
    }

// HTML Output helper functions

    /**
     * Renders a product selection <input>. Will use either select2 v4 (WC3.0+) select2 v3 (WC2.3+) or chosen (< WC2.3)
     * @param string $dom_id 
     * @param string $field_name 
     * @param array $selected_ids Array of integers
     * @param string|null $placeholder 
     * @return void
     */
    public static function render_admin_product_selector( $dom_id, $field_name, $selected_ids, $placeholder = null ) {
        $product_key_values = array();
        foreach ( $selected_ids as $product_id ) {
            $product = wc_get_product( $product_id );
            if ( is_object( $product ) ) {
                $product_key_values[ esc_attr( $product_id ) ] = wp_kses_post( $product->get_formatted_name() );
            }
        }

        if ( $placeholder === null ) $placeholder = __( 'Search for a product&hellip;', 'woocommerce' );

        //In WooCommerce version 2.3.0 chosen was replaced by select2
        //In WooCommerce version 3.0 select2 v3 was replaced by select2 v4
        if ( self::check_woocommerce_version('3.0') ) {
            self::render_admin_select2_v4_product_selector( $dom_id, $field_name, $product_key_values, $placeholder );
        } elseif ( self::check_woocommerce_version('2.3.0') ) {
            self::render_admin_select2_product_selector( $dom_id, $field_name, $product_key_values, $placeholder );
        } else {
            self::render_admin_chosen_product_selector( $dom_id, $field_name, $product_key_values, $placeholder );
        }
    }


    /**
     * Renders a product selection <input>. 
     * Chosen (Legacy)
     * @param string $dom_id 
     * @param string $field_name 
     * @param array $selected_ids Array of integers
     * @param string|null $placeholder 
     */    
    private static function render_admin_chosen_product_selector( $dom_id, $field_name, $selected_keys_and_values, $placeholder ) {
        // $selected_keys_and_values must be an array of [ id => name ]

        echo '<select id="' . esc_attr( $dom_id ) . '" name="' . esc_attr( $field_name ) . '[]" class="ajax_chosen_select_products_and_variations" multiple="multiple" data-placeholder="' . esc_attr( $placeholder ) . '">';
        foreach ( $selected_keys_and_values as $product_id => $product_name ) {
            echo '<option value="' . $product_id . '" selected="selected">' . $product_name . '</option>';
        }
        echo '</select>';
    }

    /**
     * Renders a product selection <input>. 
     * Select2 version 3 (Since WC 2.3.0)
     * @param string $dom_id 
     * @param string $field_name 
     * @param array $selected_ids Array of integers
     * @param string|null $placeholder 
     */    
    private static function render_admin_select2_product_selector( $dom_id, $field_name, $selected_keys_and_values, $placeholder ) {
        // $selected_keys_and_values must be an array of [ id => name ]

        $json_encoded = esc_attr( json_encode( $selected_keys_and_values ) );
        echo '<input type="hidden" class="wc-product-search" data-multiple="true" style="width: 50%;" name="' 
        . esc_attr( $field_name ) . '" data-placeholder="' 
        . esc_attr( $placeholder ) . '" data-action="woocommerce_json_search_products_and_variations" data-selected="' 
        . $json_encoded . '" value="' . implode( ',', array_keys( $selected_keys_and_values ) ) . '" />';

    }  

    /**
     * Renders a product selection <input>. 
     * Select2 version 4 (Since WC 3.0)
     * @param string $dom_id 
     * @param string $field_name 
     * @param string $selected_keys_and_values 
     * @param string $placeholder 
     */
    private static function render_admin_select2_v4_product_selector( $dom_id, $field_name, $selected_keys_and_values, $placeholder ) {
        // $selected_keys_and_values must be an array of [ id => name ]

        $json_encoded = esc_attr( json_encode( $selected_keys_and_values ) );

        echo '<select id="'. esc_attr( $dom_id ) .'" class="wc-product-search" name="'
        . esc_attr( $field_name ) . '[]" multiple="multiple" style="width: 50%;" data-placeholder="'
        . esc_attr( $placeholder ) . '" data-action="woocommerce_json_search_products_and_variations">';

        foreach ( $selected_keys_and_values as $product_id => $product_name ) {
            echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product_name ) . '</option>';
        }

        echo '</select>';
    }   
    
    private static function html_select( $name, $values, $selected = null, $attribs = array() ) {
        echo "<select name='" . $name . "'";
        foreach($attribs as $key => $desc ) {
            printf (" %s='%s'", $key, $desc );
        }
        echo ">";
        
        foreach ( $values as $key => $desc ) {
            echo '<option value="' . esc_attr( $key ) . '"' . selected( $key == $selected ) . '>' . esc_html( $desc ) . '</option>';
        }
        echo "</select>";
    }
    
    private static function html_help_icon( $text ) {
        $img_url = esc_url( WC()->plugin_url() ) . "/assets/images/help.png";
        echo "<img class='help_tip' data-tip='" . esc_attr( $text ) . "' src='" . $img_url . "' height='16' width='16' />";        
    }    
    

//End of html helpers
}

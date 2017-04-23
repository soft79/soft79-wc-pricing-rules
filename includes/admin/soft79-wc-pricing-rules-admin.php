<?php

defined('ABSPATH') or die();

final class SOFT79_Bulk_Pricing_Admin {
    public function __construct() {
        add_action('init', array( &$this, 'controller_init' ));
    }
    
    protected static $_instance = null;
    /**
     *  Get the single instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }    
    
    public function controller_init() {
        if ( ! class_exists('SOFT79_WC_Pricing_Rules_Plugin') || ! class_exists('WooCommerce') ) {
            return;
        }

        //Admin hooks
        add_action( 'admin_enqueue_scripts', array( &$this, 'action_admin_enqueue_scripts' ) );
        add_action( 'save_post', array( &$this, 'action_save_post' ), 10, 2 );
        add_action( 'woocommerce_save_product_variation', array( &$this, 'action_save_product_variation' ), 10, 2 );

        add_action( 'woocommerce_product_options_general_product_data', array( &$this, 'action_admin_show_price_rules' ) );
        //TODO: Bulk prices for variations: add_action( 'woocommerce_product_after_variable_attributes', array( &$this, 'action_admin_show_variation_price_rules' ), 10, 3 );
        
        //Admin page
        add_action( 'admin_init', array( &$this, 'action_admin_init' ) );    
        add_action('admin_menu', array( &$this, 'action_admin_menu' ) );
        
        
        //Custom post type meta box
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 30 );
        add_action( 'save_post', array( $this, 'save_meta_boxes' ), 1, 2 );
        //Use WC's scripts on the custom post page
        add_filter( 'woocommerce_screen_ids', array( $this, 'filter_woocommerce_screen_ids' ) );
    }

    public function action_admin_menu() {
        add_options_page( __( 'Price Rules', 'soft79-wc-pricing-rules' ), __( 'Price Rules', 'soft79-wc-pricing-rules' ), 'manage_options', 'j79-price-rules', array( &$this, 'action_admin_config_page' ) ); 
    }
    
    public function action_admin_init() {
        $this->init_settings();
    } 

    public function filter_woocommerce_screen_ids( $screen_ids ) {
            $screen_ids[] = 'j79_wc_price_rule';
            return $screen_ids;
    }
    
    /**
     * Checkbox field fallback.
     *
     * @param  array $args Field arguments.
     *
     * @return string      Checkbox field.
     */
    public function checkbox_element_callback( $args ) {
        $menu = $args['menu'];
        $id = $args['id'];
    
        if ( isset( SOFT79_WC_Pricing_Rules_Plugin()->options[$id] ) ) {
            $current = SOFT79_WC_Pricing_Rules_Plugin()->options[$id];
        } else {
            $current = isset( $args['default'] ) ? $args['default'] : '';
        }
    
        $html = sprintf( '<input type="checkbox" id="%1$s" name="%2$s[%1$s]" value="1"%3$s />', $id, $menu, checked( 1, $current, false ) );
    
        if ( isset( $args['description'] ) ) {
            $html .= sprintf( '<p class="description">%s</p>', $args['description'] );
        }
    
        echo $html;
    }  
    
    /**
     * Displays a radio settings field
     *
     * @param array   $args settings field args
     */
    public function radio_element_callback( $args ) {
        $menu = $args['menu'];
        $id = $args['id'];
    
    
        if ( isset( SOFT79_WC_Pricing_Rules_Plugin()->options[$id] ) ) {
            $current = SOFT79_WC_Pricing_Rules_Plugin()->options[$id];
        } else {
            $current = isset( $args['default'] ) ? $args['default'] : '';
        }

        $html = '';
        foreach ( $args['options'] as $key => $label ) {
            $html .= sprintf( '<input type="radio" class="radio" id="%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s"%4$s />', $menu, $id, $key, checked( $current, $key, false ) );
            $html .= sprintf( '<label for="%1$s[%2$s][%3$s]"> %4$s</label><br>', $menu, $id, $key, $label);
        }
        
        // Displays option description.
        if ( isset( $args['description'] ) ) {
            $html .= sprintf( '<p class="description">%s</p>', $args['description'] );
        }

        echo $html;
    }    

    public function section_null_callback() {
    
    }    
    
    public function init_settings() {
        $option = 'j79_price_rules_settings';
    
        // Create option in wp_options.
        if ( false == get_option( $option ) ) {
            add_option( $option );
        }
        
        $options = SOFT79_WC_Pricing_Rules_Plugin()->options;
        
        // Section.
        add_settings_section(
            'display',
            __( 'Display settings', 'soft79-wc-pricing-rules' ),
            array( &$this, 'section_null_callback' ),
            $option
        );


        add_settings_field(
            'show_min_max_price',
            __( 'Show min-max price range on category page', 'soft79-wc-pricing-rules' ),
            array( &$this, 'checkbox_element_callback' ),
            $option,
            'display',
            array(
                'menu'            => $option,
                'id'            => 'show_min_max_price',
                'description'    => 
                    __( 'If a discount is available for the product, display the min-max price range on the category pages.', 'soft79-wc-pricing-rules' )
                    . " " . __( 'Example:', 'soft79-wc-pricing-rules' ) . " " . wc_price(8) . " - " . wc_price(10)
            )
        );        
        
         add_settings_field(
            'show_min_max_price_singular',
            __( 'Show min-max price range on product detail page', 'soft79-wc-pricing-rules' ),
            array( &$this, 'checkbox_element_callback' ),
            $option,
            'display',
            array(
                'menu'            => $option,
                'id'            => 'show_min_max_price_singular',
                'description'    => 
                    __( 'If a discount is available for the product, display the min-max price range on the product detail pages.', 'soft79-wc-pricing-rules' )
                    . " " . __( 'Example:', 'soft79-wc-pricing-rules' ) . " " . wc_price(8) . " - " . wc_price(10)
            )
        );         

         add_settings_field(
            'show_cart_itemprice_as_from_to',
            __( 'Show from-to single item price on cart page', 'soft79-wc-pricing-rules' ),
            array( &$this, 'checkbox_element_callback' ),
            $option,
            'display',
            array(
                'menu'            => $option,
                'id'            => 'show_cart_itemprice_as_from_to',
                'description'    => 
                    __( 'If a discount has been applied to the product, display the from-to single product price on the cart page.', 'soft79-wc-pricing-rules' )
                    . " " . __( 'Example:', 'soft79-wc-pricing-rules' ) . " <del>" . wc_price(10) . "</del> <ins>" . wc_price(8) . "</ins>"
            )
        );

         add_settings_field(
            'show_cart_subtotal_as_from_to',
            __( 'Show from-to item subtotal on cart page', 'soft79-wc-pricing-rules' ),
            array( &$this, 'checkbox_element_callback' ),
            $option,
            'display',
            array(
                'menu'            => $option,
                'id'            => 'show_cart_subtotal_as_from_to',
                'description'    => 
                    __( 'If a discount has been applied to the product, display the from-to subtotal on the cart page.', 'soft79-wc-pricing-rules' )
                    . " " . __( 'Example:', 'soft79-wc-pricing-rules' ) . " <del>" . wc_price(100) . "</del> <ins>" . wc_price(80) . "</ins>"
            )
        );            

         add_settings_field(
            'hide_rules_not_in_stock',
            __( 'Hide discounts if not enough stock', 'soft79-wc-pricing-rules' ),
            array( &$this, 'checkbox_element_callback' ),
            $option,
            'display',
            array(
                'menu'            => $option,
                'id'            => 'hide_rules_not_in_stock',
                'description'    => 
                    __( 'Prevent discounts to be displayed if the amount of items in stock is not sufficient for the discount to be applied.', 'soft79-wc-pricing-rules' )
            )
        );    

        //Rules
        if ( SOFT79_WC_Pricing_Rules_Plugin()->controller->is_pro() ) {
            add_settings_section(
                'rules',
                __( 'Rule settings', 'soft79-wc-pricing-rules' ),
                array( &$this, 'section_null_callback' ),
                $option
            );

            add_settings_field(
                'rule_choice',
                __( 'Rule selection', 'soft79-wc-pricing-rules' ),
                array( &$this, 'radio_element_callback' ),
                $option,
                'rules',
                array(
                    'menu'            => $option,
                    'id'            => 'rule_choice',
                    'options'         => array(
                        'first'    => __( 'Apply first applicable rule' , 'soft79-wc-pricing-rules' ),
                        'best'    => __( 'Apply the rule that offers highest discount' , 'soft79-wc-pricing-rules' ),
    //                    'accumulate'    => __( 'Accumulate discounts of all applicable rules' , 'soft79-wc-pricing-rules' ),
                    ),
                    'description'    => 
                        __( 'Decide what rule to apply if multiple rules can be applied to an item in the cart.', 'soft79-wc-pricing-rules' )
                )
            );
        
        }
        
        

        // Register settings.
        register_setting( $option, $option, array( &$this, 'validate_options' ) );
    }    
    
    /**
     * Validate options.
     *
     * @param  array $input options to valid.
     *
     * @return array        validated options.
     */
    public function validate_options( $input ) {
        // Create our array for storing the validated options.
        $output = array();
    
        // Loop through each of the incoming options.
        foreach ( $input as $key => $value ) {
    
            // Check to see if the current option has a value. If so, process it.
            if ( isset( $input[$key] ) ) {    
                // Strip all HTML and PHP tags and properly handle quoted strings.
                $output[$key] = strip_tags( stripslashes( $input[$key] ) );
            }
        }
        
        //checkboxes must have value 0 if not checked
        $cbs = array( 
            'show_min_max_price', 
            'show_min_max_price_singular', 
            'show_cart_itemprice_as_from_to', 
            'hide_rules_not_in_stock'
        );
        
        foreach ( $cbs as $cb ) {
            if ( ! isset( $output[$cb] ) ) {
                $output[$cb] = '0';
            }
        }
        
        return $output;
    }

    public function action_admin_config_page() {
?>
        <h2><?php _e( 'Pricing Rules Settings', 'soft79-wc-pricing-rules' ); ?></h2>
        <form method="post" action="options.php"> 
        <?php 
        settings_fields( 'j79_price_rules_settings' );
        do_settings_sections( 'j79_price_rules_settings' );
        ?>
        <?php submit_button(); ?>
        </form>
        <h3><?php _e( 'Support', 'soft79-wc-pricing-rules' ); ?></h3>
        <p><?php _e( 'We are currently working on adding new functionality to this plugin.', 'soft79-wc-pricing-rules' ); ?></p>
        <p><?php 
            printf( 
                __( 'Please check out %s for feature requests, support or for the latest news.', 'soft79-wc-pricing-rules' ), 
                '<a href="http://www.soft79.nl" target="_blank">www.soft79.nl</a>'
        ); ?></p>
<?php
        
    }

    /**
     * Check if we're saving, the trigger an action based on the post type
     *
     * @param  int $post_id
     * @param  object $post
     */
    public function save_meta_boxes( $post_id, $post ) {
        // $post_id and $post are required
        if ( empty( $post_id ) || empty( $post )  ) {
            return;
        }
        
        if ( ! $post->post_type == "j79_wc_price_rule" ) {
            return;
        }

        // Dont' save meta boxes for revisions or autosaves
        if ( defined( 'DOING_AUTOSAVE' ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
            return;
        }

        // Check the nonce
        if ( empty( $_POST['soft79_meta_nonce'] ) || ! wp_verify_nonce( $_POST['soft79_meta_nonce'], 'soft79_save_data' ) ) {
            return;
        }

        // Check the post being saved == the $post_id to prevent triggering this call for other save_post events
        if ( empty( $_POST['post_ID'] ) || $_POST['post_ID'] != $post_id ) {
            return;
        }

        // Check user has permission to edit
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        SOFT79_Meta_Box_Bulk_Rules::save( $post_id, $post);

    }    

    
    public function add_meta_boxes() {
            
        //Bulk rule meta box
        add_meta_box( 'soft79-rule-set', __( 'Rule set', 'soft79-wc-pricing-rules' ), 'SOFT79_Meta_Box_Bulk_Rules::output', 'j79_wc_price_rule', 'normal', 'high' );
            
    }
    
    public function action_admin_enqueue_scripts() {
        wp_enqueue_style( 'soft79_bulk_admin_styles', SOFT79_WC_Pricing_Rules_Plugin()->plugin_url() . '/assets/css/admin.css', array(), "test6" );
    }
    
    /**
     *  Sort an array of arrays by field.
     *  NOTE: No return value, will sort the array in-place.
     */
    private static function sort_array_by_field(&$array, $subfield)
    {
        $sortarray = array();
        foreach ($array as $key => $row)
        {
            $sortarray[$key] = $row[$subfield];
        }

        array_multisort($sortarray, SORT_ASC, $array);
    }
    
    /**
     *  convert contents of the form fields ( e.g. $_POST['rules'] ) to a rule array
     *  If no valid fields found, returns null.
     */
    public function get_bulk_rules_from_form( $form_bulk_rules ) {
        $bulk_rules = array();
        //Placing of the values in array (safe)
        foreach( $form_bulk_rules as $form_rule ) {
            $qty_from = intval($form_rule['qty_from']);
            $qty_to = intval($form_rule['qty_to']);
            $price = $form_rule['price'];
            
            //Parse percentage or price
            if ( strstr( $price, '%' ) ) {
                $price = wc_format_decimal( trim( str_replace( '%', '', $price ) ) ) . "%";
            } else {
                $price = wc_format_decimal( trim( $price ) );
            }                
            
            if ( ($qty_from > 0 || $qty_to > 0 ) && $price !== '' ) {
                $bulk_rules[] = array( 
                    "qty_from" =>  $qty_from,
                    "qty_to" => $qty_to,
                    "price" => $price,
                );
            }
        }
        
        //Order the array, keys will be 0...n-1
        self::sort_array_by_field( $bulk_rules, 'qty_from' );
        
        if (count($bulk_rules) == 0) {
            $bulk_rules = null;
        }

        return $bulk_rules;
        
    }
    
    /**
     *  Save the bulk rules as filled on the product edit page
     */
    public function action_save_post( $post_id, $post ) {
        
        //TODO: We could handle the custom post type as well 
        if ( in_array( $post->post_type, array( 'product' ) ) && isset( $_POST['_j79_rules'] ) ) {
            $bulk_rules = $this->get_bulk_rules_from_form( $_POST['_j79_rules'] );
            //Remove the postmeta if no rules specified
            if ( count( $bulk_rules ) == 0 ) {
                delete_post_meta( $post_id, '_j79_bulk_rules' );
            } else {
                update_post_meta( $post_id, '_j79_bulk_rules', $bulk_rules );
            }
        }
    }

    private function html_select( $name, $values, $selected = null, $attribs = array() ) {
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
    
    private function html_help_icon( $text ) {
        $img_url = esc_url( WC()->plugin_url() ) . "/assets/images/help.png";
        echo "<img class='help_tip' data-tip='" . esc_attr( $text ) . "' src='" . $img_url . "' height='16' width='16' />";
        
    }
    
    private function render_admin_single_bulk_row( $idx, $rule = null, $variation_loop = null ) {

        //If variation_loop is not null, it is used for a variable product, so we add variable_ prefix and an indexer!
        if ( $variation_loop === null ) {
            $field_name = "_j79_rules";
        } else {
            $field_name = '_j79_variable_rules[' . absint( $variation_loop ) . ']';
        }

        $qty_from = esc_attr( isset( $rule['qty_from'] ) && $rule['qty_from'] ? $rule['qty_from'] : '' );         
        $qty_to = esc_attr( isset( $rule['qty_to'] ) && $rule['qty_to'] ? $rule['qty_to'] : '' );         
        $price = esc_attr( isset( $rule['price'] ) ? $rule['price'] : '' ); 

        ?><p class="form-field soft79_bulk_row">
            <span class="wrap">
        
                <input placeholder="<?php 
                    esc_attr_e( 'Qty', 'woocommerce' ); 
                ?>" class="input-text wc_input_decimal" size="6" type="text" name="<?php echo $field_name; ?>[<?php echo $idx; ?>][qty_from]" value="<?php
                    echo $qty_from; 
                ?>" />
                
                <input placeholder="<?php 
                    esc_attr_e( 'Qty', 'woocommerce' ); 
                ?>" class="input-text wc_input_decimal" size="6" type="text" name="<?php echo $field_name; ?>[<?php echo $idx; ?>][qty_to]" value="<?php
                    echo $qty_to; 
                ?>" />
                

                <input placeholder="<?php 
                    esc_attr_e( 'Price', 'woocommerce' ); 
                ?>" class="input-text wc_input_price last" size="6" type="text" name="<?php echo $field_name; ?>[<?php echo $idx; ?>][price]" value="<?php
                    echo wc_format_localized_price( $price );
                ?>" />
                
            </span>
            
        </p><?php
    }
    
    function render_admin_bulk_rules( $bulk_rules, $variation_loop = null  ) {

        $var_idx = $variation_loop === null ? '' : '[' . absint( $variation_loop ) . ']';
        
        echo "<input type='hidden' name='_j79_rule_type" . $var_idx . "' value='bulk'>";
        echo "<div class='soft79_bulk_rules'>";
        echo "<p class='form-field'><label>" . __( 'Pricing Rules', 'soft79-wc-pricing-rules' ) . "</label>";
        echo "<span class='wrap'>";
        
        echo "<span>" . __( 'From', 'soft79-wc-pricing-rules' );
        self::html_help_icon( __("Minimal amount of products for this rule.", 'soft79-wc-pricing-rules' ) );        
        echo "</span>";
        
        echo "<span>" . __( 'To', 'soft79-wc-pricing-rules' ) . " " . __( '(Optional)', 'soft79-wc-pricing-rules' );
        self::html_help_icon( __("Maximum amount of products for this rule. If the From and To price are the same value, this will be the price if you buy by the 'pack'.", 'soft79-wc-pricing-rules' ) );
        echo "</span>";
        
        
        echo "<span class='last'>" . __( 'Price', 'woocommerce' ) . " (" . get_woocommerce_currency_symbol() . ", %)";;
        self::html_help_icon( __("Enter a price or percentage (%). If a negative value is supplied, this value is discounted from the original price. Examples: 50%, -10%, -0.10 4.99", 'soft79-wc-pricing-rules' ) );        
        echo "</span>";
        
        echo "</span></p>";

        if ($bulk_rules !== null) {
            //The filled fields
            $idx = 0;
            foreach ( $bulk_rules as $r ) {    
                $this->render_admin_single_bulk_row( $idx, $r, $variation_loop );
                $idx++;
            }
        }
        
        //Extra empty fields
        $offset = $idx;
        for ( $idx = 0; $idx < 5; $idx++ ) {
            $this->render_admin_single_bulk_row( $idx + $offset, null, $variation_loop );
        }            
        echo "</div>";
        
    }

    public function render_variation_bulk_rules( $bulk_rules, $loop ) {
    	echo "<div>";
		echo '<p class="form-row form-row-full">';

        $this->render_admin_bulk_rules( $bulk_rules, $loop );

		echo '</p>';
    	echo "</div>";

    }

    function action_admin_show_variation_price_rules( $loop, $variation_data, $variation ) {
        global $thepostid;
        $variation_id = $variation->ID;

        //var_dump($variation); die();
        $rule = new SOFT79_Bulk_Rule( $variation_id );
        $this->render_variation_bulk_rules( $rule->bulk_rules, $loop );
    }

    public function action_save_product_variation( $variation_id, $index ) {
        if ( ! $_POST['_j79_variable_rules'] ) {
            return;
        }

        $rules = $_POST['_j79_variable_rules'];
        $rules = $rules[ $index ];
        
        $bulk_rules = $this->get_bulk_rules_from_form( $rules );
        if ( count( $bulk_rules ) == 0 ) {
            delete_post_meta( $variation_id, '_j79_bulk_rules' );
        } else {
            update_post_meta( $variation_id, '_j79_bulk_rules', $bulk_rules );
        }
    }    
    
    public function save_admin_bulk_rules( $post_id, $post ) {
    // Save
        
        if ( isset( $_POST['_j79_rule_type'] ) ) {
            update_post_meta( $post_id, '_j79_rule_type', wc_clean( $_POST['_j79_rule_type'] ) );
        }
        if ( isset( $_POST['_j79_quantity_scope'] ) ) {
            update_post_meta( $post_id, '_j79_quantity_scope', wc_clean( $_POST['_j79_quantity_scope'] ) );
        }
        if ( isset( $_POST['_j79_display_on_prod_page'] ) ) {
            update_post_meta( $post_id, '_j79_display_on_prod_page', wc_clean( $_POST['_j79_display_on_prod_page'] ) );
        }
        
        //Remove the postmeta if no rules specified
        if ( isset($_POST['_j79_rules']) ) {
            $bulk_rules = $this->get_bulk_rules_from_form( $_POST['_j79_rules'] );
            if ( count( $bulk_rules ) == 0 ) {
                delete_post_meta( $post_id, '_j79_bulk_rules' );
            } else {
                update_post_meta( $post_id, '_j79_bulk_rules', $bulk_rules );
            }
        }
    }
    

    
    function action_admin_show_price_rules() {
        global $thepostid;
        
        $rule = new SOFT79_Bulk_Rule( $thepostid );
        $this->render_admin_bulk_rules( $rule->bulk_rules );
    }    
    

    
}

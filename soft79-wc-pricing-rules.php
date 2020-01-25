<?php
/**
 * Plugin Name: SOFT79 Pricing Rules for Woocommerce
 * Plugin URI: http://www.soft79.nl
 * Description: Pricing rules for WooCommerce
 * Version: 1.4.3
 * WC requires at least: 3.0.0
 * WC tested up to: 3.9.0
 * Author: Soft79
 * License: GPL2
 */

defined('ABSPATH') or die();

//Load text domain
if ( ! function_exists( 'soft79_wc_pricing_rules_load_plugin_textdomain' ) ) {
    function soft79_wc_pricing_rules_load_plugin_textdomain() {
        load_plugin_textdomain('soft79-wc-pricing-rules', false, basename(dirname(__FILE__)) . '/languages/' );
    }
    add_action('plugins_loaded', 'soft79_wc_pricing_rules_load_plugin_textdomain');
}

//The main class
if ( ! class_exists( 'SOFT79_WC_Pricing_Rules_Plugin' ) ) {

    require_once('includes/soft79-rule-helpers.php');
    require_once('includes/soft79-rule-controller.php');
    require_once('includes/soft79-bulk-acumulator.php');
    require_once('includes/abstract-soft79-rule.php');
    require_once('includes/soft79-bulk-rule.php');
    require_once('includes/admin/soft79-wc-pricing-rules-admin.php');
    require_once('includes/admin/class-meta-box-bulk-rules.php');

    include_once('includes/soft79-rule-controller-pro.php');

    final class SOFT79_WC_Pricing_Rules_Plugin {
        public $version = '1.4.3';

        public $admin = null;

        public $controller = null;

        public $options = array(
        //display
            'show_min_max_price' => true,
            'show_min_max_price_singular' => false,
            'show_cart_itemprice_as_from_to' => true, //Display cart single item price as from_to if bulk discount applies
            'show_cart_subtotal_as_from_to' => false, //Display cart item subtotal as from_to if bulk discount applies
            'pack_price_format' => 0, //0 is unit price, 1 = total price, 2 = both
            'hide_rules_not_in_stock' => true, //Don't load/display rules that can't be used because not enough product stock

        //rules
            'rule_choice' => 'best', //best or first


        //misc
            'db_version' => 0 //future: usage for auto database update
        );

        public function __construct() {

            //error_log("NEW INSTANCE");
            $this->read_options();
            add_action('init', array( $this, 'controller_init' ));
            add_action('wpml_loaded', array( $this, 'action_wpml_loaded' ) );
            if(is_admin()){
                $this->admin = new SOFT79_Bulk_Pricing_Admin();
            }

        }

        public function read_options() {
            //Start with default options, overwrite what is read from db
            $db_options = get_option( 'j79_price_rules_settings', array() );
            if ( is_array( $db_options ) ) {
                foreach( $db_options as $k => $v ) {
                    $this->options[$k] = $v;
                }
            }

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
            if ( ! class_exists('WooCommerce') ) {
                return;
            }

            if ( class_exists( 'SOFT79_Rule_Controller_PRO' ) ) {
                $this->controller = new SOFT79_Rule_Controller_PRO( $this->options );
            } else {
                $this->controller = new SOFT79_Rule_Controller( $this->options );
            }

            //Frontend hooks
            add_action( 'wp_enqueue_scripts', array( $this, 'action_enqueue_scripts' ) );

            //CALCULATION: Recalculate price of products in cart
            add_action('woocommerce_cart_loaded_from_session', array( $this, 'action_inject_prices'), 0, 0);
            add_action('woocommerce_before_calculate_totals', array( $this, 'action_inject_prices'), 10, 0);

            //DISPLAY: Show table on template
            add_action('woocommerce_single_product_summary',  array( $this, 'action_woocommerce_single_product_summary'), 11 ); //11 is direct na prijs, zie wc-template-hooks.php

            //DISPLAY: Show min ... max price of the product
            add_filter('woocommerce_get_price_html', array( $this, 'action_woocommerce_get_price_html' ), 10, 2);

            //For variation update
            add_filter('woocommerce_get_variation_price_html', array( $this, 'action_woocommerce_get_variation_price_html' ), 10, 2);
            add_filter('woocommerce_available_variation', array( $this, 'action_woocommerce_available_variation' ), 10, 3);

            //DISPLAY: Overwrite cart single item price
            if ( $this->options['show_cart_itemprice_as_from_to'] ) {
                add_filter( 'woocommerce_cart_item_price', array( $this, 'filter_woocommerce_cart_item_price' ), 10, 2 );
            }

            //DISPLAY: Overwrite cart single item subtotal
            if ( $this->options['show_cart_subtotal_as_from_to'] ) {
                add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'filter_item_subtotal' ), 10, 2 );
            }
        }

        public function get_version() {
            return $this->version;
        }

        /**
         * Get the plugin url.
         * @return string
         */
        public function plugin_url() {
            return untrailingslashit( plugins_url( '/', __FILE__ ) );
        }

        /**
         * Get the plugin path.
         * @return string
         */
        public function plugin_path() {
            return untrailingslashit( trailingslashit( dirname( __FILE__ ) ) );
        }

        /**
         * WPML Compatibility
         * @since 1.1.4
         */
        public function action_wpml_loaded() {
            include_once( 'includes/soft79-wcpr-wpml.php');
            $wpml = new SOFT79_WCPR_WPML();
            $wpml->init();
        }

        public function action_enqueue_scripts() {
            wp_enqueue_style( 'soft79_bulk_styles', SOFT79_WCPR()->plugin_url() . '/assets/css/frontend.css', array(), $this->get_version() );
        }

        function action_inject_prices() {
            $this->controller->execute();
        }

        function action_woocommerce_get_price_html( $price, $product ) {
            return $this->price_html( $price, $product );
        }

        function action_woocommerce_get_variation_price_html( $price, $variation ) {
            return $this->price_html( $price, $variation );
        }

        function price_html( $original_price_html, $product ) {
            $new_price_html = $original_price_html;

            //Min max price to show?
            $is_singular = is_singular('product');
            $show_min_max_price = $is_singular ? $this->options['show_min_max_price_singular'] : $this->options['show_min_max_price'];
            if ( $show_min_max_price ) {
                $prices = array();
                foreach ( $this->controller->get_valid_rules_for( $product ) as $price_rule ) {
                    $prices = array_merge( $prices, $price_rule->get_price_range( $product ) );
                }
                $original_price = $this->controller->get_original_price( $product );
                if ( count( $prices ) == 0 ) {
                    $prices[] = $original_price;
                }
                $min_price = min($prices);
                $max_price = max($prices);

                if ( $min_price != $max_price ) {
                    if ( ! $is_singular && ! $this->options['show_min_max_price'] ) {
                        //No change on category page
                    } elseif ( $is_singular && ! $this->options['show_min_max_price_singular'] ) {
                        //No change product detail page
                    } else {
                        $suffix = $product->get_price_suffix( $min_price );
                        $new_price_html = sprintf( _x( '%1$s&ndash;%2$s', 'Price range: from-to', 'woocommerce' ), wc_price( SOFT79_Rule_Helpers::get_price_to_display( $product, $min_price ) ), wc_price( SOFT79_Rule_Helpers::get_price_to_display( $product, $max_price ) ) ) . $suffix;
                    }
                    return apply_filters( 'soft79_wcpr_min_max_price_html', $new_price_html, $original_price_html, $product, $min_price, $max_price, $is_singular );
                }
            }

            //Sale price to show?
            $sale_price = $this->controller->get_sale_price( $product );
            if ( $sale_price !== false ) {
                $original_price = $this->controller->get_original_price( $product );
                $new_price_html = SOFT79_Rule_Helpers::format_sale_price(
                    SOFT79_Rule_Helpers::get_price_to_display( $product, $original_price ),
                    SOFT79_Rule_Helpers::get_price_to_display( $product, $sale_price )
                ) . $product->get_price_suffix( $sale_price );
            }

            return $new_price_html;
        }

        public function filter_woocommerce_cart_item_price( $price, $values ) {
            $cart = WC()->cart;

            $product = $values['data'];
            if ( $this->controller->is_pricing_rule_applied( $product ) ) {
                $min_price = $this->controller->get_temp_data( $product, 'stack_min_price' );
                $max_price = $this->controller->get_temp_data( $product, 'stack_max_price' );

                if ( $min_price !== null && $min_price != $max_price ) {
                    $price = sprintf( _x( '%1$s&ndash;%2$s', 'Price range: from-to', 'woocommerce' ), wc_price( SOFT79_Rule_Helpers::get_cart_taxed_price( $product, $min_price ) ), wc_price( SOFT79_Rule_Helpers::get_cart_taxed_price( $product, $max_price ) ) );
                }

                $price = SOFT79_Rule_Helpers::format_sale_price(
                    SOFT79_Rule_Helpers::get_cart_taxed_price( $product, $this->controller->get_temp_data( $product, 'original_price') ),
                    $price
                );
            }

            return $price;
        }

        public function filter_item_subtotal( $cart_subtotal, $values ) {
            $product = $values['data'];
            if ( $this->controller->is_pricing_rule_applied( $product ) ) {
                $qty = $values['quantity'];

                $from_price = SOFT79_Rule_Helpers::get_cart_taxed_price( $product, $qty * $this->controller->get_temp_data( $product, 'original_price') );
                $to_price = SOFT79_Rule_Helpers::get_cart_taxed_price( $product, $qty * $product->get_price() );

                $cart_subtotal = SOFT79_Rule_Helpers::format_sale_price( wc_price( $from_price ), wc_price( $to_price ) );

				// Display "excl tax" or "incl tax"
				$cart = WC()->cart;
				if ( $cart->tax_display_cart == 'excl' ) {
					if ( $cart->tax_total > 0 && $cart->prices_include_tax ) {
						$cart_subtotal .= ' <small>' . WC()->countries->ex_tax_or_vat() . '</small>';
					}
				} else {
					if ( $cart->tax_total > 0 && !$cart->prices_include_tax ) {
						$cart_subtotal .= ' <small>' . WC()->countries->inc_tax_or_vat() . '</small>';
					}
				}

            }
            return $cart_subtotal;
        }

        /**
         * Show discount information on single product page
         */
        function action_woocommerce_single_product_summary() {
            global $product;

            echo '<div class="soft79-single-product-discount-wrap">';
            foreach ( $this->controller->get_valid_rules_for( $product ) as $rule ) {
                $rule->render_product_page_html( $product );
            }
            echo '</div>';

            //Auto update when switching variation
            if ( $product->is_type( 'variable' ) ) {
                wp_enqueue_script( 'soft79-single-product-summary', plugins_url( 'assets/js/frontend/single-product-summary.js', __FILE__ ), array( 'jquery' ), $this->get_version(), true );
            }
        }


        /**
         * Used when switching between product variations
         */
        function action_woocommerce_available_variation( $data, $product, $variation ) {
            //var_dump($variation);die();
            ob_start();
            foreach ( $this->controller->get_valid_rules_for( $variation ) as $rule ) {
                $rule->render_product_page_html( $variation );
            }
            $summary = ob_get_contents();
            ob_end_clean();
            $data['j79_soft79_single_product_discount'] = $summary;
            return $data;
        }

        /**
         * Get overwritable template filename
         * @param string $template_name
         * @return string Template filename
         */
        public function get_template_filename( $template_name ) {
            $template_path = 'soft79-wc-pricing-rules';

            $plugin_template_path = plugin_dir_path( __FILE__ ) . 'templates/';

            //Get template overwritten file
            $template = locate_template( trailingslashit( $template_path ) . $template_name );

            // Get default template
            if ( ! $template ) {
                $template = $plugin_template_path . $template_name;
            }

            return $template;
        }

        /**
         * Include a template file, either from this plugins directory or overwritten in the themes directory
         * @param type $template_name
         * @return type
         */
        public function include_template( $template_name, $variables = array() ) {
            extract( $variables );
            include( $this->get_template_filename( $template_name ) );
        }

    } //Main class

    function SOFT79_WC_Pricing_Rules_Plugin() {
        _doing_it_wrong( 'SOFT79_WC_Pricing_Rules_Plugin', 'The function SOFT79_WC_Pricing_Rules_Plugin() is deprecated, use SOFT79_WCPR() instead.', '1.2.0' );
        return SOFT79_WC_Pricing_Rules_Plugin::instance();
    }
    function SOFT79_WCPR() {
        return SOFT79_WC_Pricing_Rules_Plugin::instance();
    }
    SOFT79_WCPR();
} elseif ( ! function_exists( 'soft79_wc_pricing_admin_notice' ) ) {
    add_action( 'admin_notices', 'soft79_wc_pricing_admin_notice' );
    function soft79_wc_pricing_admin_notice() {
        $msg = __( 'Multiple instances of the <i>SOFT79 Pricing Rules for Woocommerce</i>-plugin are activated. Please go to the Plugins-page and disable as required.', 'soft79-wc-pricing-rules' );
        echo '<div class="error"><p>' . $msg . '</p></div>';
    }
}

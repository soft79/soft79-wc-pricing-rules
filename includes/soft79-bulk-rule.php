<?php

class SOFT79_Bulk_Rule extends SOFT79_Rule {    
    
    protected $global_qty; //Quantity of matching products in the cart (only if global scope)
    
    public $product_ids;
    public $exclude_product_ids;
    public $category_ids;
    public $exclude_category_ids;
    public $user_roles;
    public $exclude_user_roles;
    public $bulk_rules;
    public $quantity_scope;
    
    public $display_on_prod_page = "description,table"; // "description,table", "description", "table" or "nothing"
        
    public static function rule_type() {
        return 'bulk';
    }
        
    public function __construct( $post ) {
        if ( is_numeric( $post ) ) {
            $this->id   = absint( $post );
            $this->post = get_post( $this->id );
        } elseif ( isset( $post->ID ) ) {
            $this->id   = absint( $post->ID );
            $this->post = $post;
        }
        
        //Valid post?
        if ( ! in_array( $this->post->post_type, array( 'j79_wc_price_rule', 'product', 'product_variation' ) ) ) {
            throw new Exception( sprintf("Invalid post type %s id %d", $this->post->post_type, $this->id) );
        }
        
        //Products have limited settings
        if ( $this->post->post_type == 'j79_wc_price_rule' ) {
            $this->quantity_scope = get_post_meta( $this->post->ID, '_j79_quantity_scope', true );        
            
            $this->product_ids = array_filter( array_map( 'absint', explode( ',', get_post_meta( $this->post->ID, '_j79_product_ids', true ) ) ) );
            $this->exclude_product_ids = array_filter( array_map( 'absint', explode( ',', get_post_meta( $this->post->ID, '_j79_exclude_product_ids', true ) ) ) );
            $this->category_ids = (array) get_post_meta( $this->post->ID, '_j79_product_categories', true );
            $this->exclude_category_ids = (array) get_post_meta( $this->post->ID, '_j79_exclude_product_categories', true );
            $this->user_roles = array_filter( array_map( 'wc_clean', (array) get_post_meta( $this->post->ID, '_j79_user_roles', true ) ) );
            $this->exclude_user_roles = array_filter( array_map( 'wc_clean', (array) get_post_meta( $this->post->ID, '_j79_exclude_user_roles', true ) ) );
        } else {
            $this->quantity_scope = '';
            $this->product_ids = array( $this->id ); //Just in case
            $this->exclude_product_ids = array();
            $this->category_ids = array();
            $this->exclude_category_ids = array();
            $this->user_roles = array();
            $this->exclude_user_roles = array();
        }
        
        $this->bulk_rules = get_post_meta( $this->post->ID, '_j79_bulk_rules', true );        
        if ( ! is_array( $this->bulk_rules ) ) {
            $this->bulk_rules = array();
        }
        
        $display_on_prod_page = get_post_meta( $this->post->ID, '_j79_display_on_prod_page', true );        
        if ( $display_on_prod_page !== '' ) {
            $this->display_on_prod_page = $display_on_prod_page;
        }        
    }
    
    public function is_valid_for_user( $user = null ) {
        if ( sizeof( $this->user_roles ) > 0 || sizeof( $this->exclude_user_roles ) > 0 ) {
            
            //Default: current user
            if ($user == null) {
                $user = wp_get_current_user();
            }

            if ( sizeof( $this->user_roles ) > 0 && ! array_intersect( $user->roles, $this->user_roles ) ) {
                return false;
            }
            
            if ( sizeof( $this->exclude_user_roles ) > 0 && array_intersect( $user->roles, $this->exclude_user_roles ) ) {
                return false;
            }            
        }
        return true;
    }
    
    /**
     *  $product WC_Product
     */
    public function is_valid_for_product( $product ) {
        $product_id = SOFT79_Rule_Helpers::get_product_id( $product ); 
        $variation_id = SOFT79_Rule_Helpers::get_variation_id( $product ); // false if not a variation

        //Excluded products
        if ( in_array( $product_id, $this->exclude_product_ids ) ) {
            return false;
        }
        if ( $variation_id && in_array( $variation_id, $this->exclude_product_ids ) ) {
            return false;
        }
        
        $product_cats = array();
        $product_cat_terms = get_the_terms( $product_id, 'product_cat' );
        if ( is_array($product_cat_terms) ) {
        	foreach( $product_cat_terms as $product_cat )
            	$product_cats[] = $product_cat->term_id;
    	}


        //Excluded cats
        if ( array_intersect( $product_cats, $this->exclude_category_ids ) ) {
            return false;
        }        

        //No products or cat restriction
        if ( sizeof( $this->product_ids ) == 0 && sizeof( $this->category_ids ) == 0 ) {
            return true;
        }
        
        //Included prods
        if ( in_array( $product_id, $this->product_ids ) ) {
            return true;
        }
        if ( $variation_id && in_array( $variation_id, $this->product_ids ) ) {
            return true;
        }

        //Included cats
        if ( array_intersect( $product_cats, $this->category_ids ) ) {
            return true;
        }
        
        return false;
    }    
    
    public function discover_cart( $cart_array ) {
        //error_log("Scope: " . $this->quantity_scope);
        if ( $this->quantity_scope == 'global' ) {
            $this->global_qty = 0;
            foreach ( $cart_array as $cart_item_key => $values ) {
                $product = $values['data'];
                $qty = $values['quantity'];    
                if ( $this->is_valid_for_product( $product ) ) {
                    $this->global_qty += $qty;
                }
            }
            //error_log("Global qty: " . $this->global_qty);
        } else {
        }
    }
    
    
    /**
     *  Returns false if no discount applies, otherwise the total discount (negative value means discount)
     *  Additional data can be stored in optional $data
     */
    function get_discount_for_product($product, $quantity, &$data = null ) {
        
        if ( ! $this->is_valid_for_product( $product ) || ! $this->is_valid_for_user() ) {
            return false;
        }
        
        $bulk_rules = $this->bulk_rules;
        
        $quantity_left = $quantity;
        $original_price = SOFT79_WC_Pricing_Rules_Plugin()->controller->get_original_price( $product );        
        $rule_stack = new SOFT79_Price_Acumulator();

        //Iterate backwards; first to find should be the best value for the customer
        $index = count($bulk_rules);
        while($index--) {    
            $rule = $bulk_rules[$index];
            //error_log( print_r($rule, true));
            if ( $rule['qty_to'] > $rule['qty_from'] || $rule['qty_to'] == 0 ) {
                //Rule 'min_qty'
                if ( $quantity_left >= $rule['qty_from'] || ($this->quantity_scope == 'global' && $this->global_qty >= $rule['qty_from'] ) ) {
                    $qty_for_this_rule = $rule['qty_to'] == 0 ? $quantity_left : $rule['qty_to'];
                    $price_for_this_rule = SOFT79_Rule_Helpers::get_relative_price( $original_price, $rule['price'] );
                    $taxed_price = SOFT79_Rule_Helpers::get_taxed_price( $product, $price_for_this_rule );
                    
                    $rule_stack->add( $qty_for_this_rule, $taxed_price );
                }
            } 
            elseif ( $rule['qty_to'] == $rule['qty_from'] ) {
                //Rule 'pack'
                if ( $quantity_left >= $rule['qty_from'] ) {
                    $qty_for_this_rule = floor ( $quantity_left / $rule['qty_from'] ) * $rule['qty_from'];
                    $price_for_this_rule = SOFT79_Rule_Helpers::get_relative_price( $original_price, $rule['price'] );                    
                    $taxed_price = SOFT79_Rule_Helpers::get_taxed_price( $product, $price_for_this_rule );
                    
                    $rule_stack->add( $qty_for_this_rule, $taxed_price );
                }
            }
            
            $quantity_left = $quantity - $rule_stack->total_qty();
            if ( $quantity_left == 0 ) break;            
        }
        
        
        //Bulk rule applied?
        if ( $rule_stack->has_a_value() ) {        
        
            //Complete the auto stack with single product prices
            if ( $quantity_left > 0 ) {                
                $rule_stack->add( $quantity_left, $original_price );
            }
            
            $data = array(
                'avg' => $rule_stack->avg_price(),
                'min' => $rule_stack->min_price(),
                'max' => $rule_stack->max_price(),
                'discount' => $rule_stack->total_price() - $quantity * $original_price
            );            
            return $data['discount'];
            
        }
        
        $data = array();
        return false;        
    }    
    
    /**
     *  Array of all possible product prices for this rule
     *  (Can be used to calculate min/max price on archive page)
     */
    public function get_price_range( $product ) {
        //error_log("get_price_range");
        $prices = array();
        $append_original_price = true;
        $bulk_rules = $this->get_rules_valid_for_stock( $product );
        
        //Empty rule, return empty array
        if ( count( $bulk_rules ) == 0 ) {            
            return $prices;
        }
        
        $original_price = SOFT79_WC_Pricing_Rules_Plugin()->controller->get_original_price( $product );
        foreach( $bulk_rules as $rule ) {
            //error_log(print_r($rule, true));
            if ( $rule['qty_from'] <= 1 ) {
                $append_original_price = false;
            }
            $prices[] = SOFT79_Rule_Helpers::get_relative_price( $original_price, $rule['price'] );
        }
        
        //Original price also applies
        if ( $append_original_price ) {
            $prices[] = $original_price;
        }
        return $prices;
    }

    
    protected function get_bulk_quantity_string($bulk_rule) {
        if ( $bulk_rule['qty_from'] == $bulk_rule['qty_to'] ) {
            return $bulk_rule['qty_from'];
        } elseif ( $bulk_rule['qty_to'] == 0 ) {
            return $bulk_rule['qty_from'] . "+";
        } else {
            return $bulk_rule['qty_from'] . "-" . $bulk_rule['qty_to'];
        }        
    }
    
    public function render_product_page_html( $product ) {
        if ( ! $this->is_valid_for_user() ) {
            echo "Not for this user";
            return;
        }
        if ( ! $this->is_valid_for_product( $product ) ) {
            echo "Not for this product";
            return;
        }
        
        if ( empty( $this->display_on_prod_page ) ) {
            return;
        }
    
    
    
        //Make array with values to show in the table
        $table_display_data = array();
        
        //Add 1+ line if from...to price is shown
        if ( SOFT79_WC_Pricing_Rules_Plugin()->options['show_min_max_price_singular'] ) {
            //Not if first rule already has a qty of 1
            if ( isset( $this->bulk_rules[0]['qty_from'] ) && $this->bulk_rules[0]['qty_from'] > 1 ) {
                $table_display_data[] = $this->get_rule_display_data( array( 'qty_from' => 1, 'qty_to' => 0, 'price'=>'100%' ), $product );
            }
        }

        //Remove rules that won't apply because of stock level
        foreach ($this->get_rules_valid_for_stock( $product ) as $rule) {
            $table_display_data[] = $this->get_rule_display_data( $rule, $product );
        }
        
        if ( count($table_display_data) == 0 ) {
            return;
        }
        
        $show_description = strpos ( $this->display_on_prod_page, "description" ) !== false && $this->get_description() !== '';
        $show_description = $show_description && $this->post->post_type != 'product';

        $show_table = strpos ( $this->display_on_prod_page, "table" ) !== false;

        $variables = array(
            'show_description' => $show_description,
            'show_table' => $show_table,
            'table_display_data' => $table_display_data,
            'rule' => $this,
            'product' => $product
        );
        SOFT79_WC_Pricing_Rules_Plugin()->include_template( 'single-product/bulk-rule.php', $variables );


    }
    
    protected function get_rule_display_data( $rule, $product ) {

            $price = SOFT79_Rule_Helpers::get_relative_price(
                SOFT79_WC_Pricing_Rules_Plugin()->controller->get_original_price( $product ), 
                $rule['price']
            );
            $qty_rule_html = $this->get_bulk_quantity_string($rule);

            //Pack price format
            if ( $rule['qty_from'] == $rule['qty_to'] && SOFT79_WC_Pricing_Rules_Plugin()->options['pack_price_format'] > 0 ) {
                $total_price =  $rule['qty_from'] * $price;
                $price_html = wc_price( SOFT79_Rule_Helpers::get_price_to_display( $product, $total_price ) );
                if ( SOFT79_WC_Pricing_Rules_Plugin()->options['pack_price_format'] == 2 ) {
                    $suffix = $product->get_price_suffix( $total_price );
                    $price_html = sprintf( __('%1$s (%2$s each)', "soft79-wc-pricing-rules"), $price_html, wc_price( SOFT79_Rule_Helpers::get_price_to_display( $product, $price ) )) . $suffix;
                }
            } else {
                $suffix = $product->get_price_suffix( $price );
                $price_html = wc_price( SOFT79_Rule_Helpers::get_price_to_display( $product, $price ) ) . $suffix;
            }
            $display_data = array( "qty" => $qty_rule_html, "price_html" => $price_html, "price" => $price );

            return apply_filters( 'soft79_wcpr_get_rule_display_data', $display_data, $rule, $product );
    }    
    
    public function get_rules_valid_for_stock( $product ) {
        //Don't hide those rules
        if ( is_admin() || ! SOFT79_WC_Pricing_Rules_Plugin()->options['hide_rules_not_in_stock'] ) {
            return $this->bulk_rules;
        }
        
        if ( $product->managing_stock() && ! $product->backorders_allowed() ) {
            $stock_quantity = intval( SOFT79_Rule_Helpers::get_stock_quantity( $product ) );
            //Remove rules that do not apply, because of lack of product stock
            if ( $this->quantity_scope != 'global' || $stock_quantity == 0) {
                $ret = array();
                foreach ( $this->bulk_rules as $idx => $rule ) {
                    if ( $rule['qty_from'] <= $stock_quantity ) {
                        $ret[] = $rule;
                    }                    
                }
                return $ret;
            }        
        }
        

        
        return $this->bulk_rules;
    }
    
}
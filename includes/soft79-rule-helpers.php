<?php
class SOFT79_Rule_Helpers {
    /**
     *  Get price incl/excl tax (depends on setting "Prices Entered With Tax" in WooCommerce)
     */
    static function get_taxed_price( $product, $price ) {
        if ( get_option('woocommerce_prices_include_tax') == 'yes' ) {
            return self::get_price_including_tax( $product, 1, $price );
        } else {
            return self::get_price_excluding_tax( $product, 1, $price );
        }
    }    
    /**     
     *  Get price incl/excl tax for cart (depends on setting "woocommerce_tax_display_cart" in WooCommerce)     
     */
    static function get_cart_taxed_price( $product, $price, $cart = null ) {
        if ($cart == null) {
            $cart = WC()->cart;
        }
        if ( $cart->tax_display_cart == 'excl' ) {
            return self::get_price_excluding_tax( $product, 1, $price );
        } else {
            return self::get_price_including_tax( $product, 1, $price );
        }
    }
    
    //New price can be any price or a discount percentage
    static function get_relative_price( $orig_price, $new_price) {
        if ($new_price == '') {
            return $orig_price;
        }
        
        if ( strstr( $new_price, '%' ) ) {
            $percent = str_replace( '%', '', $new_price ) / 100;
            if ( $percent < 0 ) {
                $percent = 1 - abs($percent); //if negative, treat it as a % discount
            }
            $return = $orig_price * $percent;
        } elseif ($new_price < 0) {
            //Negative value; calculate difference
            $return = $orig_price + $new_price;
        } else {
            $return = $new_price;
        }
        //error_log("get_relative_price of " . $orig_price . " , " . $new_price . " is: " . $return);
        
        return $return;
    }

//WC3.0.0 Helpers

    public static function get_product_prop( $product, $prop ) {
        //Since WC 3.0
        if ( is_callable( array( $product, 'get_prop' ) ) ) {
            return $product->get_prop( $prop );
        }
        return $product->$prop;
    }

    public static function set_product_prop( $product, $prop, $value ) {
        //Since WC 3.0
        if ( is_callable( array( $product, 'set_prop' ) ) ) {
            $product->set_prop( $prop, $value );
            return;
        }
        $product->$prop = $value;
    }    

    public static function get_product_id( $product ) {
        //Since WC 3.0
        if ( is_callable( array( $product, 'get_id' ) ) ) {
            return $product->get_id();
        }
        return $product->id;
    }

    public static function is_variation( $product ) {
        return $product instanceof WC_Product_Variation;
    }

    public static function get_variable_product_id( $product ) {
        if ( ! self::is_variation( $product ) ) {
            return false;
        }

        if ( is_callable( array( $product, 'get_parent_id' ) ) ) {
            return $product->get_parent_id();
        } else {
            return wp_get_post_parent_id( $product->variation_id );
        }
    }    

    /**
     * Get current variation id
     * @return int|bool False if this is not a variation
     */
    public static function get_variation_id( $product ) {
        if ( ! self::is_variation( $product ) ) {
            return false;
        }

        if ( is_callable( array( $product, 'get_id' ) ) ) {
            return $product->get_id(); 
        } elseif ( is_callable( array( $product, 'get_variation_id' ) ) ) {
            return $product->get_variation_id(); 
        }
        return $product->variation_id;
    }

    /**
     * Retrieve the id of the product or the variation id if it's a variant.
     * 
     * @param WC_Product $product 
     * @return int|bool The variation or product id. False if not a valid product
     */
    public static function get_product_or_variation_id( $product ) {
        if ( self::is_variation( $product ) ) {
            return self::get_variation_id( $product );
        } elseif ( $product instanceof WC_Product ) {
            return self::get_product_id( $product );
        } else {
            return false;
        }
    }    

    public static function get_stock_quantity( $product ) {
        return $product->get_stock_quantity();
    }

    public static function get_price_including_tax( $product, $qty = 1, $price = '' ) {
        if ($price === null) { 
            $price = $product->get_price();
        }

        //Since WC 3.0
        if ( is_callable( 'wc_get_price_including_tax' ) ) {
            return wc_get_price_including_tax( $product, array( 'qty' => $qty, 'price' => $price ) );
        }
        return $product->get_price_including_tax( $qty, $price );        
    }  

    public static function get_price_excluding_tax( $product, $qty = 1, $price = '' ) {
        if ($price === null) { 
            $price = $product->get_price();
        }

        //Since WC 3.0
        if ( is_callable( 'wc_get_price_excluding_tax' ) ) {
            return wc_get_price_excluding_tax( $product, array( 'qty' => $qty, 'price' => $price ) );
        }
        return $product->get_price_excluding_tax( $qty, $price );        
    }    


    /**
     * Returns the price including or excluding tax, based on the 'woocommerce_tax_display_shop' setting.
     * @param  WC_Product $product
     * @return float
     */
    public static function get_price_to_display( $product, $price = null ) {
        if ($price === null) { 
            $price = $product->get_price();
        }

        //Since WC 3.0
        if ( is_callable( 'wc_get_price_to_display' ) ) {
            return wc_get_price_to_display( $product, array( 'price' => $price ) );
        }
        return $product->get_display_price( $price );
    }

    public static function format_sale_price( $regular_price, $sale_price ) {
        if ( is_callable( 'wc_format_sale_price' ) ) {
            return wc_format_sale_price( $regular_price, $sale_price );
        }

        $price = '<del>' . ( is_numeric( $regular_price ) ? wc_price( $regular_price ) : $regular_price ) . '</del> <ins>' . ( is_numeric( $sale_price ) ? wc_price( $sale_price ) : $sale_price ) . '</ins>';
        return apply_filters( 'woocommerce_format_sale_price', $price, $regular_price, $sale_price );
    }

}

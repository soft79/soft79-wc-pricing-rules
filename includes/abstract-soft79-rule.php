<?php

abstract class SOFT79_Rule {
    public $id;
    public $post;

    static function rule_type() {}
    
    public function get_description() {
        //Using apply_filters('the_content', $this->post->post_content) displays the content of the current looped post!
        //Therefore this ugly method
        
        global $post;
        $post = $this->post;
        setup_postdata($post);
        $content = apply_filters('the_content', $post->post_content);
        wp_reset_postdata(); 
        
        return $content;
    }
    
    /**
     *  @return bool
     */
    abstract public function is_valid_for_user( $user = null );

    /**
     *  @return bool
     */
    abstract public function is_valid_for_product( $product );

    /**
     *  Allows rule to do some initialization
     */    
    abstract public function discover_cart( $cart_array );
    
    /**
     *  Get discounted price if this rule can and would be applied
     *  
     *  @param array &$data An array with additional data should be returned by this function:
     *  array( 
     *      'avg' => Average single product price, 
     *      'min' / 'max' => Highest/lowest discounted price (optional)
     *      'discount' => The total discount value (same as return value of this function ) for the product quantity
     *  );
     *  @return float|bool false if no discount/not applicable otherwise the total discount value (negative is a discount)
     */
    abstract public function get_discount_for_cart_item($cart_item, &$data = null );

    abstract public function render_product_page_html( $product );
    abstract public function get_price_range( $product );
    
}

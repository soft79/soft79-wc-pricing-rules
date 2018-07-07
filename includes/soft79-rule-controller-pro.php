<?php

//The heart of the magic
final class SOFT79_Rule_Controller_PRO extends SOFT79_Rule_Controller {
        
    public function __construct( $options = null ) {
        
        parent::__construct();        
        $this->create_post_type();
        
    }    
    
    function create_post_type() {
        register_post_type( 'j79_wc_price_rule',
            array(
                'labels' => array(
                    'name'               => _x( 'Pricing rules', 'post type general name', 'soft79-wc-pricing-rules' ),
                    'singular_name'      => _x( 'Pricing rule', 'post type singular name', 'soft79-wc-pricing-rules' ),
                    'menu_name'          => _x( 'Pricing rules', 'admin menu', 'soft79-wc-pricing-rules' ),
                    'name_admin_bar'     => _x( 'Pricing rule', 'add new on admin bar', 'soft79-wc-pricing-rules' ),
                    'add_new'            => _x( 'Add New', 'pricing rule', 'soft79-wc-pricing-rules' ),
                    'add_new_item'       => __( 'Add New Pricing rule', 'soft79-wc-pricing-rules' ),
                    'new_item'           => __( 'New Pricing rule', 'soft79-wc-pricing-rules' ),
                    'edit_item'          => __( 'Edit Pricing rule', 'soft79-wc-pricing-rules' ),
                    'view_item'          => __( 'View Pricing rule', 'soft79-wc-pricing-rules' ),
                    'all_items'          => __( 'Pricing rules', 'soft79-wc-pricing-rules' ),
                    'search_items'       => __( 'Search Pricing rules', 'soft79-wc-pricing-rules' ),
                    'not_found'          => __( 'No Pricing rules found.', 'soft79-wc-pricing-rules' ),
                    'not_found_in_trash' => __( 'No Pricing rules found in Trash.', 'soft79-wc-pricing-rules' )
                ),
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => 'woocommerce',
                'supports' => array('title', 'editor'),
                'has_archive' => false,
            )
        );

    } 
    
    protected $_rules = null;
    /**
     *  Returns all rules that are allowed for the user
     *  if is_admin() we return all the rules
     */
    protected function get_global_rules() {    
        //Remember the values, don't query again if already known
        if ($this->_rules === null) {
            $this->_rules = array();
            
            $args = array(
                'posts_per_page'   => -1,
                'orderby'          => 'date',
                'order'            => 'DESC',
                'post_type'        => 'j79_wc_price_rule',
                'post_status'      => 'publish',
            );
            $posts_array = get_posts( $args ); 
            foreach ($posts_array as $post ) {
                $rule_type = get_post_meta( $post->ID, '_j79_rule_type', true );

                if ( $rule_type == SOFT79_Bulk_Rule::rule_type() ) {
                    $rule = new SOFT79_Bulk_Rule( $post );
                    if ( $rule->is_valid_for_user() || is_admin() ) {
                        $this->_rules[] = $rule;
                    }
                } else {
                    error_log( sprintf( "SOFT79_Rule_Controller_PRO::get_global_rules() Unknown rule type: %s", $rule_type ) );
                }
                
            }
        }
        return $this->_rules;
    }
    
    public function get_valid_rules_for( $product ) {    
        $valid_rules = parent::get_valid_rules_for( $product );

        foreach ( $this->get_global_rules() as $rule ) {
            if ( $rule->is_valid_for_product( $product ) ) {
                $valid_rules[] = $rule;
            }
        }
        
        return $valid_rules;
    }

}

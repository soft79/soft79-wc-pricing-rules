<?php

/**
 * WPML Compatibility tools
 * @since 1.1.4
 */
class SOFT79_WCPR_WPML {

    public function init() {
        add_action('soft79_wcpr_bulk_rule_loaded', array( $this, 'action_soft79_wcpr_bulk_rule_loaded' ), 10, 1 );
    }

// HOOKS

    public function action_soft79_wcpr_bulk_rule_loaded( $rule ) {
        $rule->product_ids = $this->translate_product_ids( $rule->product_ids );
        $rule->exclude_product_ids = $this->translate_product_ids( $rule->exclude_product_ids );

        $rule->category_ids = $this->translate_product_cat_ids( $rule->category_ids );
        $rule->exclude_category_ids = $this->translate_product_cat_ids( $rule->exclude_category_ids );
    }


// HELPER FUNCTIONS


    public function translate_product_ids( $product_ids ) {
        foreach ( $product_ids as $i => $prod_id ) {
            $post_type    = get_post_field( 'post_type', $prod_id );
            $product_ids[$i] = apply_filters( 'wpml_object_id', $prod_id, $post_type, true );
        }
        return $product_ids;
    }

    public function translate_product_cat_ids( $product_cat_ids ) {
        foreach ( $product_cat_ids as $i => $product_cat_id ) {
            $product_cat_ids[$i] = apply_filters( 'wpml_object_id', $product_cat_id, 'product_cat', true );
        }
        return $product_cat_ids;
    }    

}
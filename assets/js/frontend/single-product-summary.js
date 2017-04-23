// Update discount information when variation is selected on the single product page
jQuery(document).on( 'found_variation', 'form.cart', function( event, variation ) {  
    jQuery('.soft79-single-product-discount-wrap').html( variation.j79_soft79_single_product_discount );
} );

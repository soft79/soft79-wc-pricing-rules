<?php
/**
 * Bulk rule on the single product page
 * 
 * This template can be overridden by copying it to yourtheme/soft79-wc-pricing-rules/single-product/bulk-rule.php
 * 
 * @version     1.1.0
 */

if ( ! $show_table && ! $show_description ) { return; }

?>
<div class='soft79-discount-description' style='margin-bottom:1em'>
<?php            
	    if ( $show_description ) {
	        echo $rule->get_description();
	    }

	    if ( $show_table ):
			?>
			<table class='soft79-wc-pricing-rules'>
				<tr><th><?php _e("Qty", 'woocommerce'); ?> </th><th><?php _e("Price", 'woocommerce') ?></th></tr>
				<?php 
					foreach ($table_display_data as $row): 
					//$discount_perc = round( ( $row["price"] - $product->get_price() ) * 100 /  $product->get_price() ) . ' %';
					?>
					<tr><td><?php echo $row["qty"];?></td><td><?php echo $row["price_html"]; ?></td></tr>
					<?php 
					endforeach; ?>
			</table>
			<?php 
		endif; 
?>
</div>

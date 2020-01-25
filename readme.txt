=== SOFT79 Pricing Rules for WooCommerce ===
Contributors: josk79
Tags: woocommerce, pricing, discount
Requires at least: 4.0.0
Tested up to: 5.3.2
Stable tag: 1.4.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily create bulk prices or discounts in WooCommerce.

== Description ==

"Pricing Rules for WooCommerce" allows you to set bulk pricing rules to products. For example 5% off if customer buys 6 or more. 10% off if customer buys 12 or more.

The prices will be presented to the customer on the product page in the form of a table.

Features:

Create pricing rules the easy way!

* Per-product bulk pricing
* Automatically presents a table with the available prices
* Bulk pricing rules for multiple products
* Bulk pricing rules for certain categories
* Bulk pricing rules for combinations of products
* Pricing rules based on customer roles
* Automatically presents a custom message to inform the customer about offers

More information on [www.soft79.nl](http://www.soft79.nl).

== Installation ==

1. Upload the plugin in the `/wp-content/plugins/` directory, or automatically install it through the 'New Plugin' menu in WordPress
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Can I change the way the discounted price is displayed? =

You can use the filter `'soft79_wcpr_min_max_price_html' ( $new_price_html, $original_price_html, $product, $min_price, $max_price, $is_singular )`

The following will display 'From $ x.xx' on the catalog page (Notice that the setting Show min-max price range on category page must be checked):

```
add_filter ('soft79_wcpr_min_max_price_html', 'soft79_wcpr_min_max_price_html', 10, 6);
function soft79_wcpr_min_max_price_html( $new_price_html, $original_price_html, $product, $min_price, $max_price, $is_singular ) {
	if ( ! $is_singular ) {
		return sprintf( __('From %s', 'your-text-domain'), wc_price( $product->get_display_price( $min_price ) ) );
	}
}
```


== Screenshots ==

1. Integrated to the edit product page
2. Optionally displays the from-to price range on the category page.
3. Optionally presents a table with available prices/discounts to the customer.

== Changelog ==

= 1.4.3 =
*Release Date - 2020-01-20*
* All PRO functionality is now included in the FREE version

= 1.4.2 =
*Release Date - 2020-01-19*
* FEATURE: WP-Multilang support
* FIX: Display sale price if a discount applies to 1x quantity
* FIX: PHP Notice about count() and bulk_rules

= 1.4.1 =
* FIX: Not able to exclude categories on global product rules

= 1.4.0 =
* FIX: Invalid discount calculated for tax exempt customers
* FIX: Product variation rules were not saved properly
* FIX: Javascript product variations

= 1.3.1 =
* FEATURE: Customizable package price format
* FIX: Admin Javascript in combination with newest WooCommerce version
* INTERNAL: Created SOFT79_Bulk_Pricing_Admin::update_post_bulk_rules

= 1.3.0 =
* INTERNAL: Filter 'soft79_wcpr_accounted_quantity' that allows overriding the amount of items that are taken in account for a discount.
* Removed 'sale price' logic for rules with a quantity of 1

= 1.2.0 =
* FEATURE: Bulk pricing rules for product variations

= 1.1.4 =
* FIX: Inject prices to cart items as soon as possible (fixes issue with coupon minimum spend).
* FIX: WPML Compatibility

= 1.1.3 =
* FIX: Discount on variations in WC3.0+
* FIX: PHP Deprecation message in class-meta-box-bulk-rules.php

= 1.1.2 =
* FIX: Removed debug messages "Not for this product/user"

= 1.1.1 =
* FIX: Product selector WooCommerce 3.0 Compatibility (Select2 v4)

= 1.1.0 =
* FIX: WooCommerce 3.0 Compatibility
* FEATURE: Overrideable template for the single page discount information
* PERFORMANCE: Use cached query when retrieving product categories.

= 1.0.4 =
* FEATURE: filter 'soft79_wcpr_min_max_price_html' to allow customization of the displayed min-max price range
* FIX: Compatibility with PHP versions prior to 5.5
* FIX: Variable product support

= 1.0.3.0 =
* FEATURE: Auto update price information when switching between product variants
* FIX: Removed some notices/warnings

= 1.0.2.4 =
* FIX: If multiple variants of the same product exist in the cart, the same price would be applied to all variants

= 1.0.2.3 =
* FIX: Fatal error if WooCommerce was disabled
* FIX: Respect 'woocommerce_tax_display_shop' and 'woocommerce_tax_display_cart'
* FIX: Respect price suffix and excl / incl tax message in cart subtotal

= 1.0.2.1 =
* FIX: Removed auto update code

= 1.0.2 =
* FIX: Some PHP warnings were displayed on fresh installations.
* FIX: Tax calculation

= 1.0.0 =
* First public release

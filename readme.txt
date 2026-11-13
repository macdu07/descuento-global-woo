=== Descuento Global Woo ===
Contributors: mauriciocorrea
Tags: woocommerce, discount, sale, campaign, prices
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Schedules store-wide or category discount campaigns for WooCommerce without changing the prices saved on your products.

== Description ==

Descuento Global Woo lets administrators and Shop Managers manage a list of percentage discount campaigns for the whole store or for selected product categories. Each campaign can be switched on or off, edited, duplicated and deleted. When several active campaigns include the same product, the biggest discount applies.

The start and end dates are optional. The discount is calculated on the fly from the regular price, works in the catalog, cart, checkout and WooCommerce blocks, and restores the original sale prices when the campaign ends.

Included in Spanish (es_ES, es_CO, es_MX).

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin from the WordPress dashboard.
3. Open WooCommerce > Discount campaigns.
4. Create a campaign: name, percentage, the products it applies to and, if needed, its dates.

== Frequently Asked Questions ==

= Does it work with page caching? =

When the campaign is saved, starts or ends, the plugin clears WooCommerce price transients and purges the CloudPanel Varnish cache (CLP Varnish Cache). Other page caches can hook into the `dgw_purge_page_cache` action.

Prices are always calculated live. When the campaign starts or ends, the first request that reaches PHP (admin, cart, checkout or AJAX) clears the caches, so no server cron is required. A system cron is optional and only makes the purge happen on time on sites with a full-page cache and very little traffic reaching PHP.

= Can I change the sale badge text? =

Themes and page builders usually render their own badge. Developers can change the plugin's default badge (`-20%`) with the `dgw_sale_badge_text` filter.

= Can I list only products on sale (Bricks, blocks, shortcodes)? =

Yes. While a campaign is active, its products are added to WooCommerce's on-sale product list, which the Bricks Query Loop "On sale" option, the On Sale Products block and the `[sale_products]` shortcode use. The list is cached and refreshed when products or campaigns change.

= Do coupons still work? =

Yes. Products in the campaign are marked as on sale, so coupons with "Exclude sale items" enabled will not apply to them.

== Changelog ==

= 1.3.0 =
* Multiple campaigns: list, on/off switch, edit, duplicate and delete. The biggest discount wins when campaigns overlap.
* Settings from 1.2 are migrated into the first campaign.
* Redesigned admin screens.
* Limit a campaign to selected product categories and exclude categories.
* Detects campaign start and end on any request, so a server cron is optional.
* Campaign products appear in WooCommerce's on-sale list (Bricks "On sale" queries, On Sale Products block, [sale_products]).
* Purges the Varnish page cache when the campaign is saved, starts or ends.
* Per-request caching of settings and product eligibility.
* English source strings with Spanish translations (es_ES, es_CO, es_MX).

= 1.2.1 =
* Visual adjustment to the clear-settings icon.

= 1.2.0 =
* New Everforest palette and improved admin messages.
* New option to clear all settings.

= 1.1.0 =
* Optional start and end dates.
* Redesigned admin screen.

= 1.0.0 =
* First release.

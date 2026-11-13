# Descuento Global Woo

Reusable plugin that schedules percentage discount campaigns for a WooCommerce store, store-wide or for selected categories, without changing the prices saved on products.

## Features

- A list of campaigns: create, edit, duplicate, delete and switch each one on or off.
- Each campaign has a name and a percentage between `0.01 %` and `100 %`.
- When several active campaigns include the same product, the biggest discount applies.
- Applies to the whole store or to selected product categories (subcategories included), with optional excluded categories. Variations follow their parent product's categories.
- Optional start and end dates in the WordPress timezone.
- Manual switch plus scheduled, active, ended and disabled states.
- Temporarily takes precedence over existing sale prices and restores them automatically.
- Works with simple and variable products, cart, checkout, HPOS and WooCommerce blocks (Store API).
- Campaign products join WooCommerce's on-sale list (`wc_get_product_ids_on_sale()`), so "On sale" queries work: the Bricks Query Loop "On sale" option, the On Sale Products block and `[sale_products]`.
- Strikethrough regular price and a percentage badge (`-20%`). Themes and page builders such as Bricks usually render their own badge; developers can change the text with the `dgw_sale_badge_text` filter.
- Purges the CloudPanel Varnish page cache when the campaign is saved, starts or ends.
- Campaign list designed as a coupon booklet (status, schedule bar, on/off switch), in Mauricio Correa's palette.
- Settings from version 1.2 are migrated automatically into the first campaign.
- English source strings, with Spanish translations for `es_ES`, `es_CO` and `es_MX`.

## Requirements

- WordPress 6.9 or later.
- WooCommerce 11.0 or later.
- PHP 7.4 or later.

## Installation

1. Copy the `descuento-global-woo` folder into `wp-content/plugins/`.
2. Activate **Descuento Global Woo** from the Plugins screen.
3. Create campaigns in **WooCommerce → Discount campaigns**.

If the start date is empty, the campaign starts as soon as it is on. If the end date is empty, it runs until it is turned off.

## Page caching and scheduling

Prices are always calculated live, so the cart and checkout are correct the moment the campaign starts or ends. What needs to happen at that moment is clearing caches (WooCommerce price transients and the page cache).

The plugin does not depend on a server cron for that. It stores the last applied campaign status and compares it on every request that reaches PHP. Admin, cart, checkout and AJAX requests always bypass page caches, so the first of them after the start or end date clears the caches. WP-Cron events are also scheduled as a punctual nudge.

A real system cron is optional. It only makes the purge happen on time on sites with a full-page cache and very little traffic reaching PHP:

```
wp --path=/path/to/site cron event run --due-now
```

## Extensibility

Filters:

- `dgw_effective_percentage( $percentage, $product, $campaign )`
- `dgw_product_is_eligible( $eligible, $product, $campaign )` (receives the result of the campaign's category rules)
- `dgw_sale_badge_text( $text, $percentage, $product )`

Actions:

- `dgw_campaign_started( $campaign )`
- `dgw_campaign_ended( $campaign )`
- `dgw_purge_page_cache` (purge other page caches when prices change)

## Translations

Regenerate the template and compiled files after changing strings:

```
wp i18n make-pot . languages/descuento-global-woo.pot --exclude=languages,.git,.impeccable
wp i18n make-mo languages
wp i18n make-php languages
```

## License

GPL-2.0-or-later.

## Author

[Mauricio Correa](https://maurouix.com/)

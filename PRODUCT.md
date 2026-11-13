# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Shop managers and store owners of WooCommerce stores managed by Mauricio Correa (web designer and developer). They are not technical: they set up sale campaigns (Black Friday, seasonal sales) from wp-admin without touching product prices. The developer installs and maintains the plugin; the shop manager operates it.

Later the plugin will be published publicly (WordPress.org or sale), so any WooCommerce store owner becomes a user. It must not depend on this developer's hosting or tooling.

## Product Purpose

Schedule percentage discount campaigns for a WooCommerce store, store-wide or by product category, without modifying stored product prices. Success: a non-technical manager creates, schedules, turns on and off campaigns with confidence, and customers always see correct prices in catalog, cart, checkout and blocks.

Replaces Woo Discount Rules (Flycart) on client stores, keeping only what these stores actually use.

## Positioning

A small, focused alternative to rule-builder plugins: discounts are applied at the price-getter level (so the Store API, blocks, structured data and feeds see them), never by rewriting HTML or saving prices. Simple campaigns instead of a generic rule engine.

## Operating Context

- Lives under WooCommerce → Discount campaigns in wp-admin, beside native WooCommerce screens.
- Storefronts are built with page builders (Bricks); the theme or builder renders the sale badge, so badge styling is not the plugin's job.
- Hosting may have full-page caches (Varnish on CloudPanel) and no server cron; the plugin must not require server access.
- Campaigns happen a few times per year; managers return to the screen rarely and need to understand state at a glance.

## Capabilities and Constraints

- Multiple campaigns in a list; each can be turned on or off, edited, duplicated and deleted.
- Each campaign: percentage, scope (whole store or included categories), excluded categories, optional start and end in the WordPress timezone.
- When several active campaigns match a product, the biggest discount wins.
- A campaign overrides a product's own sale price for its duration and restores it afterward.
- Requires WordPress 6.9+, WooCommerce 11+, PHP 7.4+. HPOS and cart/checkout blocks compatible.
- English source strings with Spanish translations (es_ES, es_CO, es_MX); must stay translation-ready for public release.

## Brand Commitments

- Plugin name: Descuento Global Woo. Author: Mauricio Correa (https://maurouix.com/).
- Admin uses the author's palette: CM-Cream #F8F3EA, CM-Turquoise #00AEAE, CM-Orange #FF6B30, CM-Red #EB3D40, CM-Gunmetal #1D2730.
- A discreet signature linking to maurouix.com in the admin footer; no logo in the plugin header.

## Evidence on Hand

No testimonials, customers or usage metrics exist; do not invent them.

## Product Principles

1. Prices are never written to the database; turning a campaign off always restores the store exactly.
2. A non-technical manager must understand every campaign's state without reading documentation.
3. Correct prices everywhere (catalog, cart, checkout, blocks) matter more than visual flourish.
4. No dependency on server access, cron or a specific host.
5. Stay small: only features real stores use.

=== PizzaPilot ===
Contributors: erichmond
Tags: woocommerce, delivery, time slots, pizza, ordering
Requires at least: 6.3
Tested up to: 7.1
Stable tag: 1.2.1
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Slot-based delivery and collection ordering for WooCommerce. Built for local pizzerias.

== Description ==

PizzaPilot helps local pizzerias manage delivery and collection orders through WooCommerce with time slot-based scheduling.

**Features:**

* **Time Slot Ordering** — Auto-generated 30-minute delivery slots for today, shown at checkout
* **Delivery Radius Checker** — Validates customer postcodes against your delivery area using postcodes.io (UK postcodes, free, no API key)
* **Kitchen Order View** — Admin dashboard showing today's orders grouped by time slot with completion toggles
* **WooCommerce Integration** — Works with both classic and block checkout, supports HPOS
* **Delivery & Collection** — Customers choose delivery or collection at checkout; out-of-range postcodes fall back to collection only

PizzaPilot uses the WooCommerce Additional Checkout Fields API for seamless integration with the checkout flow.

**PizzaPilot Pro** extends the free version with future date ordering, slot capacity limits, recurring slot templates, interactive Mapbox delivery maps with What3Words, drag-and-drop kitchen UI, and more. [Learn more](https://pizzapilotpro.com/pricing/).

== External Services ==

This plugin connects to the postcodes.io API to convert UK postcodes into geographic coordinates. This is required to calculate the distance between your store and a customer's address, which is how the delivery radius check works.

**What is sent, and when:**

* A single UK postcode is sent to `https://api.postcodes.io/postcodes/{postcode}` each time a delivery radius check runs. No other customer data (name, address, email, order contents) is transmitted.
* This happens when a customer enters a postcode at checkout, when a customer uses the Delivery Postcode Checker block, and when your configured store postcode is looked up for the distance calculation.
* Results are cached in WordPress transients so that repeat lookups of the same postcode do not re-contact the service.

No API key or account is required, and the plugin sends no data to the service unless a postcode check is actually performed.

Service: https://postcodes.io/
Source code and licence (MIT): https://github.com/ideal-postcodes/postcodes.io

== Installation ==

1. Upload the `pizzapilot` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. WooCommerce must be installed and active
4. Go to PizzaPilot > Settings to configure your delivery postcode, radius, and slot times

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =

Yes. PizzaPilot requires WooCommerce 8.9 or later to be installed and active. Slot selection is built on
WooCommerce's Additional Checkout Fields API, which is not available in earlier releases. On an older
WooCommerce the plugin will load but the checkout fields will not be registered.

= How are delivery slots generated? =

Slots are auto-generated in 30-minute intervals between your configured start and end times for today. Slots that have already passed are automatically hidden.

= How does the delivery radius work? =

You enter your store's postcode and a maximum delivery radius (km or miles). Customer postcodes are checked against this radius using the postcodes.io API and Haversine distance calculation. Customers outside the radius can only select "Collection".

= Does this work with the WooCommerce block checkout? =

Yes. PizzaPilot uses the WooCommerce Additional Checkout Fields API, which works with both the classic shortcode checkout and the block-based checkout.

= Can customers order for future dates? =

The free version supports same-day ordering only. Future date scheduling is available in PizzaPilot Pro.

= Does it support HPOS (High-Performance Order Storage)? =

Yes. PizzaPilot declares HPOS compatibility and uses WooCommerce order methods for all meta operations.

== Screenshots ==

1. Kitchen Orders page showing orders grouped by time slot
2. Settings page with delivery configuration
3. Checkout with delivery type and time slot selection

== Changelog ==

= 1.2.1 =
* Fixed: on block checkout, an order could be placed with no delivery time when the shop had no slots available. WooCommerce rejects a select field with an empty options list, so the Delivery Time field was never registered and nothing validated it. A placeholder option now keeps the field registered and blocks checkout until a real slot is available.
* Fixed: the Kitchen Orders page showed "No orders for today" on stores using classic checkout. The query ordered on the block checkout's meta key, which excluded every order that stored its slot under the classic key. Slot grouping is unchanged; the groups were already sorted in PHP.
* Fixed: the "Pro: Custom time slots" hint on the Delivery settings tab ran on into its own tooltip text, because no stylesheet defined the tooltip classes. The tooltip now reveals on hover and keyboard focus, and honours reduced-motion preferences.

= 1.2.0 =
* New: "Delivery Postcode Checker" block (woocommerce category) — icon-trigger modal that lets customers verify delivery to their postcode. Background and text colour controls in the block sidebar.
* New: pizzapilot_delivery_checker_block_strings filter for overriding the block's user-facing copy.
* Improved: postcode-check AJAX extracted to a shared helper (pizzapilot-postcode-api.js) reused by checkout and the new block.
* Improved: public scripts only load on cart, checkout, or pages where they are needed — no longer enqueued site-wide.
* Improved: WordPress-Extra PHPCS pass — zero errors, zero warnings.
* Removed: empty public stylesheet placeholder.

= 1.0.0 =
* Initial release
* Time slot-based ordering at WooCommerce checkout
* Delivery radius validation via postcodes.io
* Kitchen order view with completion toggles
* HPOS compatibility
* WooCommerce block and classic checkout support

== Upgrade Notice ==

= 1.2.1 =
Bug fix release. Prevents block checkout accepting an order with no delivery time, restores the Kitchen Orders page for stores on classic checkout, and fixes tooltip styling on the Delivery settings tab.

= 1.2.0 =
Adds the Delivery Postcode Checker block and a refactored, conditionally-enqueued public asset pipeline.

= 1.0.0 =
Initial release of PizzaPilot.

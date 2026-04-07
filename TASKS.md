# Task List: PizzaPilot Free Plugin

> Generated from: PRD.md + original tasks/tasks.json (reconciled)
> Date: 2026-04-07

## Overview

PizzaPilot Free is the foundation plugin for slot-based delivery and collection ordering via WooCommerce. This task list covers all remaining work for the free version. Tasks reference the original task IDs from `tasks/tasks.json` where applicable.

## Completed Tasks (no further work needed)

- [x] **Task 1** -- Plugin Initialization and Structure Setup
- [x] **Task 2** -- Database Schema Creation (`wp_pizzapilot_order_slots`)
- [x] **Task 3** -- Admin Settings Panel (General/Delivery/Advanced tabs)
- [x] **Task 5** -- Time Slot Selection at Checkout (classic + block)
- [x] **Task 6** -- Pizza Quantity Deduction Logic (item counting, Pro hooks)
- [x] **Task 7** -- Basic Delivery Radius Checker (postcodes.io, Haversine, AJAX)
- [x] **Task 8** -- Kitchen Order Interface (order cards, completion toggle, CSS)

## Remaining Work

### 1. Plugin Infrastructure (Task 11)

- [ ] **1.1** Add WooCommerce dependency check on activation
  - What: In `class-pizzapilot-activator.php`, check WooCommerce is active before creating DB table. If missing, set a transient and bail. Add `admin_notices` hook to show error. Deactivate plugin if WooCommerce absent.
  - Test: Deactivate WooCommerce, activate PizzaPilot -- admin notice appears and plugin deactivates. Reactivate both -- clean activation.

- [ ] **1.2** Add `settings/index.php` security file
  - What: Create `settings/index.php` with `<?php // Silence is golden.` to match `admin/`, `includes/`, `public/`.
  - Test: Browse to `/wp-content/plugins/pizzapilot/settings/` -- blank page, not directory listing.

- [ ] **1.3** Implement `uninstall.php` cleanup logic
  - What: Delete options (`pizzapilot_general_settings`, `pizzapilot_delivery_settings`, `pizzapilot_advanced_settings`), drop `wp_pizzapilot_order_slots` table, clear `pizzapilot_geo_*` transients.
  - Test: Delete plugin via WP admin -- verify options gone and table dropped.

- [ ] **1.4** Fix activator/deactivator DocBlocks
  - What: Replace "Short Description. (use period)" boilerplate in both `class-pizzapilot-activator.php` and `class-pizzapilot-deactivator.php` with actual descriptions.
  - Test: Read both files, confirm DocBlocks describe what each method does.

### 2. Time Slot Management Interface (Task 4)

- [ ] **2.1** Create slot CRUD admin page -- list and add form
  - What: New admin sub-page under PizzaPilot for managing slots. Table listing today's slots (date, start, end, availability). Add form with nonce + `manage_options` capability check. Save via `$wpdb->insert()` with `$wpdb->prepare()`.
  - Test: Add a slot for today, verify it appears in the table and in the database.

- [ ] **2.2** Add slot edit and delete functionality
  - What: Edit/delete action links on each row. Edit via `$wpdb->update()`, delete via `$wpdb->delete()`. Nonce verification on both. Block deletion of slots with associated orders.
  - Test: Edit a slot, verify change. Delete empty slot, verify removed. Try deleting slot with order -- blocked.

- [ ] **2.3** Wire checkout dropdown to database-managed slots
  - What: Update `get_delivery_time_slots()` to query `wp_pizzapilot_order_slots` for today. Keep auto-generation from start/end times as fallback when no manual slots exist. Apply 20-min cutoff. Continue applying `pizzapilot_time_slots` filter.
  - Test: Create manual slots, checkout shows them. Delete all manual slots, checkout falls back to auto-generated.

- [ ] **2.4** Lock slot date to today in free version
  - What: Date field defaults to today and disabled. Greyed-out date picker with PRO badge and upsell CTA. `class_exists('PizzaPilot_Pro')` enables it when Pro active.
  - Test: Date locked to today with Pro upsell visible.

### 3. Slot Tracking (Tasks 6/9 remaining)

- [ ] **3.1** Track slot `order_id` on order placement
  - What: In `update_slot_availability()`, write `order_id` to `wp_pizzapilot_order_slots` row via `$wpdb->update()` with `$wpdb->prepare()`. Informational in free version (unlimited capacity).
  - Test: Place order, check DB -- slot row has `order_id` populated.

- [ ] **3.2** Clear slot `order_id` on cancellation
  - What: Extend `release_order_slot()` to set `order_id = NULL` on the slot row via `$wpdb->update()`.
  - Test: Place order (slot linked), cancel order (slot `order_id` cleared).

- [ ] **3.3** Pro upsell for capacity controls
  - What: In slot management UI (task 2.1), show "Unlimited" in availability column. Disabled number input with PRO badge tooltip.
  - Test: Availability column shows "Unlimited", capacity input disabled with upsell.

### 4. WooCommerce Integration Polish (Tasks 9/13 remaining)

- [ ] **4.1** Remove debug `error_log()` calls
  - What: Remove 5 `error_log()` calls in `public/class-pizzapilot-public.php` (lines ~215, 221, 261, 267, 412).
  - Test: `grep -r 'error_log(' *.php` returns zero results.

- [ ] **4.2** Declare HPOS compatibility
  - What: Add `before_woocommerce_init` hook with `FeaturesUtil::declare_compatibility()`. Replace all `update_post_meta()`/`get_post_meta()` in `class-pizzapilot-public.php` with `$order->update_meta_data()`/`$order->get_meta()` + `$order->save()`.
  - Test: Enable HPOS, place order -- delivery meta saved and displayed correctly. Kitchen page loads.

- [ ] **4.3** Add HPOS order list column hooks
  - What: Add `manage_woocommerce_page_wc-orders_columns` and `manage_woocommerce_page_wc-orders_custom_column` hooks alongside existing CPT hooks.
  - Test: With HPOS enabled, WooCommerce > Orders shows "Delivery Slot" column.

- [ ] **4.4** Add slot info to order emails
  - What: Hook into `woocommerce_email_after_order_table` to display delivery type and time slot in order confirmation emails (HTML + plain text).
  - Test: Place order, check confirmation email includes slot info.

- [ ] **4.5** Add slot info to customer order view
  - What: Hook into `woocommerce_order_details_after_order_table` to display slot info on the customer-facing order details page.
  - Test: View order as customer in My Account > Orders -- slot info visible.

### 5. Pro Feature Teasers (Task 10 remaining)

- [ ] **5.1** Kitchen page Pro upsell banner
  - What: Dismissible notice below kitchen page title: "Upgrade to PizzaPilot Pro for live-updating orders, drag-and-drop reordering, and kitchen ticket printing." Track dismissal in user meta. Only show when Pro not active.
  - Test: Notice appears, dismiss it, doesn't reappear. Not shown when Pro class exists.

- [ ] **5.2** Slot date picker Pro upsell
  - What: In slot management form (task 2.4), greyed-out date picker with PRO badge. Already covered by task 2.4 -- this ensures it's visually consistent with other Pro teasers.
  - Test: Consistent styling with same-day toggle Pro upsell.

### 6. Internationalization Fixes (Task 14 remaining)

- [ ] **6.1** Fix i18n concatenation bugs
  - What: In `class-pizzapilot-settings.php`: fix `delivery_radius_callback()` (~line 505) to use `sprintf()` instead of concatenating inside `esc_html__()`. Fix `delivery_start_time_callback()` and `delivery_end_time_callback()` to not wrap `$upgrade_message` in `__()`.
  - Test: Run `wp i18n make-pot . languages/pizzapilot.pot` -- no warnings. Settings render correctly.

- [ ] **6.2** Fix untranslatable upgrade message
  - What: In `Pizzapilot_Helpers::pizzapilot_pro_upgrade_message()`, wrap the text portion in `__()` with `'pizzapilot'` text domain.
  - Test: String appears in generated `.pot` file.

- [ ] **6.3** Add missing DocBlock to `delivery_end_time_callback()`
  - What: Add DocBlock with `@since 1.0.0` and `@return void` matching sibling method.
  - Test: DocBlock present with proper tags.

### 7. Code Quality & WordPress.org Readiness

- [ ] **7.1** Audit and fix output escaping
  - What: Review every `echo` in the plugin for proper escaping (`esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`).
  - Test: Browse all admin pages with `WP_DEBUG` enabled -- no notices.

- [ ] **7.2** Ensure `$wpdb->prepare()` on all queries
  - What: Audit all `$wpdb->` calls. New CRUD code from section 2 and section 3 must use `prepare()`.
  - Test: Search for `$wpdb->` -- all parameterised.

- [ ] **7.3** Update `README.txt` to WordPress.org format
  - What: Replace boilerplate with real content: plugin name, contributors, tags, requires/tested versions, description, installation, FAQ, changelog.
  - Test: Validate at WordPress.org readme validator.

- [ ] **7.4** Add PHPCS configuration
  - What: Create `composer.json` with `wp-coding-standards/wpcs` dev dependency. Create `phpcs.xml.dist` targeting `WordPress-Extra` with `pizzapilot` text domain.
  - Test: `vendor/bin/phpcs` returns zero errors.

### 8. Documentation (Task 15)

- [ ] **8.1** Add contextual help tabs to admin screens
  - What: Add help tabs to settings, slot management, and kitchen pages via `get_current_screen()->add_help_tab()`.
  - Test: Help tab visible on each PizzaPilot admin page.

- [ ] **8.2** Add plugin action links
  - What: Add "Settings" and "Help" links to the plugins list page via `plugin_action_links_` filter.
  - Test: Links visible on Plugins page, navigate correctly.

## Final Checks

- [ ] Run PHPCS -- `vendor/bin/phpcs --standard=WordPress-Extra .`
- [ ] Activate/deactivate with `WP_DEBUG` enabled -- no PHP notices
- [ ] Full checkout flow: add to cart, select delivery type + time slot, complete order
- [ ] Checkout with "Collection" -- no delivery radius validation
- [ ] Checkout with out-of-range postcode -- error message shown
- [ ] Kitchen page with multiple orders across slots
- [ ] Order cancellation releases slot
- [ ] Pro upsell messaging: same-day toggle, slot capacity, kitchen banner, date picker
- [ ] Admin pages render on mobile viewport
- [ ] Delete plugin via WP admin -- clean uninstall

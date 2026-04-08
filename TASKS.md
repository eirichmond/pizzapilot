# Task List: PizzaPilot Free Plugin

> Generated from: PRD.md + original tasks/tasks.json (reconciled)
> Date: 2026-04-07

## Overview

PizzaPilot Free is the foundation plugin for slot-based delivery and collection ordering via WooCommerce. This task list covers all remaining work for the free version. Tasks reference the original task IDs from `tasks/tasks.json` where applicable.

## Design Decisions

- **Slot management**: Free version auto-generates 30-minute slots from configured start/end times. No manual slot CRUD UI needed. Manual slot creation, future dates, recurring slots, and capacity controls are Pro features.
- **Slot tracking**: Auto-generated slots have no DB rows, so `order_id` tracking in `wp_pizzapilot_order_slots` is not applicable. The existing `pizzapilot_slot_booked` / `pizzapilot_slot_released` actions are sufficient for Pro to handle capacity.

## Completed Tasks (no further work needed)

- [x] **Task 1** -- Plugin Initialization and Structure Setup
- [x] **Task 2** -- Database Schema Creation (`wp_pizzapilot_order_slots`)
- [x] **Task 3** -- Admin Settings Panel (General/Delivery/Advanced tabs)
- [x] **Task 5** -- Time Slot Selection at Checkout (classic + block, auto-generated slots)
- [x] **Task 6** -- Pizza Quantity Deduction Logic (item counting, Pro hooks)
- [x] **Task 7** -- Basic Delivery Radius Checker (postcodes.io, Haversine, AJAX)
- [x] **Task 8** -- Kitchen Order Interface (order cards, completion toggle, CSS)
- [x] **Task 9** -- WooCommerce Integration (admin order display, HPOS compat, order columns, emails via additional fields API)
- [x] **Task 13** -- Order Display in WooCommerce Admin (meta box, delivery slot column, HPOS column hooks)

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

### 3. Pro Feature Teasers (Task 10 remaining)

- [ ] **3.1** Kitchen page Pro upsell banner
  - What: Dismissible notice below kitchen page title: "Upgrade to PizzaPilot Pro for live-updating orders, drag-and-drop reordering, and kitchen ticket printing." Track dismissal in user meta. Only show when Pro not active.
  - Test: Notice appears, dismiss it, doesn't reappear. Not shown when Pro class exists.

### 4. Internationalization Fixes (Task 14 remaining)

- [ ] **4.1** Fix i18n concatenation bugs
  - What: In `class-pizzapilot-settings.php`: fix `delivery_radius_callback()` (~line 505) to use `sprintf()` instead of concatenating inside `esc_html__()`. Fix `delivery_start_time_callback()` and `delivery_end_time_callback()` to not wrap `$upgrade_message` in `__()`.
  - Test: Run `wp i18n make-pot . languages/pizzapilot.pot` -- no warnings. Settings render correctly.

- [ ] **4.2** Fix untranslatable upgrade message
  - What: In `Pizzapilot_Helpers::pizzapilot_pro_upgrade_message()`, wrap the text portion in `__()` with `'pizzapilot'` text domain.
  - Test: String appears in generated `.pot` file.

- [ ] **4.3** Add missing DocBlock to `delivery_end_time_callback()`
  - What: Add DocBlock with `@since 1.0.0` and `@return void` matching sibling method.
  - Test: DocBlock present with proper tags.

### 5. Code Quality & WordPress.org Readiness

- [ ] **5.1** Audit and fix output escaping
  - What: Review every `echo` in the plugin for proper escaping (`esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`).
  - Test: Browse all admin pages with `WP_DEBUG` enabled -- no notices.

- [ ] **5.2** Ensure `$wpdb->prepare()` on all queries
  - What: Audit all `$wpdb->` calls across the plugin.
  - Test: Search for `$wpdb->` -- all parameterised.

- [ ] **5.3** Update `README.txt` to WordPress.org format
  - What: Replace boilerplate with real content: plugin name, contributors, tags, requires/tested versions, description, installation, FAQ, changelog.
  - Test: Validate at WordPress.org readme validator.

- [ ] **5.4** Add PHPCS configuration
  - What: Create `composer.json` with `wp-coding-standards/wpcs` dev dependency. Create `phpcs.xml.dist` targeting `WordPress-Extra` with `pizzapilot` text domain.
  - Test: `vendor/bin/phpcs` returns zero errors.

### 6. Documentation (Task 15)

- [ ] **6.1** Add contextual help tabs to admin screens
  - What: Add help tabs to settings and kitchen pages via `get_current_screen()->add_help_tab()`.
  - Test: Help tab visible on each PizzaPilot admin page.

- [ ] **6.2** Add plugin action links
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
- [ ] Pro upsell messaging: same-day toggle, kitchen banner
- [ ] Admin pages render on mobile viewport
- [ ] Delete plugin via WP admin -- clean uninstall

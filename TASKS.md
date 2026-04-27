# Task List: PizzaPilot Free Plugin

> Canonical task tracker for the free plugin. Reconciled against current source.
> Last reconciled: 2026-04-27

## Overview

PizzaPilot Free is the foundation plugin for slot-based delivery and collection ordering via WooCommerce. The free plugin is functionally complete: every feature in the PRD is implemented and verified in code. What remains is a single asset-cleanup task and the WordPress.org release-readiness pass.

## Design Decisions

- **Slot management**: Free version auto-generates 30-minute slots from configured start/end times. No manual slot CRUD UI. Manual slot creation, future dates, recurring slots, and capacity controls are Pro features.
- **Slot tracking**: Auto-generated slots have no DB rows, so `order_id` tracking in `wp_pizzapilot_order_slots` is not used by the free version. The `pizzapilot_slot_booked` / `pizzapilot_slot_released` actions provide everything Pro needs to manage capacity.

## Completed (13/15 + 1 deferred to Pro)

- [x] **Task 1** — Plugin Initialization and Structure Setup
- [x] **Task 2** — Database Schema Creation (`wp_pizzapilot_order_slots`)
- [x] **Task 3** — Admin Settings Panel (General / Delivery / Advanced tabs)
- [x] **Task 5** — Time Slot Selection at Checkout (classic + block, auto-generated slots)
- [x] **Task 6** — Pizza Quantity Deduction Logic (item counting, double-booking guard, Pro hooks)
- [x] **Task 7** — Basic Delivery Radius Checker (postcodes.io, Haversine, AJAX, transient caching)
- [x] **Task 8** — Kitchen Order Interface (order cards, completion toggle, responsive CSS, conditional enqueue, help tab)
- [x] **Task 9** — WooCommerce Integration (HPOS-safe meta, order columns, admin meta box, emails via additional fields API)
- [x] **Task 10** — Pro Feature Teasers (kitchen banner, settings tooltips, dedicated upgrade page, plugin-row action link)
- [x] **Task 11** — Plugin Activation & Dependency Checks (WooCommerce check, default options, full `uninstall.php` cleanup, `settings/index.php`, real DocBlocks)
- [x] **Task 13** — Order Display in WooCommerce Admin (meta box + delivery slot column with HPOS hooks)
- [x] **Task 14** — Internationalization and Localization (text domain loaded, all i18n concatenation bugs resolved, translator comments present, helpers translatable)
- [x] **Task 15** — Documentation and Help Resources (settings + kitchen help tabs, plugin action links, WordPress.org-format README)

- [—] **Task 4** — Time Slot Management Interface — **deferred to Pro** by design.

## Outstanding Feature Work

### Task 12 — Frontend Assets and Styling

- [x] **12.1** Decide on `public/css/pizzapilot-public.css`
  - What: Removed. The empty placeholder stylesheet was deleted along with the `public/css/` directory. `Pizzapilot_Public::enqueue_styles()` is now a documented no-op so the loader hook still fires (lets us add public CSS later without editing the loader). The delivery-checker block ships its own scoped styles via `block.json`.
  - Test: With `WP_DEBUG` on, browse any front-end page and confirm no `pizzapilot-public.css` is requested.

- [x] **12.2** Conditional public asset enqueue
  - What: `Pizzapilot_Public::enqueue_scripts()` now only enqueues `pizzapilot-public.js` when `Pizzapilot_Public::is_checkout_context()` returns true — i.e. on `is_cart()`, `is_checkout()`, or any singular page containing the `woocommerce/cart` or `woocommerce/checkout` block. The shared `pizzapilot-postcode-api` helper is still **registered** unconditionally so the delivery-checker block can resolve it as a viewScript dependency wherever the block lives. The legacy stylesheet is gone, so there's nothing else to gate.
  - Test: Browse a product page and the homepage with devtools' network tab open — `pizzapilot-public.js` is not requested. Cart, checkout, and any page containing the delivery-checker block still load `pizzapilot-postcode-api.js`. The postcode validator works on cart and checkout. The block works anywhere it's placed.

### Task 16 — Delivery Postcode Checker Block (new feature)

A Gutenberg block that renders a location-pin icon button. Clicking it opens a modal where the customer enters their postcode; the same AJAX endpoint used by the checkout postcode checker validates whether delivery is available. Free-version scope: hardcoded translatable copy, no persistence, icon-only trigger, no checkout link. Editor-side block-attribute customisation of the prompt and yes/no copy is deferred to the Pro plugin.

**Decisions confirmed (2026-04-27):**
- Block category: `woocommerce`
- Trigger: location-pin dashicon, icon only (no label)
- Copy: hardcoded translatable strings (free); editor-configurable in Pro
- Persistence: none — modal always starts in the prompt state
- Failure CTA: plain message, no link to checkout
- Logic: reuse the existing `pizzapilot_postcode_check` AJAX endpoint and the front-end check logic currently in `public/js/pizzapilot-public.js`

**Sub-tasks:**

- [x] **16.1** Register the block
  - What: Created `blocks/delivery-checker/block.json` (apiVersion 3, name `pizzapilot/delivery-checker`, woocommerce category, location dashicon, editorScript/viewScript/style). Supports include `align`, `spacing` (margin + padding), and `color.background` + `color.text` (link disabled). Block markup lives in `blocks/delivery-checker/render.php`; editor preview is a `wp.serverSideRender` wrapper in `blocks/delivery-checker/edit.js` (no JSX/build needed). Registration is centralised in `includes/class-pizzapilot-blocks.php` and hooked to `init` from `define_public_hooks()`. Each future block: drop a folder under `blocks/`, add a single `register_block_type()` call to `Pizzapilot_Blocks::register_blocks()`.
  - CSS: The wrapper takes the same circular size and shape as the trigger so the user's chosen background colour fills the visible circle (not a square halo behind it); the text colour cascades through `currentColor` to the SVG fill and the trigger's border.
  - Test: Verify the block appears under WooCommerce in the block inserter, the editor renders a server-side preview, the sidebar exposes Color (background + text), and saving a page renders the icon button on the front end with the chosen colours applied to the circle.

- [x] **16.2** Render the trigger and modal markup
  - What: `render.php` now outputs the trigger button (with `aria-controls` pointing at the dialog id and `aria-expanded="false"`) plus a `role="dialog" aria-modal="true"` overlay/panel containing all three state containers (prompt / success / failure). Each block instance gets a unique id via `wp_unique_id()` so multiple blocks on a page don't clash on labelling. Modal is initially `hidden`; only the prompt state is visible inside it (success and failure are also `hidden`). Close button, overlay-click, and reset-button hooks all carry `data-pizzapilot-*` attributes for view.js to target in 16.3/16.4. Strings flow through the `pizzapilot_delivery_checker_block_strings` filter so Pro can override every label without touching free-side markup (this also satisfies 16.8).
  - Test: View page source — the dialog markup is present but `hidden`. The trigger has `aria-controls` pointing at the dialog id. Each block instance has a unique id. With CSS only (no JS), opening the modal by removing `hidden` in devtools shows a centred panel with the prompt state visible and the other two states hidden via `[hidden]`.

- [x] **16.3** Modal interaction (open/close, focus trap, a11y)
  - What: `view.js` initialises every `.pizzapilot-delivery-checker` block on the page (independent per instance) and wires: trigger click → open, close button / overlay click / Escape → close, Tab/Shift-Tab cycle → focus trap inside the dialog, focus restoration to the trigger on close. Reset buttons in success/failure return the modal to the prompt state and refocus the postcode input. A `pizzapilot-delivery-checker--scroll-locked` class is applied to `<body>` while any modal is open. Each block element exposes a small API (`block.pizzapilotDeliveryChecker = { showState, resetToPrompt, close, focusInput, elements }`) so Task 16.4's AJAX wiring can move between states without re-implementing this logic.
  - Test: Keyboard-only navigation cycles inside the dialog; Escape closes; clicking the overlay closes; closing returns focus to the trigger; opening a second time starts in the prompt state with the input cleared; multiple instances on a page work independently.

- [x] **16.4** Wire postcode check to existing AJAX endpoint
  - What: Created `public/js/pizzapilot-postcode-api.js` exposing `window.pizzapilotPostcode.check( postcode )` as a Promise that resolves to `{ eligible, message, data }` and rejects on transport / non-success. AJAX action is `pizzapilot_check_postcode`; nonce is `pizzapilot_postcode_check`. The handle is registered as `pizzapilot-postcode-api` in `Pizzapilot_Public::enqueue_scripts()` with the `pizzapilotPublic` localisation attached, so any consumer that depends on this handle (the checkout script and the block's viewScript) gets `ajaxUrl` + `nonce` without re-localising. `pizzapilot-public.js` was refactored to call the helper instead of doing the fetch itself. `block.json`'s `viewScript` now lists `["pizzapilot-postcode-api", "file:./view.js"]` so the block automatically loads the helper. The block's view.js form submit calls the helper, sets a loading state (disables submit + input, toggles `is-loading` on the modal, sets `aria-busy`), then transitions to the success/failure state on resolve, or surfaces an error in the prompt's `[data-pizzapilot-error]` element on reject. Reset clears the error and re-enables the form.
  - Test: Submitting an in-range postcode → success state with thumbs-up. Out-of-range → failure state with thumbs-down. Empty postcode → no submit, focus stays in input. Existing checkout postcode validation still works unchanged. Network failure → error message in the prompt state, modal stays open, form re-enabled.

- [x] **16.5** Render the three states with hardcoded translatable copy
  - What: Done as part of 16.2. All copy is in render.php behind the `pizzapilot_delivery_checker_block_strings` filter — heading, postcode label/placeholder, submit, success, failure, reset, close. SVG icons (location-pin trigger, close X, thumbs-up success, thumbs-down failure) are inline so dashicons CSS isn't required on the front end.
  - Test: Reveal each state by removing `[hidden]` in devtools and confirm copy matches the spec; regenerate the `.pot` and confirm every string appears with the `pizzapilot` text domain.

- [x] **16.6** Block styles
  - What: Polished `style.css` with: scoped `box-sizing` reset (everything under `.pizzapilot-delivery-checker` and `.pizzapilot-delivery-checker__modal` is `border-box`), explicit `appearance: none` on buttons/inputs to neutralise theme resets, inherited typography (`font: inherit`, line-height defaults). Trigger gets a subtle hover-lift + focus ring. Modal: darker overlay, larger shadow, rounded panel, max-height + scroll for short viewports, mobile breakpoint at 30rem stacks the form vertically and grows the close button to 44px. Success/failure icons sit inside soft tinted circles. Loading: when view.js sets `aria-busy="true"` on the submit button, a CSS-only spinner replaces the label (no markup change). Animations: fade-in on the modal + pop-in on the panel, scoped via `:not([hidden])` so they run once on appear. `prefers-reduced-motion` kills transitions, panel animation, and the spinner spin.
  - Test: Visual check on Casa Gees and a default block theme — wrapper, button, modal, and three states all look correct; mobile viewport stacks form vertically; spinner shows while a request is in flight; reduced-motion preference removes all animation; no theme buttons or inputs bleed into the modal.

- [x] **16.7** Conditional asset enqueue
  - What: Done as part of 12.2. WordPress's block API auto-enqueues `block.json`'s `viewScript` (the array `["pizzapilot-postcode-api", "file:./view.js"]`) and `style` only on pages where the block actually renders. The shared API helper handle is registered unconditionally in `Pizzapilot_Public::enqueue_scripts()` so the block can pull it in anywhere; the legacy `pizzapilot-public.js` is gated behind `is_checkout_context()` so it only loads on cart/checkout. No further changes needed beyond what 12.2 already shipped.
  - Test: Insert the block on a non-checkout page → page loads `pizzapilot-postcode-api.js`, `view.js`, and the block's `style.css`, but **not** `pizzapilot-public.js`. Remove the block → none of those load. Cart/checkout pages still load `pizzapilot-public.js` (with `pizzapilot-postcode-api.js` resolved as its dependency).

- [x] **16.8** Pro hook for editor-configurable copy (free-side filter only)
  - What: Done as part of 16.2. `pizzapilot_delivery_checker_block_strings` wraps the strings array in render.php and is documented inline. Pro can later hook this filter to swap in block-attribute values from the editor sidebar without modifying free-side markup.
  - Test: Add a temporary `add_filter( 'pizzapilot_delivery_checker_block_strings', fn( $s ) => array_merge( $s, [ 'success_message' => 'CUSTOM' ] ) );` and confirm the override renders.

**Files this task introduces or touches:**
- `blocks/index.php` (silence file — created in 16.1)
- `blocks/delivery-checker/block.json` (created in 16.1)
- `blocks/delivery-checker/render.php` (created in 16.1; modal markup added in 16.2)
- `blocks/delivery-checker/edit.js` (created in 16.1)
- `blocks/delivery-checker/view.js` (stub created in 16.1; real interaction in 16.3 / 16.4)
- `blocks/delivery-checker/style.css` (minimal trigger styles in 16.1; full styles in 16.6)
- `includes/class-pizzapilot-blocks.php` (created in 16.1 — registration hub for all blocks)
- `public/js/pizzapilot-postcode-api.js` (new — shared postcode-check helper, Task 16.4)
- `public/js/pizzapilot-public.js` (refactor to use the shared helper, Task 16.4)
- `includes/class-pizzapilot.php` (require + `init` hook wired in 16.1)
- Settings/admin: none

## Release-Readiness Checklist (WordPress.org submission)

- [x] Run `vendor/bin/phpcs --standard=WordPress-Extra .` — 24 files, zero errors, zero warnings (last run 2026-04-27).
- [ ] Activate / deactivate / uninstall with `WP_DEBUG` and `WP_DEBUG_LOG` enabled — no PHP notices, options removed, table dropped, transients cleared on uninstall.
- [ ] Full checkout regression: classic + block checkout × delivery + collection × in-range + out-of-range postcode × slot-cutoff edge case.
- [ ] Kitchen page with multiple orders across slots; completion toggle round-trips; banner dismissal persists per-user.
- [ ] Settings persistence: change every field, save, reload — values intact and translated correctly.
- [ ] Plugins screen action links navigate correctly (Settings, Upgrade to Pro).
- [ ] README.txt validates against the WordPress.org readme validator.
- [ ] Final visual check on mobile viewport for kitchen + settings pages.

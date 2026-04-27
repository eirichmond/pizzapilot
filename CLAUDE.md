# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Plugin Overview

**PizzaPilot (Free)** is the core foundation plugin for a WordPress + WooCommerce slot-based delivery and collection service designed for local pizzerias. This is the FREE version that provides essential delivery setup and basic kitchen workflow tools.

**Important**: This plugin is part of a **freemium suite**. PizzaPilot Pro is a separate premium addon that extends this free version with advanced features.

## Plugin Architecture

### Foundation Principle
**PizzaPilot Free must always be installed as the foundation.** PizzaPilot Pro extends functionality using WordPress-native hooks and filters to avoid duplication.

### Technical Patterns

- **Extensibility via Filters**: `apply_filters('pizzapilot_time_slots', $slots)` and `apply_filters('pizzapilot_geocode_postcode', null, $postcode)` used throughout to allow Pro overrides
- **Pro Detection**: `class_exists('PizzaPilot_Pro')` check used to conditionally enable/disable features
- **Modular Class Loading**: `PizzaPilot\Features\FutureOrderToggle` pattern
- **WordPress Standards**: Settings API for admin settings, WP hooks and actions
- **WooCommerce Integration**: Hooks used for injecting slot selector at checkout

### Plugin Constants

```php
PIZZAPILOT_VERSION       // Plugin version
PIZZAPILOT_PLUGIN_DIR    // Plugin directory path
PIZZAPILOT_PLUGIN_URL    // Plugin URL
```

## Free Version Features

### 1. Time Slot-Based Ordering
- Manual creation of **same-day slots only** (start/end times)
- **Unlimited orders per slot** (no capacity limits in free version)
- Applies to both delivery and collection types
- Slot selection UI on WooCommerce checkout
- Slots stored as custom database table: `{prefix}_pizzapilot_order_slots`

### 2. Delivery Radius Logic
- Admin defines delivery location (postcode)
- Admin sets static delivery radius (km or miles)
- Customer postcode input field validates range
- If outside range, fallback to "Collection only"
- Uses **postcodes.io** API for geocoding (free, no API key, UK postcodes only)
- Distance calculated using **Haversine formula** for real-world accuracy
- Geocoded coordinates are cached via WordPress transients (30 days)
- Geocoding provider is swappable via `pizzapilot_geocode_postcode` filter (Pro uses Google Maps for global support)

### 3. Same-Day Ordering Only
- Customers can only book time slots for **today**
- Future date selection is a **Pro feature**
- Setting shown in admin but greyed out with Pro upsell CTA

### 4. Kitchen UI (Basic)
- Admin screen shows all orders for today grouped by slot
- Columns: Customer name, items, time slot
- Checkbox to mark as completed (manual refresh required)
- **No live updates or drag-and-drop** (Pro features)

### 5. Settings UI
Via WordPress Settings API:
- Delivery postcode
- Delivery radius (km/miles)
- Create/edit/delete manual slots
- Toggle for same-day ordering (disabled in Free, shows Pro upsell)

### 6. WooCommerce Integration
- Slot stored as order meta: `_pizzapilot_time_slot_id`
- Displayed in WooCommerce admin panel
- Works with all WooCommerce products and payment gateways
- Hooks into checkout process for slot selection validation

## Database Schema

### Table: `wp_pizzapilot_order_slots`

```sql
slot_id         bigint(20)      PRIMARY KEY, AUTO_INCREMENT
date            date            NOT NULL
start_time      time            NOT NULL
end_time        time            NOT NULL
availability    int(11)         NOT NULL DEFAULT 0  (unlimited in free version)
order_id        bigint(20)      DEFAULT NULL
created_at      datetime        NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at      datetime        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

INDEXES:
- PRIMARY KEY (slot_id)
- KEY date (date)
- KEY order_id (order_id)
```

## Task Management

### Canonical Tracker
`TASKS.md` (plugin root) is the single source of truth for free-plugin work. It lists completed tasks, outstanding feature work, and the WordPress.org release-readiness checklist.

**Always read `TASKS.md` before starting any free-plugin work** and update it (mark items completed, add remaining notes) when you finish a task.

The original per-task spec files (`tasks/task_001.txt`–`task_015.txt`) and `tasks/tasks.json` were removed once the free plugin reached feature-complete; their content is captured in `TASKS.md` and the implementation itself.

## Key Classes and Structure

### Main Plugin File
`pizzapilot.php` - Plugin header, activation/deactivation hooks, initialization

### Class Structure
```
includes/
├── class-pizzapilot.php              // Main plugin class
├── class-pizzapilot-loader.php       // Hooks loader
├── class-pizzapilot-i18n.php         // Internationalization
├── class-pizzapilot-helpers.php      // Helper functions
├── class-pizzapilot-activator.php    // Activation logic
└── class-pizzapilot-deactivator.php  // Deactivation logic

admin/
├── class-pizzapilot-admin.php        // Admin area functionality
└── partials/                         // Admin templates

public/
├── class-pizzapilot-public.php       // Public-facing functionality
└── partials/                         // Frontend templates

settings/
└── class-pizzapilot-settings.php     // Settings management
```

## Development Guidelines

### When Adding Features

1. **Check if it's a Pro feature**: Review the PRD feature comparison table
2. **Use filters for extensibility**: Allow Pro to override behavior
3. **Check for Pro version**: Use `class_exists('PizzaPilot_Pro')` when needed
4. **Follow WordPress standards**: Settings API, nonces, sanitization, escaping
5. **Update task status**: Mark items completed in `TASKS.md`

### Pro Feature Indicators

In the free version, Pro features should be:
- **Visible but disabled** in the UI
- **Clearly marked** with "PRO" badges
- **Linked to upgrade page** with clear CTA
- **Explained via tooltips** showing the benefit

Example Pro features that should show as disabled:
- Future date selection (greyed out date picker)
- Slot capacity controls (view-only fields)
- Auto-generation of recurring slots
- Drag-and-drop kitchen UI elements

### WooCommerce Compatibility

- Minimum WooCommerce version: 3.0
- Tested up to: 7.0
- Must check for WooCommerce on activation
- Use WooCommerce hooks: `woocommerce_before_order_notes`, `woocommerce_checkout_process`, etc.

## Freemium Strategy

### What's Free
- ✅ Manual slot creation (today only)
- ✅ Unlimited orders per slot
- ✅ Fixed delivery radius from 1 postcode
- ✅ Geocoding via postcodes.io (UK postcodes, free, no API key)
- ✅ Basic kitchen UI with checkboxes
- ✅ Collection/delivery unified logic

### What Requires Pro
- ❌ Future dates, auto-generation, recurring logic
- ❌ Slot capacity limits by order count or pizza quantity
- ❌ Multiple origins, variable radii, override rules
- ❌ Global geocoding via Google Maps API (supports any country)
- ❌ Interactive Mapbox delivery maps (checkout + admin) with What3Words
- ❌ Live kitchen queue, drag & drop, responsive UI
- ❌ Driver delivery interface with maps
- ❌ Separate capacities for collection vs delivery
- ❌ SMS/Email notifications

## Testing Requirements

Each feature should be tested for:
1. **Activation/deactivation** without errors
2. **WooCommerce dependency** check works
3. **Database table creation** and structure
4. **Settings persistence** and validation
5. **Frontend checkout flow** with slot selection
6. **Admin UI rendering** and functionality
7. **Order meta storage** and display
8. **Compatibility** with WooCommerce themes
9. **Pro upsell messaging** displays correctly

## Distribution Strategy

- **Free plugin**: WordPress.org repository
- **License**: GPLv2 or later
- **Text Domain**: `pizzapilot`
- **Requires**: WordPress 5.0+, PHP 7.2+, WooCommerce 3.0+

## WordPress Coding Standards - CRITICAL

**IMPORTANT**: Both PizzaPilot (Free) and PizzaPilot Pro will be submitted to the WordPress.org plugin repository. This means **ALL code must strictly adhere to WordPress Coding Standards**.

### Mandatory Standards

All code must follow:
- **[WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)**
- **[WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)**
- **[WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)**
- **[WordPress Accessibility Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/accessibility/)**

### Code Quality Tools

Use these tools before committing any code:

```bash
# Install PHP_CodeSniffer with WordPress standards
composer require --dev wp-coding-standards/wpcs
composer require --dev dealerdirect/phpcodesniffer-composer-installer

# Run PHPCS to check code
vendor/bin/phpcs --standard=WordPress /path/to/file.php

# Auto-fix fixable issues
vendor/bin/phpcbf --standard=WordPress /path/to/file.php

# Check for WordPress.org specific requirements
vendor/bin/phpcs --standard=WordPress-Extra /path/to/file.php
```

### Key Requirements for WordPress.org Submission

1. **Security**
   - All user input must be sanitized: `sanitize_text_field()`, `sanitize_email()`, `absint()`, etc.
   - All output must be escaped: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses()`, etc.
   - Use nonces for all form submissions: `wp_nonce_field()`, `wp_verify_nonce()`
   - Prepare all database queries: `$wpdb->prepare()`

2. **Internationalization**
   - All strings must be translatable: `__()`, `_e()`, `_n()`, `_x()`, `esc_html__()`, etc.
   - Use proper text domain: `'pizzapilot'` (not hardcoded strings)
   - Load text domain properly: `load_plugin_textdomain()`

3. **Prefixing**
   - All functions: `pizzapilot_function_name()`
   - All classes: `PizzaPilot_Class_Name` or `PizzaPilot\Namespace\Class_Name`
   - All hooks: `pizzapilot_hook_name`
   - All options: `pizzapilot_option_name`
   - All database tables: `{$wpdb->prefix}pizzapilot_table_name`
   - All CSS classes: `pizzapilot-class-name`
   - All JavaScript variables: `pizzapilot_js_object`

4. **No External Dependencies**
   - Cannot use external CDNs for libraries
   - Must bundle all assets locally
   - No tracking scripts or analytics without explicit user consent
   - No "phoning home" without disclosure

5. **File Structure**
   - `readme.txt` must follow WordPress.org format
   - Include `license.txt` for GPL
   - Proper plugin headers in main file
   - Use `index.php` files in all directories for security

6. **Performance**
   - No code execution on every page load unless necessary
   - Use transients for caching: `set_transient()`, `get_transient()`
   - Enqueue scripts/styles only where needed
   - Optimize database queries (avoid N+1 queries)

### Code Examples - Correct vs Incorrect

**Security - Output Escaping:**
```php
// ❌ WRONG - XSS vulnerability
echo '<p>' . $user_input . '</p>';

// ✅ CORRECT
echo '<p>' . esc_html( $user_input ) . '</p>';
```

**Security - Input Sanitization:**
```php
// ❌ WRONG - SQL injection vulnerability
$wpdb->query( "SELECT * FROM $table WHERE slot_id = " . $_POST['slot_id'] );

// ✅ CORRECT
$slot_id = absint( $_POST['slot_id'] );
$wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE slot_id = %d", $slot_id ) );
```

**Internationalization:**
```php
// ❌ WRONG
echo 'Delivery Time Slot';

// ✅ CORRECT
echo esc_html__( 'Delivery Time Slot', 'pizzapilot' );
```

**Nonces:**
```php
// ❌ WRONG - No nonce verification
if ( $_POST['action'] === 'save_slot' ) {
    save_slot();
}

// ✅ CORRECT
if ( isset( $_POST['pizzapilot_nonce'] ) && wp_verify_nonce( $_POST['pizzapilot_nonce'], 'pizzapilot_save_slot' ) ) {
    save_slot();
}
```

### WordPress.org Review Checklist

Before submitting to WordPress.org, ensure:

- [ ] All code passes `phpcs --standard=WordPress-Extra`
- [ ] No PHP errors or warnings with `WP_DEBUG` enabled
- [ ] All user inputs are sanitized
- [ ] All outputs are escaped
- [ ] All strings are internationalized
- [ ] All database queries use `$wpdb->prepare()`
- [ ] All forms use nonces
- [ ] No external HTTP requests without user consent
- [ ] No obfuscated code
- [ ] No encoded/serialized data in options (use arrays)
- [ ] Proper uninstall.php for cleanup
- [ ] readme.txt follows WordPress.org format
- [ ] Tested with `WP_DEBUG_LOG` for any notices
- [ ] All assets are local (no CDNs)
- [ ] Follows semantic versioning

### Common WordPress.org Rejection Reasons to Avoid

1. **Security issues**: Missing sanitization, escaping, or nonce checks
2. **Using generic function/class names**: Not prefixed properly
3. **Direct database access**: Not using `$wpdb` or missing `prepare()`
4. **Calling files directly**: Not checking for `ABSPATH`
5. **Poor internationalization**: Hardcoded strings
6. **External dependencies**: Loading scripts from CDNs
7. **Undisclosed data collection**: Tracking without consent

### Resources

- [Plugin Handbook](https://developer.wordpress.org/plugins/)
- [Plugin Review Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)
- [Common Guideline Violations](https://developer.wordpress.org/plugins/wordpress-org/common-violations/)
- [PHPCS WordPress Rulesets](https://github.com/WordPress/WordPress-Coding-Standards)

## Common Development Tasks

### Activate Plugin Locally
Plugin should auto-activate via WordPress admin. Check for WooCommerce dependency.

### Create a Time Slot
Navigate to WooCommerce > Time Slots, add new slot with start/end time and date (today only).

### Test Checkout Flow
1. Add products to cart
2. Proceed to checkout
3. Verify time slot dropdown appears
4. Select a slot and complete order
5. Check order meta contains `_pizzapilot_time_slot_id`

### View Kitchen Orders
Navigate to Kitchen Orders menu item, verify orders grouped by time slot.

### Debug
- Enable `WP_DEBUG` in wp-config.php
- Check error logs in wp-content/debug.log
- Review browser console for JS errors

## Important Notes

- **Never duplicate Pro code**: Use filters and hooks to allow Pro to extend
- **Keep it simple**: Free version focuses on core value proposition
- **Mobile-first design**: Kitchen staff use tablets/phones
- **Performance**: Efficient database queries, no unnecessary API calls in free version
- **Upsell strategically**: Don't be annoying, show value of Pro features

## Related Files

- `/pizza_pilot_full_prd.md` - Complete product requirements document
- `/wp-content/plugins/pizzapilot-pro/` - Pro version (separate plugin)
- Root `CLAUDE.md` - Casa Gees theme development context

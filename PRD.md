# PizzaPilot Plugin Suite -- Product Requirements Document (PRD)

**Project:** PizzaPilot Plugin Suite
**Author:** Business in a Pizza Box
**Platform:** WordPress + WooCommerce
**Model:** Freemium (Free core plugin + Pro addon plugin)
**Last Updated:** 2026-04-07

---

## 1. Purpose

PizzaPilot is a WordPress plugin designed to help local pizzerias run an efficient, slot-based delivery and collection service through WooCommerce. The plugin is split into a Free and Pro version, with the Free plugin providing essential delivery setup and the Pro plugin unlocking advanced logistics, kitchen workflows, and capacity controls.

### Target User

Independent or small pizza businesses that:

- Take pre-orders via WooCommerce
- Offer local delivery and/or collection
- Need basic slot/time-based kitchen management

---

## 2. Plugin Architecture

### Suite Structure

| Plugin | Slug | Location | Description |
|--------|------|----------|-------------|
| **PizzaPilot (Free)** | `pizzapilot` | `wp-content/plugins/pizzapilot/` | Core plugin with foundational slot scheduling, delivery radius logic, and basic kitchen UI |
| **PizzaPilot Pro** | `pizzapilot-pro` | `wp-content/plugins/pizzapilot-pro/` | Premium addon that extends the free version with capacity controls, future ordering, driver delivery tools, and advanced admin interfaces |

### Foundation Principle

PizzaPilot Free **must always be installed** as the foundation. PizzaPilot Pro extends functionality using WordPress-native hooks and filters to avoid code duplication.

### Technical Patterns

- `apply_filters('pizzapilot_time_slots', $slots)` -- used throughout to allow Pro overrides
- `apply_filters('pizzapilot_geocode_postcode', null, $postcode)` -- allows Pro to swap geocoding provider (postcodes.io to Google Maps)
- `class_exists('PizzaPilot_Pro')` check used in Free to conditionally enable/disable features
- Modular class loading (e.g. `PizzaPilot\Features\FutureOrderToggle`)
- Follows WordPress Settings API for admin settings
- WooCommerce hooks used for injecting slot selector at checkout

### Plugin Constants

**Free:**
```
PIZZAPILOT_VERSION
PIZZAPILOT_PLUGIN_DIR
PIZZAPILOT_PLUGIN_URL
```

**Pro:**
```
PIZZAPILOT_PRO_VERSION
PIZZAPILOT_PRO_PLUGIN_DIR
PIZZAPILOT_PRO_PLUGIN_URL
PIZZAPILOT_PRO_LICENSE_KEY
```

---

## 3. PizzaPilot (Free) -- Features

### 3.1 Time Slot-Based Ordering

- Admin manually defines same-day delivery slots (e.g. 17:00-17:30)
- Each slot has a maximum pizza quantity allowed
- Slots stored in database table `{prefix}_pizzapilot_order_slots`
- Applies to both delivery and collection types
- Slot selection UI on WooCommerce checkout
- Only shows available slots for the current day

**Database Schema:**

| Column | Type | Description |
|--------|------|-------------|
| `slot_id` | `bigint(20)` PK AUTO_INCREMENT | Unique slot identifier |
| `date` | `date` NOT NULL | Slot date |
| `start_time` | `time` NOT NULL | Slot start time |
| `end_time` | `time` NOT NULL | Slot end time |
| `availability` | `int(11)` NOT NULL DEFAULT 0 | Pizza quantity remaining (unlimited in free = 0) |
| `order_id` | `bigint(20)` DEFAULT NULL | Associated WooCommerce order |
| `created_at` | `datetime` NOT NULL | Creation timestamp |
| `updated_at` | `datetime` NOT NULL | Last update timestamp |

**Indexes:** PRIMARY KEY (`slot_id`), KEY `date` (`date`), KEY `order_id` (`order_id`)

### 3.2 Pizza Quantity Deduction per Slot

- Slot pizza count (availability) decreases based on quantity added to cart
- When slot reaches zero, it becomes unavailable to new customers
- Automatic disabling of fully booked slots
- Customers prompted to select the next available slot

### 3.3 Same-Day Ordering Only

- Customers can only book time slots for **today**
- Future dates are disabled in the free version
- Setting toggle is visible but greyed out with Pro upsell CTA ("Unlock this feature with PizzaPilot Pro")

### 3.4 Delivery Radius Logic

- Admin defines delivery location (postcode)
- Admin sets static delivery radius (km or miles)
- Customer postcode input field validates range
- If outside range, fallback to "Collection only"
- Geocoding via **postcodes.io** API (free, no API key, UK postcodes only)
- Distance calculated using **Haversine formula** for real-world accuracy
- Geocoded coordinates cached via WordPress transients (30 days)
- Geocoding provider swappable via `pizzapilot_geocode_postcode` filter (Pro overrides with Google Maps)

### 3.5 Kitchen Order Interface (Basic)

- Admin-only screen in WP dashboard
- Orders grouped by time slot for today
- View includes: Customer name, items, slot time
- Checkbox to mark an order as completed
- Manual page refresh required
- **No live updates or drag-and-drop** (Pro features)

### 3.6 Settings UI (via WordPress Settings API)

- Enable/disable plugin features
- Delivery postcode
- Delivery radius (km or miles)
- Create/edit/delete manual time slots
- Assign max pizza per slot
- View-only toggle: "Same-Day Delivery Only" (locked in Free, shows Pro upsell)

### 3.7 WooCommerce Integration

- Uses standard WooCommerce product types and cart/checkout flow
- Slot stored as order meta: `_pizzapilot_time_slot_id`
- Displayed in WooCommerce admin order details panel
- Works with all WooCommerce products and payment gateways
- Hooks into checkout process for slot selection validation
- WooCommerce hooks used: `woocommerce_before_order_notes`, `woocommerce_checkout_process`, etc.

---

## 4. PizzaPilot Pro -- Additional Features

PizzaPilot Pro requires PizzaPilot (Free) to be installed and active. It declares this dependency in its plugin header via `Requires Plugins: pizzapilot`.

### 4.1 Future Order Scheduling

**Extends:** Free version's same-day-only limitation

- Date picker on checkout (with min/max advance days configurable)
- Dynamic generation of slots per date
- Day-specific rules (e.g. Saturday = more/different slots)
- Calendar UI for viewing future bookings
- **Implementation:** Hook into `pizzapilot_available_dates` filter

### 4.2 Slot Capacity Controls

**Extends:** Free version's unlimited orders per slot

- Set max orders per slot OR max pizza quantities per slot
- Separate capacities for delivery vs collection
- Slot auto-locks when capacity is reached
- Remaining quantity dynamically deducted based on cart contents
- Visual capacity indicators in admin
- **Database:** Extends `pizzapilot_order_slots` table with columns: `max_capacity`, `current_capacity`, `delivery_capacity`, `collection_capacity`
- **Implementation:** Hook into `pizzapilot_slot_availability` filter

### 4.3 Drag-and-Drop Kitchen UI

**Replaces:** Free version's static checkbox kitchen UI

- Live-updating prep dashboard (AJAX/WebSocket)
- Touch-screen ready for tablet use
- Order queue with time countdown to delivery slot
- Drag to reorder priority, mark complete, or delay
- Status columns: Pending -> Preparing -> Ready -> Out for Delivery
- Print functionality for kitchen tickets
- **Tech Stack:** React/Vue.js with REST API endpoints (bundled locally, no CDNs)

### 4.4 Driver Delivery Interface

**New Feature:** Not in free version

- Mobile-optimized UI for delivery drivers
- View assigned orders for their route
- Navigation integration:
  - Google Maps (via URL scheme)
  - Apple Maps (via URL scheme)
  - What3Words (optional integration)
- Customer location via lat/lon with drop pin
- Mark as delivered with optional photo capture (uses device camera)
- Delivery confirmation timestamp
- **Access Control:** Custom user role `delivery_driver` with capabilities: `read`, `view_delivery_orders`, `mark_delivered`

### 4.5 Notification System (Optional Module)

**New Feature:** Not in free version

- SMS or Email updates to customers:
  - Order confirmed
  - Being prepared
  - Out for delivery
  - Delivered
- **Integration:** Twilio for SMS (user provides own API credentials), WP Mail for email
- **Settings:** Toggle notifications on/off per event type
- **Template System:** Customizable message templates

### 4.6 Interactive Delivery Maps (Mapbox + What3Words)

**Extends:** Free version's postcode-only delivery validation

#### Admin Settings Map
- Interactive Mapbox map on the Delivery settings tab
- Admin clicks to set store delivery origin point
- Store coordinates (lat/lng) saved in `pizzapilot_delivery_settings`
- Only renders when Mapbox Access Token is configured

#### Frontend Checkout Map
- Interactive Mapbox map rendered after billing form on WooCommerce checkout
- Customer enters postcode -> geocoded via postcodes.io -> map flies to location + drops pin
- Customer clicks map to refine -> What3Words REST API converts coordinates to 3-word address
- Hidden fields capture: `pizzapilot_customer_lat`, `pizzapilot_customer_lng`, `pizzapilot_what3words`
- "Reset Location" button to clear pin and fields
- Graceful degradation to postcode-only when Mapbox token not configured

#### Distance Calculation
- When both store and customer coordinates are available, distance is calculated directly via Haversine (no geocoding API calls needed)
- Pro distance validator runs at priority 5, before free version's postcode geocoding at priority 10
- Falls back to free version's postcode-based geocoding if customer doesn't use the map

#### Order Meta Display
- Admin order page shows: What3Words address (clickable link to what3words.com), latitude, longitude
- Data stored as: `_pizzapilot_what3words`, `_pizzapilot_customer_lat`, `_pizzapilot_customer_lng`

#### Settings (Delivery Tab)
- `mapbox_access_token` -- Mapbox GL JS access token
- `what3words_api_key` -- What3Words REST API key
- `store_latitude` / `store_longitude` -- set via interactive map click

### 4.7 Global Geocoding via Google Maps

**Extends:** Free version's UK-only postcodes.io geocoding

- Replaces postcodes.io with Google Maps Geocoding API for global postcode/address support
- Admin provides their own Google Maps API key in Delivery settings tab
- Falls back to free version's postcodes.io when no API key is configured
- Uses `pizzapilot_geocode_postcode` filter provided by free version
- Coordinates cached via WordPress transients (30 days) to minimise API usage

### 4.8 Location Management

**Extends:** Free version's single delivery origin

- Add multiple delivery origin points (multi-location support)
- Variable radius by location (different postcodes, different radii)
- Day-of-week specific radii (wider on weekends, for example)
- Location-specific slot templates
- Map view of all delivery zones

### 4.9 Override Logic / Admin Controls

**Extends:** Free version's strict postcode validation

- Manually override out-of-zone postcodes (approve exceptions)
- Block specific time slots for holidays or breaks
- Custom slot capacity overrides for special events
- Blacklist specific postcodes
- **Permissions:** Only admin/shop manager roles

### 4.10 Auto-Generated Recurring Slots

**Extends:** Free version's manual slot creation

- Define slot templates (e.g. "Every Friday 5-6pm, 6-7pm, 7-8pm")
- Auto-generate slots X days in advance
- Day-of-week slot variations
- Batch slot creation UI
- **Cron Integration:** Uses WordPress cron for automation

---

## 5. Freemium Strategy Summary

| Feature | Free | Pro |
|---------|------|-----|
| Slot Scheduling | Manual (today only) | Future dates, auto-generation, recurring logic |
| Slot Capacity | Unlimited only | Set limits by order count or pizza qty |
| Delivery Radius | Fixed from 1 postcode (UK via postcodes.io) | Multiple origins, variable radii, override rules |
| Geocoding | postcodes.io (UK only, free) | Google Maps Geocoding API (global, user provides API key) |
| Delivery Maps | -- | Interactive Mapbox maps (checkout + admin) with What3Words |
| Kitchen UI | Static view, checkboxes | Live queue, drag & drop, responsive UI |
| Driver Tools | -- | Delivery interface, maps, navigation, pins |
| Collection/Delivery Logic | Unified | Separate capacities and logic |
| Notifications | -- | SMS/Email (via optional module) |
| Settings UI | View-only toggle for Pro features | Fully editable with conditional fields |

### Pro Feature Indicators in Free Version

In the free version, Pro features should be:
- **Visible but disabled** in the UI
- **Clearly marked** with "PRO" badges
- **Linked to upgrade page** with clear CTA
- **Explained via tooltips** showing the benefit

---

## 6. File Structure

### Free Plugin (`pizzapilot/`)

```
pizzapilot/
├── pizzapilot.php                        # Main plugin file
├── index.php                             # Security index
├── uninstall.php                         # Cleanup on uninstall
├── README.txt                            # WordPress.org readme
├── LICENSE.txt                           # GPLv2
├── CLAUDE.md                             # Development guidance
├── PRD.md                                # This document
├── includes/
│   ├── class-pizzapilot.php              # Main plugin class
│   ├── class-pizzapilot-loader.php       # Hooks loader
│   ├── class-pizzapilot-i18n.php         # Internationalization
│   ├── class-pizzapilot-helpers.php      # Helper functions
│   ├── class-pizzapilot-activator.php    # Activation logic
│   └── class-pizzapilot-deactivator.php  # Deactivation logic
├── admin/
│   ├── class-pizzapilot-admin.php        # Admin area functionality
│   └── partials/                         # Admin templates
├── public/
│   ├── class-pizzapilot-public.php       # Public-facing functionality
│   └── partials/                         # Frontend templates
├── settings/
│   └── class-pizzapilot-settings.php     # Settings management
├── languages/                            # Translation files
└── scripts/                              # Build/utility scripts
```

### Pro Plugin (`pizzapilot-pro/`)

```
pizzapilot-pro/
├── pizzapilot-pro.php                    # Main plugin file (Requires Plugins: pizzapilot)
├── index.php                             # Security index
├── uninstall.php                         # Cleanup on uninstall
├── README.txt                            # WordPress.org readme
├── LICENSE.txt                           # GPLv2
├── CLAUDE.md                             # Development guidance
├── includes/
│   ├── class-pizzapilot-pro.php              # Main pro plugin class
│   ├── class-pizzapilot-pro-loader.php       # Hooks loader
│   ├── class-pizzapilot-pro-geocoder.php     # Google Maps geocoding
│   ├── class-pizzapilot-pro-map-checkout.php # Mapbox checkout map
│   ├── class-pizzapilot-pro-map-distance.php # Coordinate-based Haversine validation
│   ├── class-pizzapilot-pro-license.php      # License validation
│   ├── class-pizzapilot-pro-dependency.php   # Free version dependency check
│   └── modules/
│       ├── future-ordering/
│       ├── capacity-controls/
│       ├── kitchen-ui/
│       ├── driver-tools/
│       ├── notifications/
│       ├── location-management/
│       └── admin-controls/
├── admin/
│   ├── class-pizzapilot-pro-admin.php        # Pro admin functionality
│   ├── class-pizzapilot-pro-settings.php     # Extends free settings
│   ├── js/pizzapilot-pro-settings-map.js     # Admin store location map
│   └── partials/                             # Pro admin templates
├── public/
│   ├── class-pizzapilot-pro-public.php       # Pro public functionality
│   ├── js/pizzapilot-pro-checkout-map.js     # Frontend Mapbox + W3W map
│   ├── css/pizzapilot-pro-checkout-map.css   # Checkout map styles
│   ├── img/pizzapilot-map-pin.png            # Map marker icon
│   └── partials/                             # Pro frontend templates
└── languages/                                # Translation files
```

---

## 7. REST API Endpoints (Pro)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/wp-json/pizzapilot-pro/v1/kitchen/orders` | Fetch kitchen orders |
| `POST` | `/wp-json/pizzapilot-pro/v1/kitchen/update-status` | Update order status |
| `GET` | `/wp-json/pizzapilot-pro/v1/driver/assigned-orders` | Get driver's assigned orders |
| `POST` | `/wp-json/pizzapilot-pro/v1/driver/mark-delivered` | Mark order as delivered |
| `GET` | `/wp-json/pizzapilot-pro/v1/slots/future/{date}` | Get future slots for date |
| `POST` | `/wp-json/pizzapilot-pro/v1/slots/bulk-create` | Bulk create slots |

All endpoints require authentication and proper capability checks.

---

## 8. WordPress Coding Standards

Both plugins target the **WordPress.org plugin repository**. All code must strictly adhere to:

- [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- [WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)
- [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)
- [WordPress Accessibility Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/accessibility/)

### Key Requirements

1. **Security:** Sanitize all input, escape all output, use nonces, use `$wpdb->prepare()`
2. **Internationalization:** All strings translatable. Free text domain: `pizzapilot`. Pro text domain: `pizzapilot-pro`
3. **Prefixing:** All functions, classes, hooks, options, tables, CSS classes, and JS variables must be prefixed with `pizzapilot_` (free) or `pizzapilot_pro_` (pro)
4. **No External Dependencies:** Bundle all assets locally, no CDNs. No tracking without consent
5. **Performance:** Enqueue assets only where needed, use transients for caching, optimise queries

### Code Quality Tools

```bash
composer require --dev wp-coding-standards/wpcs
composer require --dev dealerdirect/phpcodesniffer-composer-installer
vendor/bin/phpcs --standard=WordPress-Extra /path/to/file.php
vendor/bin/phpcbf --standard=WordPress /path/to/file.php
```

---

## 9. Testing Requirements

### Free Plugin

1. Activation/deactivation without errors
2. WooCommerce dependency check works
3. Database table creation and structure
4. Settings persistence and validation
5. Frontend checkout flow with slot selection
6. Admin UI rendering and functionality
7. Order meta storage and display (`_pizzapilot_time_slot_id`)
8. Compatibility with WooCommerce themes
9. Pro upsell messaging displays correctly
10. Slot availability/locking logic

### Pro Plugin

1. Fails gracefully without free version installed
2. License activation, deactivation, expiry scenarios
3. Slot locking at various capacities
4. Kitchen UI live updates, drag-and-drop on touch devices
5. Driver interface on mobile devices (iOS/Android)
6. SMS/email delivery with Twilio test credentials
7. Multi-location zone calculations with overlapping radii
8. Date picker, slot generation, capacity over time
9. Manual postcode override exceptions
10. Cron-based slot auto-generation
11. Activation/deactivation order (Pro first, Free second, etc.)
12. Settings merge correctly between free and pro
13. Database migrations when upgrading free -> pro

---

## 10. Distribution Strategy

| | Free | Pro |
|--|------|-----|
| **Platform** | WordPress.org repository | WordPress.org repository |
| **License** | GPLv2 or later | GPLv2 or later |
| **Text Domain** | `pizzapilot` | `pizzapilot-pro` |
| **Requires** | WordPress 5.0+, PHP 7.2+, WooCommerce 3.0+ | WordPress 5.0+, PHP 7.2+, WooCommerce 3.0+, PizzaPilot (Free) 1.0+ |

- Upsell messaging built into disabled UI fields and admin notices in the free version
- Pro declares dependency via plugin header: `Requires Plugins: pizzapilot`

---

## 11. Roadmap

| Phase | Deliverable | Status |
|-------|-------------|--------|
| Phase 1 | Build Free plugin with core slot & radius functionality | In Progress |
| Phase 2 | Develop Pro plugin with future ordering and slot caps | Planned |
| Phase 3 | Add driver tools, drag-and-drop kitchen queue | Planned |
| Phase 4 | Introduce SMS/email notifications as optional module | Planned |

---

## 12. Performance Considerations

### Kitchen Dashboard (Pro)
- Use WebSockets or long-polling for live updates
- Cache order queries
- Paginate large order lists
- Optimise for low-bandwidth mobile connections

### Capacity Calculations (Pro)
- Cache slot availability calculations
- Use database indexes on capacity columns
- Batch update operations when possible

### Notifications (Pro)
- Queue SMS/email sends (don't block checkout)
- Rate limit to avoid Twilio overages
- Retry failed sends with backoff

### General
- No code execution on every page load unless necessary
- Enqueue scripts/styles only where needed
- Use transients for caching geocoded coordinates (30 days)
- Optimise database queries (avoid N+1 queries)

---

## 13. Mobile-First Design

All UIs (especially Pro) prioritise mobile:
- Touch-friendly buttons (min 44x44px)
- Responsive layouts
- Offline capability where possible (driver app)
- Fast load times (< 3s on 3G)
- Kitchen staff primarily use tablets/phones

---

## 14. Third-Party Service Integration (Pro)

| Service | Usage | Credential Ownership |
|---------|-------|---------------------|
| **Twilio** | SMS notifications | User provides own SID/token |
| **Google Maps** | Global geocoding | User provides own API key |
| **Mapbox** | Interactive delivery maps | User provides own access token |
| **What3Words** | Precise delivery addressing | User provides own API key |
| **postcodes.io** | UK postcode geocoding (Free) | No key required |

All external API calls must handle failures gracefully. No data sent to external services without user configuration and knowledge.

---

## 15. WordPress.org Submission Checklist

- [ ] All code passes `phpcs --standard=WordPress-Extra`
- [ ] No PHP errors or warnings with `WP_DEBUG` enabled
- [ ] All user inputs are sanitized
- [ ] All outputs are escaped
- [ ] All strings are internationalized with correct text domain
- [ ] All database queries use `$wpdb->prepare()`
- [ ] All forms and AJAX use nonces
- [ ] No external HTTP requests without user consent/configuration
- [ ] No obfuscated code
- [ ] Proper `uninstall.php` for cleanup
- [ ] `readme.txt` follows WordPress.org format
- [ ] All assets are local (no CDNs)
- [ ] Follows semantic versioning
- [ ] REST API endpoints have proper permission callbacks
- [ ] Pro declares dependency on free version in plugin header
- [ ] Third-party API credentials are user-provided, not bundled

# Subscription System - Technical Implementation Guide

## Architecture Overview

```
Course Variations (Admin UI)
        ↓
   store-variations.php (Form + Nonce)
        ↓
   save_post_course hook
        ↓
   PayPal Plan Creation (paypal-api.php)
        ↓
   Store plan_id in postmeta
        ↓
   Frontend: PayPal Checkout Button
        ↓
   AJAX: handle_course_subscription (paypalsettings.php)
        ↓
   Database: wp_course_subscriptions table
        ↓
   Admin Dashboard: render_subscriptions_dashboard
```

---

## Step-by-Step Flow

### 1. Admin Creates Variation (Backend)

**File**: `more_functions/store-variations.php`

```
User creates variation:
- Label: "Pro Monthly"
- Price: 29.99
- Billing: "monthly"
- Clicks Save

↓ Form submitted with nonce ↓

Save handler: save_post_course hook
- Validates nonce
- Validates data (price > 0, label not empty)
- For MONTHLY/YEARLY:
  → Calls mm_pp_get_or_create_product()
  → Calls mm_pp_create_plan() with interval='MONTH'
  → Stores plan_id in postmeta: _course_variations
  → Returns plan_id back to form
  → Shows green checkmark: "✓ Plan linked"
```

### 2. Customer Makes Purchase (Frontend)

**Files**:
- `template-custom/auth/single-store-template.php` (HTML/JS)
- `more_functions/paypalsettings.php` (AJAX handler)

**For One-Time Payment**:
```javascript
// PayPal Client creates order
order = {
  intent: "CAPTURE",
  purchase_units: [{
    amount: { value: variation.price }
  }]
}

// User pays via PayPal
orderId = response.id  // "7C6234XX"

// AJAX: handle_course_purchase
→ Verifies order with PayPal
→ Gets order details
→ Checks amount matches variation price (fraud check)
→ Saves to user meta: purchased_courses
→ Records in purchase log
→ Returns success
```

**For Subscription (Monthly/Yearly/Weekly)**:
```javascript
// PayPal Client creates subscription
subscription = {
  planId: course_variation.plan_id,  // "I-XXXXXXXXXXX"
  ...
}

// User approves PayPal subscription
subscriptionId = response.subscriptionID  // "I-XXXXXXXXXX"

// AJAX: handle_course_subscription
→ Verifies subscription with PayPal
  GET /v1/billing/subscriptions/{subscriptionId}
→ Checks status = ACTIVE/APPROVAL_PENDING
→ Verifies plan_id matches variation's plan_id
→ Inserts into wp_course_subscriptions:
  {
    user_id: 123,
    course_id: 456,
    paypal_subscription_id: "I-XXXXXXXX",
    paypal_plan_id: "I-XXXXXXXX",
    billing_type: "monthly",
    price: 29.99,
    status: "ACTIVE",
    started_at: "2026-04-23 12:00:00"
  }
→ Updates user meta: active_subscriptions
→ Records in purchase log
→ Returns success
```

### 3. Webhook Handling

When PayPal events occur (payment, cancellation, etc.):

**URL**: `/?mm_paypal_webhook=1`

```
PayPal sends:
{
  event_type: "BILLING.SUBSCRIPTION.ACTIVATED",
  resource: {
    id: "I-XXXXXXXX",
    status: "ACTIVE"
  }
}

↓ System:
→ Finds subscription by paypal_subscription_id
→ Updates status in wp_course_subscriptions
→ May grant/revoke course access
```

---

## Key Files & Functions

### 1. store-variations.php

**Meta Box Registration**
```php
add_action('add_meta_boxes', function() {
  add_meta_box('mm_course_variations', ...);
});
```

**Billing Options** (Updated with Weekly)
```php
<select name="mm_variations[...][billing]">
  <option value="onetime">One-time</option>
  <option value="weekly">Weekly</option>      ← NEW
  <option value="monthly">Monthly</option>
  <option value="yearly">Yearly</option>
</select>
```

**Interval Mapping**
```php
$interval_map = [
  'weekly'  => 'WEEK',   ← PayPal API interval
  'monthly' => 'MONTH',
  'yearly'  => 'YEAR',
];
$interval = $interval_map[$billing] ?? 'MONTH';
$plan = mm_pp_create_plan($product_id, $label, $price, $interval);
```

### 2. paypal-api.php

**Plan Creation with Interval**
```php
function mm_pp_create_plan($product_id, $label, $price, $interval)
{
  // $interval = 'WEEK' | 'MONTH' | 'YEAR'
  $payload = [
    'billing_cycles' => [[
      'frequency' => [
        'interval_unit' => $interval,    ← PayPal interval
        'interval_count' => 1,
      ],
      'pricing_scheme' => [
        'fixed_price' => ['value' => $price]
      ]
    ]]
  ];
  
  return mm_pp_request('POST', '/v1/billing/plans', $payload);
}
```

### 3. paypalsettings.php

**New: Subscription Database Table**
```php
add_action('after_setup_theme', function() {
  // Creates wp_course_subscriptions table
  // With columns: id, user_id, course_id, billing_type, status, etc.
});
```

**Subscription Logging**
```php
function mm_log_subscription(
  $user_id, 
  $course_id, 
  $variation_index,
  $variation_label,
  $paypal_subscription_id,
  $paypal_plan_id,
  $billing_type,        // 'weekly', 'monthly', 'yearly'
  $price,
  $paypal_payer_id,
  $paypal_payer_email
)
{
  // Inserts into wp_course_subscriptions
}
```

**Helper Functions**
```php
// Get user's active subscriptions
$subs = mm_get_user_subscriptions($user_id);

// Get all subscriptions for a course
$subs = mm_get_course_subscriptions($course_id);

// Update status
mm_update_subscription_status($sub_id, 'CANCELLED', $reason);

// Find subscription by PayPal ID
$sub = mm_get_subscription_by_paypal_id($paypal_sub_id);
```

**Admin Dashboard**
```php
function render_subscriptions_dashboard()
{
  // Stats: Active count, total revenue
  // Filters: Status, Course, User
  // Table: All subscription details
}
```

---

## Database: wp_course_subscriptions

### Schema
```sql
CREATE TABLE wp_course_subscriptions (
  id                        BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id                   BIGINT NOT NULL,
  course_id                 BIGINT NOT NULL,
  variation_index           INT DEFAULT -1,
  variation_label           VARCHAR(255),
  paypal_subscription_id    VARCHAR(50) NOT NULL UNIQUE,
  paypal_plan_id            VARCHAR(50),
  billing_type              ENUM('onetime','weekly','monthly','yearly'),
  price                     DECIMAL(10,2),
  status                    ENUM('ACTIVE','SUSPENDED','CANCELLED','EXPIRED'),
  started_at                DATETIME,
  updated_at                DATETIME ON UPDATE CURRENT_TIMESTAMP,
  cancelled_at              DATETIME,
  cancellation_reason       VARCHAR(500),
  paypal_payer_id           VARCHAR(50),
  paypal_payer_email        VARCHAR(255),
  next_billing_date         DATE,
  failed_attempts           INT DEFAULT 0,
  last_payment_date         DATETIME,
  last_payment_amount       DECIMAL(10,2),
  
  KEY user_id (user_id),
  KEY course_id (course_id),
  KEY status (status),
  KEY paypal_subscription_id (paypal_subscription_id)
);
```

### Queries

**Get User's Active Subscriptions**
```sql
SELECT * FROM wp_course_subscriptions 
WHERE user_id = 123 
AND status = 'ACTIVE' 
ORDER BY started_at DESC;
```

**Revenue Report (Monthly)**
```sql
SELECT 
  billing_type,
  COUNT(*) as count,
  SUM(price) as revenue,
  AVG(price) as avg_price
FROM wp_course_subscriptions
WHERE status = 'ACTIVE'
GROUP BY billing_type;
```

**Churn Rate**
```sql
SELECT 
  COUNT(*) as total_active,
  SUM(CASE WHEN cancelled_at IS NOT NULL THEN 1 ELSE 0 END) as cancelled,
  (SUM(CASE WHEN cancelled_at IS NOT NULL THEN 1 ELSE 0 END) * 100.0 / COUNT(*)) as churn_percent
FROM wp_course_subscriptions
WHERE started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);
```

---

## PayPal API Integration

### Get Access Token
```php
$token = mm_pp_get_access_token();
// Returns cached OAuth token valid for ~1 hour
```

### Make Request
```php
$response = mm_pp_request('POST', '/v1/billing/plans', $payload);
// Handles: auth, error checking, retry logic
```

### Create Billing Plan (All Intervals Supported)
```php
// Weekly
$plan = mm_pp_create_plan($product_id, "Weekly Plan", 9.99, 'WEEK');

// Monthly
$plan = mm_pp_create_plan($product_id, "Monthly Plan", 29.99, 'MONTH');

// Yearly
$plan = mm_pp_create_plan($product_id, "Yearly Plan", 99.99, 'YEAR');
```

### Verify Subscription
```php
$sub = mm_pp_get_subscription($subscription_id);
// Returns: {id, status, plan_id, subscriber, ...}

// Status values:
// - APPROVAL_PENDING
// - APPROVED
// - ACTIVE
// - SUSPENDED
// - CANCELLED
// - EXPIRED
```

### Cancel Subscription
```php
$response = mm_pp_request(
  'POST',
  '/v1/billing/subscriptions/' . $sub_id . '/cancel',
  ['reason' => 'User requested cancellation']
);
```

---

## Billing Cycle Calculation

For **next_billing_date** calculation:

```php
// For weekly subscriptions
$next_date = strtotime('+7 days', strtotime($sub['started_at']));

// For monthly subscriptions  
$next_date = strtotime('+1 month', strtotime($sub['started_at']));

// For yearly subscriptions
$next_date = strtotime('+1 year', strtotime($sub['started_at']));
```

---

## Error Handling

### PayPal API Errors
```php
$result = mm_pp_request(...);
if (is_wp_error($result)) {
  $error_code = $result->get_error_code();      // e.g., 'paypal_api_error'
  $error_message = $result->get_error_message();
  $error_data = $result->get_error_data();      // Full response from PayPal
  
  error_log("PayPal error: $error_code - $error_message");
}
```

### Common Errors
```
paypal_missing_creds    → API credentials not configured
paypal_no_token         → OAuth token request failed
paypal_api_error        → API returned HTTP error
paypal_no_product_id    → Product creation failed
paypal_no_plan_id       → Plan creation failed
Invalid variation       → Variation index out of range
Amount mismatch         → Client sent different price than server
Subscription inactive   → Status not ACTIVE/APPROVED
```

---

## Testing with Sandbox

### Sandbox URLs
- OAuth: `https://api.sandbox.paypal.com`
- WebhookListener: `https://webhook.sandbox.paypal.com`

### Test Accounts
Create at https://developer.paypal.com → Sandbox → Accounts

```
Business Account:
  Email: sb-xxxxxx@business.example.com
  Password: <generated>

Personal Account (for buyers):
  Email: sb-xxxxxx@personal.example.com
  Password: <generated>
```

### Test Data
```php
// Test API calls
$creds = [
  'client_id' => 'YOUR_SANDBOX_CLIENT_ID',
  'secret'    => 'YOUR_SANDBOX_SECRET',
  'api_url'   => 'https://api.sandbox.paypal.com'
];

// In wp-config.php for testing:
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

---

## Extending the System

### Adding New Billing Interval
1. Add option in `store-variations.php`:
   ```php
   <option value="biweekly">Bi-weekly</option>
   ```

2. Update validation:
   ```php
   $billing = in_array(..., ['onetime', 'weekly', 'biweekly', 'monthly', 'yearly'], true);
   ```

3. Update interval mapping:
   ```php
   $interval_map = [
     'biweekly' => 'BIWEEK',  // Check PayPal API docs
     ...
   ];
   ```

4. Database migration (if needed):
   ```sql
   ALTER TABLE wp_course_subscriptions 
   MODIFY billing_type ENUM('onetime','weekly','biweekly','monthly','yearly');
   ```

### Custom Subscription Logic
Hook into subscription creation:

```php
add_action('mm_subscription_activated', function($sub_data) {
  // $sub_data contains all subscription info
  // Your custom logic here (send email, set user role, etc.)
});
```

---

## Security Checklist

- ✅ All user input sanitized (sanitize_text_field, floatval, etc.)
- ✅ Nonce verification on form submission
- ✅ Server-side price verification (fraud check)
- ✅ PayPal signature verification (webhook)
- ✅ Permissions checks (current_user_can)
- ✅ User can only access their own subscriptions
- ✅ Admin can view all subscriptions (manage_options)

---

**Last Updated**: April 2026  
**Version**: 1.0 with Weekly Support  
**Maintainer**: Development Team

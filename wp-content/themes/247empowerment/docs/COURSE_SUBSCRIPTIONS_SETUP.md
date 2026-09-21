# Course Subscriptions System - Complete Setup Guide

## Overview
This is a comprehensive course/product subscription system integrated with PayPal supporting:
- **One-time payments**
- **Recurring subscriptions**: Weekly, Monthly, Yearly
- **User-controlled cancellation** - subscribers can cancel anytime
- **Detailed subscription tracking** - all data stored in database with full billing history
- **Admin dashboard** - manage all subscriptions from WordPress admin panel

---

## 📋 Table of Contents
1. [PayPal Setup](#paypal-setup)
2. [Creating Course Variations](#creating-course-variations)
3. [Billing Types Explained](#billing-types-explained)
4. [Admin Dashboard](#admin-dashboard)
5. [Subscription Database Structure](#subscription-database-structure)
6. [Testing](#testing)
7. [Troubleshooting](#troubleshooting)

---

## PayPal Setup

### Step 1: Create PayPal Developer Account
1. Go to https://developer.paypal.com
2. Sign in with your PayPal account (or create one)
3. Navigate to **Dashboard** → **Apps & Credentials**

### Step 2: Create App for Sandbox (Testing)
1. Click **Create App** under Sandbox
2. Name it: `247empowerment-sandbox`
3. Copy:
   - **Sandbox Client ID** 
   - **Sandbox Secret**

### Step 3: Create App for Live (Production)
1. Switch to **Live** tab
2. Click **Create App**
3. Name it: `247empowerment-live`
4. Copy:
   - **Live Client ID**
   - **Live Secret**

### Step 4: Configure in WordPress
1. Go to **Settings → PayPal Settings**
2. Select **Environment**:
   - Development: Choose "Sandbox"
   - Production: Choose "Live"
3. Enter credentials:
   - Sandbox Client ID & Secret (for testing)
   - Live Client ID & Secret (for production)
4. **Save Settings**

### Step 5: Create Webhook for Subscription Events
1. In PayPal Developer Dashboard → **Webhooks**
2. Click **Create Webhook**
3. Set Webhook URL to:
   ```
   https://yoursite.com/?mm_paypal_webhook=1
   ```
4. Subscribe to Events:
   - `BILLING.SUBSCRIPTION.ACTIVATED`
   - `BILLING.SUBSCRIPTION.CANCELLED`
   - `BILLING.SUBSCRIPTION.EXPIRED`
   - `BILLING.SUBSCRIPTION.SUSPENDED`
   - `PAYMENT.SALE.COMPLETED` (for one-time payments)
5. Copy **Webhook ID**
6. Paste in **Settings → PayPal Settings → Webhook ID**

---

## Creating Course Variations

### What is a Variation?
A **variation** is a different pricing/billing option for the same course. Example:

| Label | Price | Billing | Duration |
|-------|-------|---------|----------|
| Basic | $19 | One-time | Lifetime access |
| Pro | $9.99 | Weekly | For 12 weeks |
| Plus | $29 | Monthly | Cancel anytime |
| Premium | $99 | Yearly | Cancel anytime |

### How to Create Variations
1. Go to **Courses** in WordPress Admin
2. Create or Edit a Course
3. Scroll down to **Product / Course Variations** section
4. Click **+ Add variation**
5. Fill in:
   - **Label**: What the user sees (e.g., "Pro Plan")
   - **Price**: In USD (e.g., 29.00)
   - **Billing**: 
     - `One-time` - customer pays once
     - `Weekly` - recurring every 7 days
     - `Monthly` - recurring every month
     - `Yearly` - recurring every year
   - **SKU**: Optional product code (e.g., BASIC-01)
   - **Description**: Short text shown at checkout (e.g., "30 days access")
6. Click **Save Post**

When you save, the system:
- Creates a PayPal Product (if first variation for this course)
- Creates a PayPal Billing Plan (for recurring subscriptions)
- Shows green checkmark: ✓ Plan linked (when successful)
- Shows red warning if PayPal plan creation fails

---

## Billing Types Explained

### 1. One-time Payment
- Customer pays **once** for **lifetime access**
- **No recurring charges**
- PayPal processes as immediate payment
- Good for: Fixed courses, certifications

### 2. Weekly Subscription
- Customer pays **every 7 days**
- Subscription **continues until cancelled by user**
- PayPal charges every week automatically
- User can **cancel anytime** from their account
- Good for: Coaching programs, recurring content

### 3. Monthly Subscription
- Customer pays **every 30 days**
- Subscription **continues until cancelled by user**
- PayPal charges monthly automatically
- User can **cancel anytime** from their account
- Good for: Membership sites, training programs

### 4. Yearly Subscription
- Customer pays **every 365 days**
- Subscription **continues until cancelled by user**
- PayPal charges annually automatically
- User can **cancel anytime** from their account
- Good for: Premium memberships, annual licenses

---

## Admin Dashboard

### Accessing the Dashboard
1. Go to WordPress Admin
2. Click **Subscriptions** (in left menu)

### What You'll See
- **Statistics**: Active subscriptions count, total revenue by status
- **Filters**: Filter by Status, Course, User
- **Table with**:
  - User email & name
  - Course name (clickable to edit)
  - Variation/Plan name
  - Billing type (Weekly, Monthly, etc.)
  - Price
  - Status (ACTIVE, CANCELLED, SUSPENDED, EXPIRED)
  - Start date
  - Next billing date
  - PayPal subscription ID (first 15 chars)

### Status Meanings
- **ACTIVE**: Subscription is active, will charge on next billing date
- **SUSPENDED**: PayPal suspended due to payment failure (awaiting retry)
- **CANCELLED**: User cancelled subscription
- **EXPIRED**: Subscription reached its end date or user cancelled

---

## Subscription Database Structure

All subscription data is stored in `wp_course_subscriptions` table:

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| user_id | BIGINT | WordPress user ID |
| course_id | BIGINT | Course post ID |
| variation_index | INT | Which variation (0, 1, 2, etc.) |
| variation_label | VARCHAR | "Pro Plan", "Monthly", etc. |
| paypal_subscription_id | VARCHAR | PayPal subscription ID |
| paypal_plan_id | VARCHAR | PayPal billing plan ID |
| billing_type | ENUM | 'weekly', 'monthly', 'yearly', 'onetime' |
| price | DECIMAL | Amount in USD |
| status | ENUM | ACTIVE, SUSPENDED, CANCELLED, EXPIRED |
| started_at | DATETIME | When subscription started |
| updated_at | DATETIME | Last update |
| cancelled_at | DATETIME | When user cancelled |
| cancellation_reason | VARCHAR | Why they cancelled |
| paypal_payer_id | VARCHAR | PayPal customer ID |
| paypal_payer_email | VARCHAR | Customer email |
| next_billing_date | DATE | Next charge date |
| failed_attempts | INT | Failed payment attempts |
| last_payment_date | DATETIME | When last charged |
| last_payment_amount | DECIMAL | Last payment amount |

---

## Testing

### Sandbox Testing Setup
1. Create test PayPal accounts:
   - **Business account**: https://developer.paypal.com → Sandbox → Create account
   - **Personal account**: Similar process (for buyer)

### Test Payment Flow
1. Switch to **Sandbox** environment (Settings → PayPal Settings)
2. Create a test course with one-time variation ($5)
3. On frontend, click "Buy Now"
4. Choose variation
5. Pay with test Personal account
6. Verify in Admin **Subscriptions** dashboard

### Test Subscription Flow
1. Create monthly variation ($2.99)
2. Click "Subscribe"
3. PayPal will charge monthly
4. Verify subscription shows in dashboard with status ACTIVE
5. Test cancellation - user should be able to cancel anytime

### Verify Billing Type
In Admin → Subscriptions:
- Check "Billing" column shows correct type (Weekly, Monthly, Yearly)
- Check "Next Billing" date is correctly calculated
- For weekly: 7 days from today
- For monthly: 30 days from today
- For yearly: 365 days from today

---

## Troubleshooting

### Issue: "⚠ No PayPal plan yet — save to create"
**Cause**: Variation is set to Monthly/Yearly but plan wasn't created
**Solution**:
1. Check PayPal credentials are correct (Settings → PayPal Settings)
2. Check WordPress debug log: `wp-content/debug.log`
3. Save course again to retry plan creation
4. If still fails, check PayPal API status: https://status.paypal.com

### Issue: Subscription not appearing in dashboard
**Cause**: Webhook not configured or payment failed
**Solution**:
1. Check PayPal Settings → Webhook ID is saved
2. Check WordPress debug log for errors
3. Manually verify PayPal subscription:
   - PayPal Dashboard → Subscriptions → Find subscription
   - Check status is ACTIVE

### Issue: User can't cancel subscription
**Cause**: Cancel button not showing or AJAX failing
**Solution**:
1. Check browser console (F12) for JavaScript errors
2. Check WordPress debug log: `wp-content/debug.log`
3. Verify PayPal credentials are correct
4. Test with admin account first

### Issue: Next billing date is wrong
**Cause**: Billing type not recognized or date calculation error
**Solution**:
1. Check variation in admin - is billing type set correctly?
2. Check `wp_course_subscriptions` table - is `billing_type` column correct?
3. Check PayPal plan created with correct interval:
   - WEEK = weekly
   - MONTH = monthly  
   - YEAR = yearly

---

## Helper Functions Reference

If you need to access subscription data programmatically:

```php
// Get all active subscriptions for a user
$subs = mm_get_user_subscriptions($user_id);

// Get all subscriptions for a course
$course_subs = mm_get_course_subscriptions($course_id);

// Get subscription by PayPal ID
$sub = mm_get_subscription_by_paypal_id($paypal_sub_id);

// Update subscription status
mm_update_subscription_status($subscription_id, 'CANCELLED', 'User requested');

// Log new subscription
mm_log_subscription(
    $user_id, 
    $course_id, 
    $variation_index,
    $variation_label, 
    $paypal_subscription_id,
    $paypal_plan_id,
    'monthly', // billing type
    29.99,     // price
    $payer_id,
    $payer_email
);
```

---

## Files Modified/Created

- `more_functions/store.php` - Course CPT registration
- `more_functions/store-variations.php` - Variation UI + weekly billing
- `more_functions/paypal-api.php` - PayPal API integration
- `more_functions/paypalsettings.php` - Settings page, dashboard, helpers

---

## Support & Debugging

Enable debug mode in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Then check logs:
```
wp-content/debug.log
```

Look for PayPal errors prefixed with: `[PayPal]`, `[Subscription]`, `[Plan]`

---

**Last Updated**: April 2026  
**System Version**: v1.0 with Weekly Support

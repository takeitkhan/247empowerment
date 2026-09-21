# Payout System - Setup & Configuration Guide

## Overview
Complete Payout System that allows users to withdraw their earnings directly to their PayPal account from the WordPress dashboard.

## Features
- ✅ User Withdrawal Request Form
- ✅ Admin Approval/Rejection Dashboard
- ✅ PayPal Payout Integration
- ✅ Automated Email Notifications
- ✅ Audit Logging
- ✅ Balance Management
- ✅ Security & Permissions
- ✅ Rate Limiting

---

## Installation Steps

### 1. Database Migration
When the theme is activated, the following tables are automatically created:
- `wp_withdrawal_requests` - Stores all withdrawal requests
- `wp_payout_audit_log` - Logs all admin actions

```sql
-- Manual trigger (optional):
// In WordPress admin or via plugin:
PayoutSystem::activate();
```

### 2. Set Up PayPal

#### A. Create PayPal Developer Account
1. Go to https://www.paypal.com/paypalme
2. Create a Developer/Business account
3. Log in to https://developer.paypal.com/

#### B. Get Sandbox Credentials (for Testing)
1. Dashboard → Apps & Credentials
2. Click "Sandbox" tab
3. Get REST API credentials:
   - **Client ID** (copy this)
   - **Secret** (copy this)

#### C. Get Live Credentials (for Production)
1. Go to "Live" tab
2. Follow the same process

### 3. Configure Settings in WordPress

### 3. Configure Settings in WordPress

**WordPress Admin → Payouts → Settings**

```
Payout Min Amount: 5 (USD)
Payout Max Amount: 5000 (USD)
PayPal Mode: sandbox (for testing)
PayPal Client ID: [Your Client ID]
PayPal Secret: [Your Secret]
```

**For Production:**
```
PayPal Mode: live
PayPal Client ID: [Live Client ID]
PayPal Secret: [Live Secret]
```

### 4. Create Required Pages

**WordPress Admin → Pages → Add New**

#### Page 1: PayPal Email Management
- **Title:** PayPal Email
- **Template:** Modify PayPal Email
- **Slug:** modify-paypal-email
- **Status:** Published

#### Page 2: Withdrawal Request (Full Page with Sidebar)
- **Title:** Withdrawal Request (or "Withdrawals")
- **Template:** Withdrawal Request
- **Slug:** modify-withdrawal-request
- **Status:** Published

#### Page 3: Withdrawal Form (Optional - Shortcode only)
- **Title:** Withdrawals
- **Content:** `[withdrawal_form]`
- **Status:** Published
- (This is for embedding the simple shortcode version)

---

## Usage

### For Users

#### 1. Set Up PayPal Email
**Path:** Profile → PayPal Email Management

Users must first add their PayPal email before requesting withdrawals:
1. Navigate to `/modify-paypal-email` (or "PayPal Email" in profile menu)
2. Enter their PayPal email address
3. Save the email
4. This email will be used for all future withdrawal requests

#### 2. Request Withdrawal
**Path:** Withdrawals → [withdrawal_form] shortcode or `/modify-withdrawal-request`

Users can now request withdrawals:
1. Navigate to the withdrawal page
2. See their current balance
3. Enter withdrawal amount
4. Click "Request Withdrawal"
5. Receive automatic email confirmation
6. View withdrawal history

**Limitations:**
- Maximum 1 request per day
- Minimum $5, Maximum $5000
- Balance is verified
- PayPal email must be set in profile

#### Available Pages
- **[withdrawal_form]** - Shortcode for embedding withdrawal form anywhere
- **/modify-paypal-email** - Manage saved PayPal email (page template)
- **/modify-withdrawal-request** - Full withdrawal page with sidebar (page template)

### For Administrators

#### View Withdrawal Requests
WordPress Admin → Payouts → Withdrawal Requests

**Statuses:**
- **Pending** - Waiting for admin approval
- **Approved** - Admin approved (automatic PayPal processing starts)
- **Processing** - Being processed by PayPal
- **Paid** - Successfully transferred to user's PayPal account
- **Rejected** - Admin rejected the request
- **Failed** - PayPal error occurred

#### Take Action
**On Pending Requests:**
- ✅ **Approve** - Triggers PayPal transfer (automatic)
- ❌ **Reject** - Cancels the withdrawal

#### Filter Requests
- Filter by status
- Search by date range

#### View Audit Log
Every withdrawal is logged with:
- Admin approval/rejection actions
- PayPal API responses
- Success/failure details

---

## Email Notifications

### Users Receive Emails For:
1. **Withdrawal Requested** - When form is submitted
2. **Withdrawal Approved** - When admin approves
3. **Withdrawal Rejected** - When admin rejects
4. **Payment Success** - When PayPal transfer succeeds
5. **Payment Failed** - When PayPal encounters an error

---

## Database Schema

### `wp_withdrawal_requests`
```
id              - Unique withdrawal ID
user_id         - WordPress User ID
amount          - Withdrawal amount (USD)
paypal_email    - User's PayPal email
status          - pending/approved/processing/paid/rejected/failed
transaction_id  - PayPal Batch ID (when payment completed)
admin_notes     - Admin notes/comments
created_at      - Request creation date
updated_at      - Last update timestamp
```

### `wp_payout_audit_log`
```
id              - Audit log ID
withdrawal_id   - Foreign key to wp_withdrawal_requests
admin_id        - Admin user ID who took action
action          - approved/rejected/payout_success/payout_failed
notes           - Details of the action
response_data   - PayPal API response (JSON)
created_at      - Timestamp
```

---

## Security Features

✅ **User Permissions**
- Only logged-in users can request withdrawals
- Users can only view their own withdrawal history

✅ **Admin Permissions**
- Only users with `manage_options` capability can access admin panel

✅ **Nonce Security**
- All AJAX requests are verified with `wp_verify_nonce()`

✅ **Input Validation**
- Email format validation
- Amount range validation (min $5, max $5000)
- Balance verification
- Rate limiting (1 request per 24 hours)

✅ **Data Sanitization**
- All inputs are sanitized with `sanitize_*` functions
- Protected against HTML/SQL injection attacks

---

## Troubleshooting

### PayPal Authentication Error
**Problem:** "PayPal authentication failed"

**Solution:**
1. Verify Client ID and Secret are correct
2. Check Sandbox/Live mode is set correctly
3. Log in to PayPal Developer account and copy credentials again
4. Ensure mode matches (sandbox credentials for sandbox mode, live for live)

### Withdrawal Stuck on Failed Status
**Problem:** Withdrawal stays in "failed" status

**Solution:**
1. Check audit log for error message
2. Verify PayPal response:
   - Is the PayPal email valid?
   - Is the PayPal account active?
   - Has daily payout limit been exceeded?
3. Try manual retry from admin panel (if available)

### Email Not Sending
**Problem:** Users don't receive notification emails

**Solution:**
1. Verify WordPress email settings
2. Check admin email is correct (Settings → General)
3. Use a mail plugin like WP Mail SMTP for debugging
4. Check spam/junk folder

### Cannot See Payouts Menu
**Problem:** Payouts menu doesn't appear in admin

**Solution:**
1. Verify you're logged in as admin (manage_options capability required)
2. Hard refresh the page (Cmd+Shift+R on Mac, Ctrl+Shift+R on Windows)
3. Check that PayoutSystem.php is in `/inc/` directory
4. Verify functions.php includes PayoutSystem.php

---

## API Reference

### Hooks

#### `payout_withdrawal_requested` (Action)
Fires when user submits a withdrawal request
```php
do_action('payout_withdrawal_requested', $user_id, $withdrawal_id, $amount, $paypal_email);
```

#### `payout_payment_success` (Action)
Fires when PayPal payment succeeds
```php
do_action('payout_payment_success', $user_id, $withdrawal_id, $amount, $batch_id);
```

#### `payout_payment_failed` (Action)
Fires when PayPal payment fails
```php
do_action('payout_payment_failed', $user_id, $withdrawal_id, $error_message);
```

### Shortcodes

#### `[withdrawal_form]`
Displays the user withdrawal form
```
[withdrawal_form]
```

---

## File Locations

```
wp-content/themes/247empowerment/
├── inc/
│   ├── PayoutSystem.php          # Core system class
│   ├── PayPalAPI.php             # PayPal integration
│   └── PayoutNotifications.php    # Email notifications
├── template-custom/frontend/
│   └── withdrawal-form.php        # User form template
└── functions.php                  # Shortcode & includes
```

---

## Testing Checklist

Before going live, verify:

- [ ] PayPal sandbox credentials are configured
- [ ] User can submit a withdrawal request
- [ ] Admin receives the withdrawal in dashboard
- [ ] Admin can approve a withdrawal
- [ ] User receives approval email
- [ ] PayPal payment is processed
- [ ] User's balance is deducted correctly
- [ ] User receives success email
- [ ] Audit log records all actions
- [ ] Rate limiting works (can't submit 2 requests in 24 hours)

---

## Future Enhancements

- [ ] Stripe integration
- [ ] Advanced reporting and analytics
- [ ] Batch processing (weekly/monthly payments)
- [ ] Mobile app notifications
- [ ] CSV export of withdrawal history
- [ ] Multi-currency support
- [ ] Automated retry for failed payouts
- [ ] Webhook support for real-time payout status updates

---

## Support

For issues or questions:
1. Check WordPress Admin → Payouts → Documentation (this page)
2. Review Payout Settings for configuration issues
3. Check email spam folder if notifications aren't received
4. Enable WordPress debug logging for technical details

**WordPress Debug Mode:**
Edit `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Logs will appear in `/wp-content/debug.log`

---

## Frequently Asked Questions

### Q: Can I change the minimum or maximum withdrawal amount?
A: Yes! Go to WordPress Admin → Payouts → Settings

### Q: How often can users request withdrawals?
A: Currently limited to 1 request per 24 hours. Edit `PayoutSystem.php` to change this.

### Q: What happens if PayPal payment fails?
A: The withdrawal status changes to "Failed" and the user receives an error email. Admin can retry from the dashboard.

### Q: Are user balances automatically deducted?
A: Yes, when payment is successful to PayPal, the amount is deducted from the user's `referral_commission` meta.

### Q: Can I bulk approve withdrawals?
A: Currently you must approve individually. Bulk approval feature can be added in future updates.

### Q: Where is the user balance stored?
A: In the `usermeta` table as `referral_commission` meta key.

---

## Version History

- **v1.0** - Initial release
  - User withdrawal requests
  - Admin approval/rejection
  - PayPal integration
  - Email notifications
  - Audit logging

---

*Last Updated: February 15, 2026*

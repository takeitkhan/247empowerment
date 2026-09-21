# Withdrawal Request Approval - Test Plan & Development Guide

## Overview
Complete testing guide for the Withdrawal Request Approval functionality in the admin panel.

---

## System Architecture

### User Flow
```
User Submits Request → Pending Status → Balance NOT Deducted
                                            ↓
                          [Admin Approval Process]
                                    ↓
                        Status = "Processing"
                        Balance DEDUCTED
                        PayPal API Triggered
                                    ↓
                    Payment Sent to PayPal Email
                                    ↓
                        Status = "Paid"
```

### Admin Panel Location
- Dashboard → **Payouts** → **Withdrawal Requests**
- URL: `/wp-admin/admin.php?page=payout-requests`

---

## Test Scenarios

### ✅ TEST 1: Display Pending Withdrawals
**Objective**: Admin can see pending withdrawal requests

**Steps**:
1. Log in as admin
2. Go to Dashboard → Payouts → Withdrawal Requests
3. Filter by "Pending" status
4. Verify table displays:
   - Withdrawal ID
   - User name & email
   - Amount
   - PayPal email
   - Status badge
   - Request date

**Expected Result**:
- ✅ Only pending requests display
- ✅ All columns populated correctly
- ✅ Status shows "Pending" (orange badge)

---

### ✅ TEST 2: Approve Withdrawal - Success
**Objective**: Admin can approve a withdrawal and balance is deducted

**Preconditions**:
- User has balance of at least $10
- Pending withdrawal request exists for $5

**Steps**:
1. In admin panel, click "Approve" button on pending request
2. Confirmation dialog appears
3. Click "OK" to confirm
4. Optional: Enter admin notes
5. Click confirm again

**Expected Results**:
- ✅ Success alert: "Withdrawal approved and processing"
- ✅ Page refreshes
- ✅ Withdrawal status changes to "Processing"
- ✅ User balance reduced by withdrawal amount
- ✅ Audit log entry created with action="approved"
- ✅ User receives email notification

**Data Verification**:
```sql
-- Check withdrawal status
SELECT id, user_id, amount, status FROM pet_withdrawal_requests 
WHERE id = [withdrawal_id];
-- Should show: status = 'processing'

-- Check user balance was deducted
SELECT user_id, meta_value FROM wp_usermeta 
WHERE user_id = [user_id] AND meta_key = 'referral_commission';

-- Check audit log
SELECT * FROM pet_payout_audit_log 
WHERE withdrawal_id = [withdrawal_id] AND action = 'approved';
```

---

### ✅ TEST 3: Reject Withdrawal
**Objective**: Admin can reject withdrawal, balance remains intact

**Preconditions**:
- Pending withdrawal request exists

**Steps**:
1. In admin panel, click "Reject" button
2. Confirmation dialog
3. Enter rejection reason
4. Confirm

**Expected Results**:
- ✅ Success alert: "Withdrawal rejected"
- ✅ Status changes to "Rejected"
- ✅ User balance NOT deducted
- ✅ User receives rejection email
- ✅ Audit log entry created

---

### ✅ TEST 4: Filter by Status
**Objective**: Admin can filter withdrawals by status

**Steps**:
1. In Withdrawal Requests page
2. Select different statuses from dropdown:
   - All Status
   - Pending
   - Approved
   - Processing
   - Paid
   - Rejected
   - Failed
3. Click "Filter"

**Expected Results**:
- ✅ Table updates to show only selected status
- ✅ Count matches expected number

---

### ✅ TEST 5: Pagination
**Objective**: Admin can navigate through pages

**Steps**:
1. If more than 20 requests exist
2. Click pagination buttons at bottom
3. Navigate through pages

**Expected Results**:
- ✅ Next/Previous buttons work
- ✅ Correct requests shown on each page

---

### ✅ TEST 6: View Admin Notes
**Objective**: Admin notes are stored and visible

**Steps**:
1. Approve/Reject a withdrawal with notes
2. Check audit log for notes

**Expected Results**:
- ✅ Notes saved in audit log
- ✅ Notes visible in admin panel

---

### ✅ TEST 7: Concurrent Approvals
**Objective**: System handles multiple approvals correctly

**Steps**:
1. Open multiple browser tabs
2. Approve same withdrawal in one tab
3. Try to approve again in another tab

**Expected Results**:
- ✅ First approval succeeds
- ✅ Second approval shows error or is skipped
- ✅ Balance only deducted once

---

### ✅ TEST 8: Balance Validation
**Objective**: System verifies sufficient balance when approving

**Preconditions**:
- User balance = $10
- Pending withdrawal = $15

**Steps**:
1. Try to approve the $15 withdrawal

**Expected Results**:
- ✅ Approval still works (admin can override)
- ✅ Balance goes negative if approved
- OR
- ✅ Error message: "Insufficient balance"

---

### ✅ TEST 9: Database Integrity
**Objective**: All tables update correctly

**Steps**:
1. Approve a withdrawal
2. Check database tables

**Expected Results**:

```
pet_withdrawal_requests:
- status updated to 'processing'
- transaction_id may be populated
- updated_at timestamp changed

pet_payout_audit_log:
- New entry created
- action = 'approved'
- admin_id = current admin
- notes = admin notes (if any)
- created_at = current timestamp

wp_usermeta (referral_commission):
- Amount deducted
- Balance change logged
```

---

### ✅ TEST 10: Error Handling
**Objective**: System handles errors gracefully

**Test Cases**:

#### 10a: Invalid Withdrawal ID
- Approval link/form with fake ID
- Expected: Error message "Withdrawal not found"

#### 10b: Invalid Nonce
- Submit with expired/invalid nonce
- Expected: "Security check failed"

#### 10c: Unauthorized User
- Regular user tries to access approval
- Expected: "Unauthorized" error or 403

#### 10d: Already Processed Withdrawal
- Try to approve already-approved request
- Expected: Either success or error (system-dependent)

---

## Performance Tests

### TEST 11: Bulk Operations
- Load test with 100+ pending withdrawals
- Verify page loads in <2 seconds
- Verify approval completes in <1 second

### TEST 12: Large Balance Values
- Test with amounts like $999,999.99
- Verify currency formatting correct
- Verify balance calculations accurate

---

## Security Tests

### TEST 13: CSRF Protection
- Nonce verification on all approval/rejection actions
- Invalid nonce should be rejected

### TEST 14: Capability Check
- Only users with `manage_options` can approve
- Regular admins cannot approve if capability missing

### TEST 15: SQL Injection
- Try to inject SQL in withdrawal ID parameter
- Should be safely handled via prepared statements

### TEST 16: XSS Prevention
- Admin notes with HTML/JS tags
- Should be sanitized/escaped properly

---

## Integration Tests

### TEST 17: Email Notifications
- Approval email sent to user
- Rejection email sent to user
- Email contains:
  - Amount
  - Status
  - Admin notes (if any)
  - User action (if needed)

### TEST 18: PayPal Action Hook
- `do_action('payout_process_paypal', $withdrawal_id)` triggered
- PayPal API integration can hook into this

### TEST 19: Audit Trail
- All actions logged in audit table
- Admin can see who approved/rejected
- Timestamp recorded
- Notes preserved

---

## UI/UX Tests

### TEST 20: Button States
- Approve/Reject buttons only show for pending requests
- Buttons disabled for processed requests
- Buttons have clear visual states

### TEST 21: Status Colors
```
Pending   → Orange (#ff9800)
Approved  → Blue   (#2196F3)
Processing→ Purple (#9C27B0)
Paid      → Green  (#27ae60)
Rejected  → Red    (#f44336)
Failed    → Red    (#f44336)
```

### TEST 22: Confirmation Dialogs
- Approval shows: "Are you sure you want to approve this withdrawal?"
- Rejection shows: "Are you sure you want to reject this withdrawal?"
- Can cancel operations

---

## Quick Manual Test Script

```bash
# 1. Create test user
wp user create testuser test@example.com --user_pass=password123 --role=subscriber

# 2. Add balance to test user
wp db query "INSERT INTO wp_usermeta (user_id, meta_key, meta_value) VALUES (ID, 'referral_commission', 50)"

# 3. Add PayPal email
wp user meta set USER_ID paypal_email 'test@paypal.com'

# 4. Create test withdrawal request
wp db query "INSERT INTO pet_withdrawal_requests (user_id, amount, paypal_email, status) VALUES (USER_ID, 25, 'test@paypal.com', 'pending')"

# 5. Verify in admin panel
# - Go to /wp-admin/admin.php?page=payout-requests
# - Should see pending request
# - Click Approve

# 6. Verify balance deducted
wp user meta get USER_ID referral_commission
# Should be 25 (50 - 25)

# 7. Verify status changed
wp db query "SELECT status FROM pet_withdrawal_requests WHERE id = WITHDRAWAL_ID"
# Should show: processing
```

---

## Logging & Debugging

### Enable Debug Logs
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Logs go to: wp-content/debug.log
```

### Check Logs
```bash
tail -f wp-content/debug.log | grep -i "approval\|withdrawal\|payout"
```

### MySQL Query Verification
```sql
-- Check all withdrawal requests
SELECT id, user_id, amount, status, created_at FROM pet_withdrawal_requests;

-- Check user balance
SELECT user_id, meta_key, meta_value FROM wp_usermeta 
WHERE meta_key IN ('referral_commission', 'balance_change_logs');

-- Check audit trail
SELECT * FROM pet_payout_audit_log ORDER BY created_at DESC;

-- Check balance changes
SELECT meta_value FROM wp_usermeta 
WHERE user_id = X AND meta_key = 'balance_change_logs'\G
```

---

## Known Issues & Limitations

1. **No Batch Approval**: Cannot approve multiple withdrawals at once
2. **No Scheduling**: Cannot schedule payments for future dates
3. **No Partial Approval**: Cannot approve partial amounts
4. **No Payment Status Polling**: PayPal status not auto-updated

---

## Next Phase: PayPal Integration

After approval system is stable:

1. **PayPal API Setup**
   - Create PayPal batch payout API
   - Setup webhook for payment callbacks

2. **Status Updates**
   - When PayPal sends webhook: "Paid"
   - Failed payments: "Failed" status

3. **Retry Logic**
   - Failed payments auto-retry
   - Max retry attempts configurable

4. **Reporting**
   - Daily payout summary
   - Revenue reports by user
   - Payment failure alerts

---

## Success Criteria

✅ All 22 tests passing
✅ No security vulnerabilities
✅ Balance calculations 100% accurate
✅ Audit trail complete
✅ Email notifications working
✅ <1s approval response time
✅ <2s page load for 100+ requests

---

## Test Sign-Off

- [ ] Developer: Completed manual testing
- [ ] Admin: Verified in staging environment
- [ ] QA: All scenarios tested
- [ ] Ready for production deployment

---

Last Updated: February 19, 2026
Status: Ready for Testing Phase

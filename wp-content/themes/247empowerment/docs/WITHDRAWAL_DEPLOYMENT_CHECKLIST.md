# Withdrawal System Update - Deployment Checklist

## প্রি-ডিপ্লয়মেন্ট চেকলিস্ট

- [ ] **Code Changes Reviewed**
  - [ ] PayPalAPI.php checked for balance refund logic
  - [ ] PayoutSystem.php checked for status update enhancement
  - [ ] withdrawal-form.php checked for UI changes
  - [ ] payout-balance.php reviewed for balance functions

- [ ] **Database Verified**
  - [ ] `wp_withdrawal_requests` table has `admin_notes` column
  - [ ] `wp_payout_audit_log` table exists
  - [ ] Backups taken

- [ ] **Backup Created**
  - [ ] Database backup taken
  - [ ] File backups taken (especially PayPalAPI.php, PayoutSystem.php)
  - [ ] Backup location noted: `__________________`

## ডিপ্লয়মেন্ট স্টেপস

### Step 1: ফাইল আপলোড করুন
```bash
# Upload the modified files to production:
/wp-content/themes/247empowerment/inc/PayPalAPI.php
/wp-content/themes/247empowerment/inc/PayoutSystem.php
/wp-content/themes/247empowerment/template-custom/frontend/withdrawal-form.php
/wp-content/themes/247empowerment/inc/payout-balance.php
```

- [ ] PayPalAPI.php uploaded
- [ ] PayoutSystem.php uploaded
- [ ] withdrawal-form.php uploaded
- [ ] payout-balance.php uploaded

### Step 2: Permission চেক করুন
```bash
# Verify file permissions
ls -la /wp-content/themes/247empowerment/inc/PayPalAPI.php
# Should be readable and writable by web server
```

- [ ] File permissions verified (644 or 755)
- [ ] Web server can read files

### Step 3: সিস্টেম ফাংশনালিটি টেস্ট করুন

#### Test 1: Admin Dashboard Access
```
Go to: Admin Dashboard → Payouts → Withdrawal Requests
```

- [ ] Admin page loads without errors
- [ ] Withdrawal list displays correctly
- [ ] New "Details" column visible for failed withdrawals

#### Test 2: Create Test Withdrawal
```php
// In admin, go to Clear Test Data page
// Set some test balance in user meta (if needed)

// User-side: Request small withdrawal (e.g., $0.25)
// Admin-side: Click "Approve" button
```

- [ ] Withdrawal can be submitted
- [ ] Admin approval works
- [ ] Withdrawal list updates

#### Test 3: Simulate PayPal Error (Optional)
```php
// To test error handling without real PayPal calls:
// Temporarily modify PayPalAPI.php get_access_token() to return an error
// Or disconnect network during test

// Then approve a withdrawal
// Expected: Status becomes "failed", balance is restored
```

- [ ] Error handling works
- [ ] Balance is refunded
- [ ] Error message is stored
- [ ] Admin and user can see error details

#### Test 4: Frontend User View
```
Go to: Withdrawal Request page (user-facing)
```

- [ ] Balance displays correctly
- [ ] Recent withdrawals table shows all fields
- [ ] Failed withdrawal shows "Failed - Click for details"
- [ ] Clicking shows error reason with restoration message

### Step 4: Log ফাইল চেক করুন
```bash
# Check WordPress debug log for any errors
tail -f /wp-content/debug.log
```

- [ ] No fatal errors in debug log
- [ ] Withdrawal actions logged correctly
- [ ] Balance refund logged with reason

### Step 5: Database ভেরিফিকেশন
```sql
-- Check a failed withdrawal record
SELECT id, status, admin_notes, transaction_id 
FROM wp_withdrawal_requests 
WHERE status = 'failed' 
ORDER BY created_at DESC LIMIT 1;
```

- [ ] Failed withdrawals have `admin_notes` populated
- [ ] Successful withdrawals have `transaction_id` populated
- [ ] No NULL status or invalid data

### Step 6: Audit Logs চেক করুন
```sql
-- Check audit trail
SELECT * FROM wp_payout_audit_log 
ORDER BY created_at DESC LIMIT 10;
```

- [ ] Audit entries created for all actions
- [ ] Error details logged when failures occur
- [ ] Timestamps are accurate

## পোস্ট-ডিপ্লয়মেন্ট ভেরিফিকেশন

### User-Facing Changes
- [ ] Users can see withdrawal history
- [ ] Failed withdrawals show error details when clicked
- [ ] Message "Your balance has been restored" displays for failed withdrawals
- [ ] Balance updates correctly after restoration

### Admin-Facing Changes
- [ ] Admin can see error reasons for failed withdrawals
- [ ] Admin can see transaction IDs for successful withdrawals
- [ ] Details column shows appropriate information for each status
- [ ] Admin approval/rejection buttons still work

### System Health
- [ ] No JavaScript console errors
- [ ] No PHP errors in debug log
- [ ] Database queries execute without errors
- [ ] Email notifications sent (if configured)

## রোলব্যাক প্ল্যান

If issues occur, follow these steps to revert:

### Option 1: Revert from Backup
```bash
# Restore files from backup
cp backup/PayPalAPI.php /wp-content/themes/247empowerment/inc/
cp backup/PayoutSystem.php /wp-content/themes/247empowerment/inc/
cp backup/withdrawal-form.php /wp-content/themes/247empowerment/template-custom/frontend/

# Restore database (if schema was modified)
mysql -u username -p database_name < backup/database.sql
```

### Option 2: Manual File Revert
```bash
# If you have git
git revert HEAD

# Or manually restore old versions from your version control
```

### Verification After Rollback
```
1. Clear browser cache
2. Verify admin page loads
3. Check that withdrawal system still functions
4. Review debug logs for any errors
```

---

## Performance Considerations

### Before Optimization
- [ ] Test withdrawal approval response time (should be < 2 seconds)
- [ ] Test balance refund response time (should be < 1 second)
- [ ] Monitor database query performance

### After Optimization (if needed)
```sql
-- Add indexes for frequently queried fields
ALTER TABLE wp_withdrawal_requests ADD INDEX idx_user_status (user_id, status);
ALTER TABLE wp_withdrawal_requests ADD INDEX idx_status (status);

-- Check index effectiveness
EXPLAIN SELECT * FROM wp_withdrawal_requests WHERE user_id = 5 AND status = 'failed';
```

---

## Monitoring Setup

### Key Metrics to Monitor
```
1. Failed withdrawal rate:
   SELECT COUNT(*) FROM wp_withdrawal_requests WHERE status = 'failed' AND DATE(created_at) = CURDATE();

2. Average payout processing time:
   SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) FROM wp_withdrawal_requests WHERE status = 'paid';

3. User balance accuracy:
   Check that sum of all user balances matches total referral_commission across all users
```

### Alert Setup
- [ ] Set alert if failed withdrawal count > 5 in a day
- [ ] Set alert if processing time > 5 minutes
- [ ] Set alert if database backup fails

---

## Documentation Updates

- [ ] README updated with new features
- [ ] CHANGELOG updated with fix details
- [ ] Support documentation updated
- [ ] FAQ updated with "Why was my withdrawal rejected?" answer

---

## Stakeholder Notification

### Internal Team
- [ ] Developers notified of changes
- [ ] Support team trained on new error messages
- [ ] Admin users trained on new details column

### External Communication
- [ ] Users informed about balance restoration fix (optional blog post)
- [ ] Support page updated with troubleshooting guide

---

## Sign-Off

- [ ] **Developer**: __________________ Date: __________
- [ ] **QA Tester**: _________________ Date: __________
- [ ] **DevOps**: __________________ Date: __________
- [ ] **Project Manager**: __________ Date: __________

---

## Deployment Timeline

| Step | Estimated Time | Actual Time | Status |
|------|----------------|-------------|--------|
| File upload | 15 min | ____ | __ |
| Testing | 30 min | ____ | __ |
| Database check | 10 min | ____ | __ |
| Monitoring | 15 min | ____ | __ |
| Documentation | 20 min | ____ | __ |
| **Total** | **90 min** | ______ | __ |

---

## Support Contacts

For issues during deployment:
- **Primary**: `[Development Lead Email]`
- **Secondary**: `[DevOps Email]`
- **Emergency**: `[Manager Email]`

---

## Appendix: Common Issues & Solutions

### Issue 1: "admin_notes column not found"
**Solution**: Database migration might not have run. Manually check column exists:
```sql
DESC wp_withdrawal_requests;
```
If missing, add it:
```sql
ALTER TABLE wp_withdrawal_requests ADD COLUMN admin_notes LONGTEXT DEFAULT NULL;
```

### Issue 2: Balance not refunding
**Solution**: Check `payout_refund_withdrawal()` function is called correctly:
```php
// Verify in PayPalAPI.php line ~167
payout_refund_withdrawal($withdrawal->user_id, $withdrawal->amount, 'PayPal payout failed...');
```

### Issue 3: Error details not showing
**Solution**: Clear WordPress object cache:
```php
wp_cache_flush();
```

### Issue 4: Admin page slow
**Solution**: Check for missing database indexes:
```sql
CREATE INDEX idx_user_status ON wp_withdrawal_requests(user_id, status);
CREATE INDEX idx_status ON wp_withdrawal_requests(status);
```

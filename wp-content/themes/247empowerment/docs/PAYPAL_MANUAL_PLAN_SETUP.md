# PayPal Manual Plan Setup Guide

This guide walks you through creating subscription plans in PayPal and adding them to WordPress courses.

---

## 📋 Table of Contents
1. [Create Plans in PayPal](#create-plans-in-paypal)
2. [Add Plan IDs to WordPress](#add-plan-ids-to-wordpress)
3. [Test the Setup](#test-the-setup)
4. [Troubleshooting](#troubleshooting)

---

## Create Plans in PayPal

### Step 1: Go to PayPal Developer Dashboard

1. Open [https://developer.paypal.com/dashboard](https://developer.paypal.com/dashboard)
2. Login with your PayPal account
3. Click **Apps & Credentials** (top navigation)

### Step 2: Ensure You're in Sandbox

- At the top, select **Sandbox** (for testing)
- You should see your app listed under "REST API apps"
- Click on your app name

### Step 3: Create First Plan (Starter $297/month)

1. In the left sidebar, find **Subscriptions** or **Billing** section
2. Click **Create Plan** or **New Plan**
3. Fill in the details:

```
Plan Name:        Starter $297/month (Individuals & Startups)
Billing Frequency: Monthly
Cycle Amount:      $297.00
Currency:          USD
Status:            Active
```

4. Click **Create Plan**
5. ✅ **COPY the Plan ID** (looks like `I-ABC1D2E3F4G5H6I7`)
6. Paste it somewhere safe (notepad)

### Step 4: Create Second Plan (Professional $497/month)

Repeat Step 3 with:

```
Plan Name:        Professional $497/month (Growing Businesses)
Billing Frequency: Monthly
Cycle Amount:      $497.00
Currency:          USD
Status:            Active
```

**Copy Plan ID** ✅

### Step 5: Create Third Plan (Premium $997/month)

Repeat Step 3 with:

```
Plan Name:        Premium $997/month (Enterprises)
Billing Frequency: Monthly
Cycle Amount:      $997.00
Currency:          USD
Status:            Active
```

**Copy Plan ID** ✅

### Step 6: Create Additional Plans (if needed)

If you have any weekly or yearly subscriptions, create those too:

**Weekly Plan Example:**
```
Plan Name:        24/7 Empowerment Meetings to Alumni
Billing Frequency: Weekly
Cycle Amount:      $25.00
Currency:          USD
Status:            Active
```

**Yearly Plan Example:**
```
Plan Name:        Annual Plan Name
Billing Frequency: Yearly
Cycle Amount:      $XXX.00
Currency:          USD
Status:            Active
```

---

## Add Plan IDs to WordPress

### Step 1: Go to Course Editor

1. WordPress Admin → **Posts → Courses**
2. Edit the course that needs subscription plans
3. Scroll down to **Product / Course Variations**

### Step 2: Add/Edit Variations

For each monthly/yearly/weekly variation:

1. **Label**: `Starter $297/month (Individuals & Startups)`
2. **Price (USD)**: `297.00`
3. **Billing**: Select `Monthly` (or Weekly/Yearly)
4. **Description**: Add features (optional)
5. **PayPal Plan ID**: ← **Paste the Plan ID here**
   - Example: `I-ABC1D2E3F4G5H6I7`

### Step 3: Save the Course

Click **Save Post** and wait for confirmation.

### Step 4: Verify

1. Go to the **store page** for the course
2. Select one of the subscription variations
3. If the debug box disappears and the PayPal button shows → ✅ **Success!**

---

## Test the Setup

### Quick Test

1. **Go to course store page** (logged in as admin)
2. **Select a subscription variation**
3. **Look for the PayPal subscribe button**
4. If you see the button → Plan ID is working ✅

### Full Purchase Test (Optional)

1. Use a **PayPal sandbox buyer account**
2. Start a subscription purchase
3. Complete the payment in PayPal sandbox
4. Verify subscription appears in dashboard

---

## Troubleshooting

### ❌ Plan ID field is empty

**Problem**: You didn't paste the Plan ID

**Solution**: 
1. Go back to PayPal Developer Dashboard
2. Find the plan
3. Copy the Plan ID again
4. Paste into WordPress variation
5. Save the course

### ❌ Still see "Subscription Not Configured" error

**Problem**: Plan ID wasn't saved properly

**Solution**:
1. Edit the course again
2. Click into the Plan ID field
3. Make sure it's not empty
4. Click **Save Post** again
5. Wait 5 seconds for page to refresh

### ❌ Can't find PayPal Subscriptions section

**Problem**: You might be looking in the wrong place

**Solution**:
1. Go to [developer.paypal.com/dashboard](https://developer.paypal.com/dashboard)
2. Click **Apps & Credentials**
3. Click on your **REST API app**
4. Look in the left sidebar for "Billing", "Subscriptions", or "Products"
5. If not visible, you may need to look in the main "Tools" menu

### ❌ Different plan prices showing for different months

**Problem**: PayPal doesn't allow changing prices once plan is created

**Solution**: Create separate plans for different prices (e.g., "Starter Early Bird $199", "Starter Regular $297")

---

## Reference: Your Plan IDs

When you create plans, save them here:

| Course | Plan Name | Price | Plan ID |
|--------|-----------|-------|---------|
| Digital Marketing | Starter | $297 | `I-________________` |
| Digital Marketing | Professional | $497 | `I-________________` |
| Digital Marketing | Premium | $997 | `I-________________` |
| Other Course | Weekly | $25 | `I-________________` |

---

## Questions?

- **PayPal Help**: https://developer.paypal.com/docs/subscriptions/
- **WordPress Admin**: Tools → PayPal Plans Debug (to verify setup)

---

**Last Updated**: April 28, 2026

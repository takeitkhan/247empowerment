# Zoom Integration Pages - Setup Guide

## 🚀 Quick Setup

শুধু পেজ তৈরি করুন + **"Zoom Integration Page"** template select করুন।  
বাকি সব automatic! ✨

---

## 📋 Auto-Mapped Pages

Page slug অনুযায়ী automatic shortcode load হবে:

### 1. **Connect Zoom Account**
- **Slug**: `connect-zoom` অথবা `zoom-connect`
- **Shortcode**: `[zoom_connect_button]`
- **Features**: OAuth connection, status display, disconnect button

### 2. **My Zoom Meetings**
- **Slug**: `my-zoom-meetings` অথবা `zoom-meetings`
- **Shortcode**: `[zoom_all_meetings]`
- **Features**: All upcoming meetings table, join links

### 3. **Book Meeting**
- **Slug**: `book-meeting` অথবা `zoom-book`
- **Shortcode**: `[zoom_book_appointment]`
- **Features**: Meeting creation form, instant meeting generation

### 4. **Search Meetings**
- **Slug**: `search-meetings` অথবা `zoom-search`
- **Shortcode**: `[zoom_search_meetings]`
- **Features**: Search by topic or meeting ID

### 5. **Zoom Contacts**
- **Slug**: `zoom-contacts`
- **Shortcode**: `[zoom_show_contacts]`
- **Features**: Grid view of all contacts

### 6. **Cancel Meeting**
- **Slug**: `cancel-meeting`
- **Shortcode**: `[zoom_cancel_meeting]`
- **Features**: Meeting ID input, cancellation confirmation

### 7. **Reschedule Meeting**
- **Slug**: `reschedule-meeting`
- **Shortcode**: `[zoom_reschedule_meeting]`
- **Features**: Update topic or start time

### 8. **Zoom Token**
- **Slug**: `zoom-token`
- **Shortcode**: `[zoom_zak_token]`
- **Features**: Display ZAK token for Web SDK integration

### 9. **Meeting Details**
- **Slug**: `meeting-details`
- **Shortcode**: `[zoom_meeting_details]`
- **Features**: Full meeting information, join link

---

## 🎁 BONUS: Booking Calendar (NEW!)

**অন্যরা আপনার সাথে meeting book করতে পারবে!**

### How It Works:
1. **Guest visits** your booking calendar page
2. **Selects available time** from your calendar
3. **Enters name, email, topic**
4. **Zoom meeting auto-creates** on your account
5. **Both get confirmation email** with join link

### Setup:

#### Step 1: Set Your Availability
```
প্রতিটি user এর profile এ availability set করুন:
- Available days (Monday-Friday default)
- Working hours (9 AM - 5 PM default)
- Slot duration (30 minutes default)
```

#### Step 2: Create Booking Page
1. **WordPress Admin** → **Pages** → **Add New**
2. **Title**: "Book a Meeting" (যেকোনো নাম)
3. **Content**: Leave empty অথবা write some intro
4. **Template**: "Zoom Integration Page"
5. **Slug**: `booking` (যেকোনো slug)
6. **Custom Zoom Shortcode**: `[zoom_booking_calendar author_id="YOUR_USER_ID"]`

Replace `YOUR_USER_ID` with your WordPress user ID (mine is `1`)

**Example**:
```
[zoom_booking_calendar author_id="1"]
```

#### Step 3: Share Link
Guest users এ এই page link পাঠান:
```
http://pet.test/booking/
```

### Features:
✅ Auto-detect available slots
✅ Guest enters name & email
✅ Zoom meeting auto-creates
✅ Confirmation email sent to both
✅ Meeting link included in email

---

## ⚙️ User Availability Setup

Default settings:
```
📅 Available Days: Mon-Fri
🕐 Working Hours: 9 AM - 5 PM  
⏱️ Slot Duration: 30 minutes
```

### To Change Your Availability:

Go to **Profile** → **Edit Profile** and look for **"Zoom Availability"** section (coming soon)

Or use this database command:
```sql
UPDATE wp_usermeta 
SET meta_value = '{"enabled": true, "timezone": "UTC", "slot_duration": 30, "days": [1,2,3,4,5], "start_time": "09:00", "end_time": "17:00"}'
WHERE user_id = 1 AND meta_key = '_zoom_availability';
```

---

## 📋 Direct Shortcode Usage

### For Page Owners (Auto-book meetings):
```
[zoom_book_appointment]
```

### For Visitors (Book with someone):
```
[zoom_booking_calendar author_id="1"]
```
Replace `1` with the user ID who is offering bookings.

---

## 🔐 Security

✅ Nonce verification on all forms
✅ Email validation
✅ User authentication for bookings
✅ Database encryption for tokens
✅ CSRF protection

---

## ✅ How to Create Pages

### Step 1: Create New Page
1. Go to **WordPress Admin** → **Pages** → **Add New**

### Step 2: Set Title & Content
- **Title**: যেকোনো নাম (যেমন: "My Zoom Meetings")
- **Content**: ঐচ্ছিক (template যাই overwrite করবে)

### Step 3: Select Template
- Right sidebar → **Template** → **Zoom Integration Page**

### Step 4: Set Slug
- **Slug**: উপরের auto-mapped slugs এর মধ্যে একটা দিন

### Step 5: Publish
Done! ✅

---

## 🎯 Example Pages to Create

```
1. Page Title: Connect My Zoom
   Slug: connect-zoom
   Template: Zoom Integration Page
   
2. Page Title: My Meetings
   Slug: my-zoom-meetings
   Template: Zoom Integration Page
   
3. Page Title: Book a Zoom Call
   Slug: book-meeting
   Template: Zoom Integration Page
   
4. Page Title: Search Meetings
   Slug: search-meetings
   Template: Zoom Integration Page
   
5. Page Title: Contact List
   Slug: zoom-contacts
   Template: Zoom Integration Page
```

---

## 🔧 Advanced: Custom Shortcode Override

যদি একটি page এ অন্য shortcode দেখাতে চান:

1. Page edit করুন
2. **Template** tab scroll down করুন
3. নিচে একটি **"Custom Zoom Shortcode"** field থাকবে
4. সেখানে shortcode লিখুন: `[zoom_search_meetings]`
5. Save করুন

**Priority**:
1. Custom shortcode (যদি set করা থাকে)
2. Auto-mapped shortcode (slug অনুযায়ী)
3. Page custom content
4. Default (সব sections)

---

## 🎨 Sidebar Navigation

সব pages auto-linked থাকবে help section এ:
- Quick Links card
- Available Features card

---

## 🔐 Security

✅ OAuth 2.0 authentication
✅ AES-256 token encryption
✅ Nonce verification
✅ User capability checks
✅ XSS/CSRF protection

---

## 📱 Responsive Design

সব shortcodes mobile-optimized ✨

---

## 🆘 Troubleshooting

### Shortcode not showing?
1. ✅ Page slug সঠিক? (উপরের list check করুন)
2. ✅ Template "Zoom Integration Page" select করেছেন?
3. ✅ User logged in?
4. ✅ Zoom connected?

### Token issues?
- Check WordPress debug.log:
```
tail -50 wp/wp-content/debug.log
```

---

## 📚 Available Shortcodes (Direct Use)

যদি template ছাড়া direct shortcode use করতে চান:

```php
[zoom_connect_button]
[zoom_all_meetings]
[zoom_book_appointment]
[zoom_search_meetings]
[zoom_show_contacts]
[zoom_cancel_meeting]
[zoom_reschedule_meeting]
[zoom_meeting_details meeting_id="123456"]
[zoom_zak_token]
```

---

## 🎓 Integration Points

**Sidebar menu**:
```
/template-custom/auth/common-parts/editprofilemenu.php
```

**Help section links**:
All auto-generated with proper URLs

---

Happy Zooming! 🚀

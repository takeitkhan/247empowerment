# MM Referral Chat - Quick Start Guide

## 🎯 ৫ মিনিটের মধ্যে শুরু করুন

### ধাপ ১: প্লাগইন অ্যাক্টিভেট করুন
```
WordPress Admin Dashboard
→ Plugins
→ MM Referral Chat
→ Activate
```

### ধাপ ২: ডাটাবেস টেবিল যাচাই করুন (ঐচ্ছিক)
প্লাগইন activate হওয়ার সাথে সাথে এই টেবিল তৈরি হবে:
- `wp_chat_conversations`
- `wp_chat_messages`

### ধাপ ৩: ফ্রন্টএন্ডে দেখুন
```
1. একজন ইউজার হিসাবে লগ ইন করুন
2. সাইটের নিচে ডান কোণে 💬 আইকন দেখুন
3. আইকনে ক্লিক করুন চ্যাট খুলতে
```

---

## 💬 চ্যাট ব্যবহার করুন

### Conversation শুরু করুন
```
1. চ্যাট খুলুন (💬 বোতামে ক্লিক)
2. "Add Chat" ট্যাবে যান
3. Referral partner খুঁজুন এবং ক্লিক করুন
4. Conversation স্বয়ংক্রিয়ভাবে শুরু হবে
```

### বার্তা পাঠান
```
1. চ্যাট উইন্ডোতে যান
2. নীচে ইনপুট ফিল্ডে টেক্সট লিখুন
3. প্রেরণ বোতাম (📤) ক্লিক করুন অথবা Enter চাপুন
4. বার্তা তাত্ক্ষণিকভাবে পাঠানো হবে
```

### অপঠিত বার্তা দেখুন
```
- চ্যাট বোতামে লাল ব্যাজ (সংখ্যা সহ) দেখবেন
- "Conversations" ট্যাবে প্রতিটি conversation এর পাশে সংখ্যা
```

---

## 🔧 টেকনিক্যাল সেটাপ

### প্রয়োজনীয়তা
- WordPress 5.0+
- PHP 7.2+
- MySQL 5.7+
- jQuery (ইতিমধ্যে WordPress এ আছে)

### Referral সেটআপ
দুজন ইউজার চ্যাট করতে পারার জন্য:

**ডাটাবেস এ সরাসরি:**
```sql
-- User B কে User A দ্বারা রেফার করা হয়েছে সেট করুন
UPDATE wp_usermeta 
SET meta_value = 'john' 
WHERE user_id = 10 AND meta_key = 'referrer';
```

**অথবা WordPress কোডে:**
```php
update_user_meta(10, 'referrer', 'john'); // user_login ব্যবহার করুন
// অথবা
update_user_meta(10, 'referrer', 5); // user_id ব্যবহার করুন
```

---

## 📊 ফ্রন্টএন্ড ওয়াকথ্রু

### চ্যাট ইন্টারফেস দেখতে কেমন

```
┌─────────────────────────────┐
│  Referral Chat          ✕   │
├─────────────────────────────┤
│ Conversations | Add Chat    │
├─────────────────────────────┤
│ 👤 John Smith          2    │  ← 2 অপঠিত বার্তা
│ "Hey, how are you?"         │
│                             │
│ 👤 Jane Doe               │
│ "Let's connect"             │
│                             │
├─────────────────────────────┤
│ [Type message...] [📤]      │
└─────────────────────────────┘
```

### Message Window

```
┌──────────────────────────────┐
│  ← John Smith                │
├──────────────────────────────┤
│                              │
│                              │
│         Hi there! 👋  3:45   │
│                              │
│  How are you doing?  ← 3:46  │
│                              │
│                              │
├──────────────────────────────┤
│ [Type a message...]    [📤]  │
└──────────────────────────────┘
```

---

## 🔐 নিরাপত্তা বিস্তারিত

### প্রতিটি AJAX Request এ চেক হয়
```
✓ User is logged in
✓ Nonce token is valid
✓ User can access this conversation
✓ Sender is authorized
✓ Input is sanitized
```

### Referral Relationship Verification
```
✓ User A referenced User B? → Chat allowed
✓ User B referenced User A? → Chat allowed
✓ No relationship? → Chat blocked
```

---

## 🚨 সাধারণ সমস্যা সমাধান

### সমস্যা: "চ্যাট বোতাম দেখা যাচ্ছে না"

**সমাধান:**
```
1. নিশ্চিত করুন আপনি লগ ইন করছেন
2. ব্রাউজার কনসল খুলুন (F12)
3. JavaScript errors দেখুন
4. Plugin active আছে কিনা যাচাই করুন
5. wp-content/debug.log দেখুন
```

### সমস্যা: "বার্তা পাঠানো কাজ করছে না"

**সমাধান:**
```
1. Network tab দেখুন (F12 → Network)
2. AJAX request সফল? (200 status code?)
3. Nonce token valid? (wp_create_nonce সেট?)
4. Database টেবিল exist করে? 
5. Error message console এ?
```

### সমস্যা: "আমি যাকে রেফার করেছি তাকে দেখতে পাচ্ছি না"

**সমাধান:**
```
1. Referrer meta value সঠিক?
   SELECT * FROM wp_usermeta 
   WHERE meta_key = 'referrer'
   
2. User ID vs Username (যেকোনোটি হতে পারে)

3. UserProfileData class লোড হচ্ছে?
```

### সমস্যা: "Conversation তৈরি হচ্ছে না"

**সমাধান:**
```
1. দুজনের মধ্যে referral সম্পর্ক আছে?
2. MM_Chat_Manager::can_chat() check করুন
3. Database টেবিল খালি তো কিছু আছে?
4. Permission issue? User IDs যাচাই করুন
```

---

## 📈 Performance Tips

### Polling Interval পরিবর্তন করুন
```php
// mm-referral-chat.php এ:
wp_localize_script('mm-referral-chat-script', 'mmChat', [
    'pollingInterval' => 5000, // বাড়ান (5 সেকেন্ড)
]);
```

বড় ইনস্ট্যান্সের জন্য polling interval বাড়ান।

### Message Limit
```php
// class-chat-database.php এ:
$limit = 50; // প্রতিটি load এ messages এর সংখ্যা
```

---

## 🔗 Related Files

```
Plugin File
└── /wp-content/plugins/mm-referral-chat/

Core Classes
├── includes/class-chat-database.php      ← Database queries
├── includes/class-chat-manager.php       ← Referral verification
├── includes/class-message-handler.php    ← Message logic
└── includes/class-chat-ajax.php          ← AJAX endpoints

Frontend
├── assets/css/chat-styles.css            ← UI styles
├── assets/js/chat-interface.js           ← Main script
└── templates/chat-interface.php          ← PHP template

Documentation
├── readme.txt                            ← Plugin docs
├── MM_REFERRAL_CHAT_IMPLEMENTATION.md    ← Full guide
└── MM_REFERRAL_CHAT_QUICK_START.md       ← এই ফাইল
```

---

## 💡 উন্নত ব্যবহার

### Custom Hook যোগ করুন (ভবিষ্যত)
```php
// do_action পয়েন্ট যোগ করা হতে পারে:
do_action('mm_chat_message_sent', $conversation_id, $sender_id, $message);
do_action('mm_chat_before_verification', $user1_id, $user2_id);
```

### Database সরাসরি Query করুন
```php
global $wpdb;

// সব conversation দেখুন
$conversations = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}chat_conversations"
);

// সব message দেখুন
$messages = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}chat_messages LIMIT 10"
);
```

---

## ✅ Verification Checklist

প্লাগইন সঠিকভাবে কাজ করছে কিনা যাচাই করুন:

- [ ] Plugin active রয়েছে
- [ ] Database table তৈরি হয়েছে (`wp_chat_conversations`, `wp_chat_messages`)
- [ ] লগ ইন করা ইউজার চ্যাট বোতাম দেখে
- [ ] দুজন referral partner চ্যাট শুরু করতে পারে
- [ ] বার্তা পাঠানো এবং পাওয়া কাজ করছে
- [ ] অপঠিত counter সঠিক
- [ ] Real-time updates কাজ করছে (polling)

---

## 📞 Support Resources

### Debug করার জন্য
1. **Browser Console**: F12 → Console tab
2. **WordPress Debug Log**: `wp-content/debug.log`
3. **MySQL Direct Access**: Database tool ব্যবহার করুন
4. **Server Logs**: `/var/log/apache2/` বা `/var/log/nginx/`

### কোড দেখুন
```
Main file: mm-referral-chat.php (সব কিছু শুরু হয় এখানে)
├── Database: class-chat-database.php
├── Logic: class-chat-manager.php + class-message-handler.php
├── AJAX: class-chat-ajax.php
├── Frontend: chat-interface.js
└── Styling: chat-styles.css
```

---

## 🎉 আপনি প্রস্তুত!

এখন শুরু করুন:
1. ✅ প্লাগইন অ্যাক্টিভেট করুন
2. ✅ দুজন ইউজার তৈরি করুন
3. ✅ Referral সম্পর্ক সেট করুন
4. ✅ লগ ইন করুন এবং চ্যাট করুন!

আনন্দদায়ক চ্যাটিং! 💬

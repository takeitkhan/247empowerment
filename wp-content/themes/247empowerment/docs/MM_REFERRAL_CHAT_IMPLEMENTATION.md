# MM Referral Chat Plugin - ইমপ্লিমেন্টেশন সম্পূর্ণ

## 📦 কী তৈরি হয়েছে

একটি সম্পূর্ণ, প্রোডাকশন-রেডি চ্যাটিং প্লাগইন যা referral কানেকশনের উপর ভিত্তি করে কাজ করে।

## 📁 ফাইল স্ট্রাকচার

```
wp-content/plugins/mm-referral-chat/
├── mm-referral-chat.php                 # Main plugin file
├── readme.txt                           # Documentation
├── includes/
│   ├── class-chat-database.php          # Database operations
│   ├── class-chat-manager.php           # Referral verification & logic
│   ├── class-message-handler.php        # Message operations
│   └── class-chat-ajax.php              # AJAX endpoints
├── assets/
│   ├── css/
│   │   └── chat-styles.css              # Chat UI styles (Tailwind-compatible)
│   └── js/
│       └── chat-interface.js            # Frontend JavaScript
└── templates/
    └── chat-interface.php               # PHP template
```

## 🔧 মূল উপাদানগুলি

### 1. **MM_Chat_Database** (Database Operations)
- টেবিল তৈরি (activate hook এ)
- Conversation CRUD
- Message CRUD
- Unread count tracking
- Message searching

### 2. **MM_Chat_Manager** (Referral Verification)
- Two users এর মধ্যে chat permission check করে
- Referral relationship verify করে (যদি UserProfileData থাকে)
- Chat partners তালিকা তৈরি করে
- Conversation management

### 3. **MM_Message_Handler** (Message Logic)
- Message পাঠানো এবং পাওয়া
- Message read status tracking
- Unread count হিসাব করা
- Message search functionality

### 4. **MM_Chat_AJAX** (AJAX Endpoints)
সব AJAX handlers এর জন্য security verification:
- `mm_chat_get_partners` - রেফারেল পার্টনার লিস্ট
- `mm_chat_get_conversations` - কথোপকথন লিস্ট
- `mm_chat_get_messages` - নির্দিষ্ট কথোপকথনের মেসেজ
- `mm_chat_send_message` - নতুন মেসেজ পাঠান
- `mm_chat_start_conversation` - নতুন কথোপকথন শুরু করুন
- `mm_chat_mark_read` - মেসেজ read চিহ্নিত করুন
- `mm_chat_get_unread_count` - মোট অপঠিত সংখ্যা

### 5. **Frontend Interface** (JavaScript + CSS)
- **Chat Toggle Button**: সাইটের নিচে ডান কোণে
- **Conversations Tab**: সমস্ত কথোপকথন দেখান
- **Add Chat Tab**: নতুন চ্যাট শুরু করুন
- **Real-time Updates**: AJAX polling (প্রতি 3 সেকেন্ডে)
- **Responsive Design**: মোবাইল + ডেস্কটপ
- **Message Display**: Sender avatars, timestamps সহ

## 🔐 সিকিউরিটি বৈশিষ্ট্য

✅ WordPress nonce verification (প্রতিটি AJAX এ)
✅ `is_user_logged_in()` চেক
✅ User authorization (শুধু নিজের কথোপকথন দেখতে পারে)
✅ Input sanitization (`sanitize_textarea_field()`)
✅ SQL injection prevention (`$wpdb->prepare()`)
✅ Referral relationship verification

## 💾 ডাটাবেস টেবিল

### wp_chat_conversations
```sql
CREATE TABLE wp_chat_conversations (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_1_id BIGINT,
    user_2_id BIGINT,
    created_at DATETIME,
    updated_at DATETIME,
    is_archived TINYINT,
    UNIQUE KEY (user_1_id, user_2_id)
);
```

### wp_chat_messages
```sql
CREATE TABLE wp_chat_messages (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    conversation_id BIGINT,
    sender_id BIGINT,
    message LONGTEXT,
    is_read TINYINT,
    read_at DATETIME,
    created_at DATETIME,
    FOREIGN KEY (conversation_id) REFERENCES wp_chat_conversations(id) ON DELETE CASCADE
);
```

## 🚀 কীভাবে ব্যবহার করবেন

### 1. প্লাগইন অ্যাক্টিভেট করুন
- WordPress admin dashboard এ যান
- Plugins → Find "MM Referral Chat"
- Activate বোতামে ক্লিক করুন

### 2. ডাটাবেস টেবিল তৈরি হবে
- Activation hook স্বয়ংক্রিয়ভাবে টেবিল তৈরি করবে
- কোনো ম্যানুয়াল সেটআপ প্রয়োজন নেই

### 3. ফ্রন্টএন্ডে ব্যবহার করুন
- লগ ইন করা ইউজাররা সাইটে চ্যাট বোতাম দেখবে
- তারা referral partners এর সাথে চ্যাট করতে পারবে

## 🔗 Integration Points

### UserProfileData Class
যদি থাকে তাহলে ব্যবহার করে:
```php
UserProfileData::getReferredUsersBy($user)
```

Fallback মেটা query ব্যবহার করে:
```php
get_users([
    'meta_query' => [
        [
            'key' => 'referrer',
            'value' => [$user_id, $user_login],
            'compare' => 'IN'
        ]
    ]
]);
```

### Gamification Plugin
ভবিষ্যতে পয়েন্ট দেওয়ার জন্য:
```php
// এক্সটেনশন এর জন্য প্রস্তুত
// কোনো কোড যোগ করা হয়নি এখনো
```

## ⚙️ Customization

### Chat Position পরিবর্তন করুন
`chat-interface.js` এ:
```javascript
.mm-chat-container {
    bottom: 20px;  // এটি পরিবর্তন করুন
    right: 20px;   // বা এটি
}
```

### Polling Interval পরিবর্তন করুন
`mm-referral-chat.php` এ:
```php
wp_localize_script(..., [
    'pollingInterval' => 3000, // মিলিসেকেন্ডে
]);
```

### Colors কাস্টমাইজ করুন
`chat-styles.css` এ CSS variables পরিবর্তন করুন:
```css
:root {
    --chat-primary: #007bff;    /* Primary color */
    --chat-secondary: #6c757d;  /* Secondary color */
    ...
}
```

## 📊 API Methods

### Backend Classes

#### MM_Chat_Database
```php
// Static methods
MM_Chat_Database::get_conversation($user1, $user2)
MM_Chat_Database::get_or_create_conversation($user1, $user2)
MM_Chat_Database::get_user_conversations($user_id, $limit)
MM_Chat_Database::get_messages($conversation_id, $limit)
MM_Chat_Database::insert_message($conv_id, $sender_id, $msg)
MM_Chat_Database::mark_as_read($message_id)
MM_Chat_Database::get_unread_count($conversation_id, $user_id)
```

#### MM_Chat_Manager
```php
// Static methods
MM_Chat_Manager::can_chat($user1, $user2)
MM_Chat_Manager::get_chat_partners($user_id)
MM_Chat_Manager::start_conversation($user1, $user2)
MM_Chat_Manager::get_conversation($conv_id, $user_id)
```

#### MM_Message_Handler
```php
// Static methods
MM_Message_Handler::send_message($conv_id, $sender_id, $message)
MM_Message_Handler::get_messages($conv_id, $user_id, $limit)
MM_Message_Handler::mark_as_read($msg_id, $user_id)
MM_Message_Handler::get_unread_count($user_id)
```

## 🧪 Testing করুন

### ম্যানুয়ালি টেস্টিং
1. দুজন ইউজার তৈরি করুন
2. একজনকে অন্যজন রেফার করুন (user meta `referrer` সেট করুন)
3. উভয়ে লগ ইন করুন
4. চ্যাট বোতাম দেখুন এবং মেসেজ পাঠান

### Debug করুন
WordPress debug log দেখুন:
```
wp-content/debug.log
```

## 📝 পরবর্তী উন্নতিগুলি

আগামীতে যোগ করা যেতে পারে:
- [ ] WebSocket রিয়েল-টাইম আপডেট
- [ ] ফাইল শেয়ারিং
- [ ] মেসেজ encryption
- [ ] ব্লক/আনব্লক ইউজার
- [ ] মেসেজ রিঅ্যাকশন/emoji
- [ ] ভয়েস/ভিডিও (Zoom integration)
- [ ] Group chat
- [ ] এন্ড-টু-এন্ড এনক্রিপশন

## ✅ Checklist

- ✅ Plugin structure সঠিক
- ✅ Database tables সৃষ্টি হয়
- ✅ AJAX endpoints security সহ
- ✅ Referral verification কাজ করছে
- ✅ Frontend UI সম্পূর্ণ
- ✅ Responsive design
- ✅ Real-time updates (polling)
- ✅ Documentation সম্পূর্ণ

## 🆘 সমস্যা সমাধান

### "Conversation not found"
- নিশ্চিত করুন যে দুজনের মধ্যে referral সম্পর্ক আছে
- Debug log দেখুন

### Messages দেখা যাচ্ছে না
- AJAX request সফল কিনা ব্রাউজার console দেখুন
- Database table exist করছে কিনা check করুন

### Chat button দেখা যাচ্ছে না
- নিশ্চিত করুন you're logged in
- JavaScript console এ errors check করুন
- Plugin active আছে কিনা verify করুন

---

## 📞 সাপোর্ট

যেকোনো প্রশ্ন বা সমস্যার জন্য:
1. Debug log দেখুন (`wp-content/debug.log`)
2. Browser console দেখুন (F12 → Console)
3. Database directly check করুন

**Plugin Version**: 1.0.0  
**Created**: February 27, 2026  
**Status**: Production Ready ✅

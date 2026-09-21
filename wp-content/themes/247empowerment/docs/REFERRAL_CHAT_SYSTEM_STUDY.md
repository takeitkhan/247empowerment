# Referral কানেকশন ভিত্তিক চ্যাটিং সিস্টেম - গভীর অধ্যয়ন

## ১. বর্তমান Referral সিস্টেম কীভাবে কাজ করে

### ১.১ Referral ডেটা স্ট্রাকচার
- **মেটা কী**: `referrer` (ইউজার মেটা তে স্টোর করা)
- **মান**: রেফারারের ইউজার ID বা ইউজার লগইন নেম উভয়ই হতে পারে
- **লোকেশন**: WordPress `wp_usermeta` টেবিলে সংরক্ষিত

**উদাহরণ**:
```
User A (ID: 5, login: 'john') 
  ↓ refers
User B (ID: 10, login: 'jane') 
  → User B এর meta field 'referrer' = 'john' or 5
```

### ১.২ Referral কানেকশন খোঁজা (কোড)
```php
// UserProfileData::getReferredUsersBy($referrer_user)
$args = [
    'meta_query' => [
        [
            'key'     => 'referrer',
            'value'   => [$referrer_id, $referrer_login], // ID বা login
            'compare' => 'IN'
        ]
    ]
];
$referred_users = get_users($args);
```

**বৈশিষ্ট্য**:
- এটি একটি one-to-many সম্পর্ক (১ জন রেফারার → অনেক জন রেফারড)
- শুধুমাত্র **এক-দিকীয় সম্পর্ক** (যে রেফার করেছে তার দিক থেকে)

### ১.৩ Referral UI (বর্তমান)
- **পেজ**: `/{username}/referrals/` 
- **দেখায়**: কোন ইউজার এই ইউজারকে রেফার করেছে তার তালিকা
- **ফাংশন**: AJAX এ "Load More" সুবিধা

---

## ২. চ্যাটিং সিস্টেমের জন্য প্রয়োজনীয় নতুন টেবিল/ডেটা

### ২.১ Chat Relationships নির্ধারণ করা
দুজন ইউজার চ্যাট করতে পারবে যদি:
- ✅ **Option A**: তারা উভয়ে একে অপরকে রেফার করেছে (mutual connection)
- ✅ **Option B**: তাদের মধ্যে রেফারার-রেফারড সম্পর্ক আছে
- ✅ **Option C**: Custom সম্পর্ক টেবিল তৈরি করা (ভবিষ্যতের জন্য নমনীয়)

### ২.২ চ্যাটের জন্য নতুন টেবিল
```sql
-- চ্যাট conversations টেবিল
CREATE TABLE wp_chat_conversations (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_1_id BIGINT NOT NULL,           -- প্রথম ইউজার
    user_2_id BIGINT NOT NULL,           -- দ্বিতীয় ইউজার
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_archived TINYINT DEFAULT 0,
    UNIQUE KEY unique_conversation (user_1_id, user_2_id),
    INDEX idx_user1 (user_1_id),
    INDEX idx_user2 (user_2_id)
);

-- চ্যাট মেসেজেস টেবিল
CREATE TABLE wp_chat_messages (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    conversation_id BIGINT NOT NULL,
    sender_id BIGINT NOT NULL,           -- যে পাঠিয়েছে
    message TEXT NOT NULL,
    is_read TINYINT DEFAULT 0,
    read_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES wp_chat_conversations(id) ON DELETE CASCADE,
    INDEX idx_conversation (conversation_id),
    INDEX idx_sender (sender_id),
    INDEX idx_created (created_at)
);

-- চ্যাট নোটিফিকেশনস (অপশনাল)
CREATE TABLE wp_chat_notifications (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    message_id BIGINT NOT NULL,
    recipient_id BIGINT NOT NULL,
    is_sent TINYINT DEFAULT 0,
    sent_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES wp_chat_messages(id) ON DELETE CASCADE,
    INDEX idx_recipient (recipient_id)
);
```

---

## ৩. চ্যাটিং প্লাগইনের আর্কিটেকচার

### ৩.১ Plugin Structure
```
wp-content/plugins/mm-referral-chat/
├── mm-referral-chat.php              # Main plugin file
├── includes/
│   ├── class-chat-manager.php        # চ্যাট ম্যানেজমেন্ট লজিক
│   ├── class-message-handler.php     # মেসেজ প্রসেসিং
│   ├── class-chat-ajax.php           # AJAX এন্ডপয়েন্টস
│   └── class-chat-database.php       # ডাটাবেস কোয়েরিস
├── assets/
│   ├── js/
│   │   ├── chat-interface.js         # ফ্রন্টএন্ড ইন্টারঅ্যাকশন
│   │   └── chat-notifications.js     # নোটিফিকেশন হ্যান্ডলিং
│   └── css/
│       └── chat-styles.css           # চ্যাট UI স্টাইলিং
├── templates/
│   ├── chat-modal.php                # চ্যাট মোডাল টেমপ্লেট
│   ├── chat-list.php                 # কথোপকথনের তালিকা
│   └── chat-window.php               # চ্যাট উইন্ডো
└── readme.txt
```

### ৩.২ মূল ফিচারগুলি

#### A. Conversation Management
```php
class ChatManager {
    // দুই ইউজারের মধ্যে কথোপকথন শুরু করা
    public function start_conversation($user_1_id, $user_2_id) {}
    
    // সম্পর্ক যাচাই করা (referral connection)
    public function can_chat($user_1_id, $user_2_id) {}
    
    // কথোপকথনের তালিকা পাওয়া
    public function get_conversations($user_id, $limit = 20) {}
}
```

#### B. Message Handling
```php
class MessageHandler {
    // নতুন মেসেজ পাঠানো
    public function send_message($conversation_id, $sender_id, $message) {}
    
    // মেসেজ মার্ক করা (পড়া হয়েছে)
    public function mark_as_read($message_id) {}
    
    // কথোপকথনের মেসেজগুলি পাওয়া
    public function get_messages($conversation_id, $limit = 50, $offset = 0) {}
}
```

#### C. AJAX Endpoints
```
POST /wp-admin/admin-ajax.php?action=chat_start_conversation
POST /wp-admin/admin-ajax.php?action=chat_send_message
POST /wp-admin/admin-ajax.php?action=chat_get_conversations
POST /wp-admin/admin-ajax.php?action=chat_get_messages
POST /wp-admin/admin-ajax.php?action=chat_mark_read
```

---

## ৪. ফ্রন্টএন্ড ইন্টারফেস

### ৪.১ চ্যাটিং UI স্থান
- **Option 1**: সাইডবার (Messenger স্টাইল)
- **Option 2**: মোডাল উইন্ডো (পপ-আপ)
- **Option 3**: ডেডিকেটেড পেজ (/{username}/chat/)

### ৪.২ UI কম্পোনেন্টস
1. **Conversations List**
   - অনুসন্ধান ফিচার
   - সর্বশেষ মেসেজ প্রিভিউ
   - অপঠিত মেসেজ কাউন্ট

2. **Chat Window**
   - মেসেজ ইতিহাস
   - ইনপুট ফিল্ড
   - পাঠানোর বোতাম
   - রিয়েল-টাইম আপডেট (AJAX polling)

3. **Notifications**
   - নতুন মেসেজ নোটিফিকেশন
   - Desktop notifications (optional)
   - ব্রাউজার ট্যাব টাইটেল আপডেট

---

## ৫. সিকিউরিটি বিবেচনা

### ৫.১ Authentication & Authorization
```php
// প্রতিটি AJAX এ চেক করা প্রয়োজন:
if (!is_user_logged_in()) {
    wp_send_json_error('Not logged in', 401);
}

$current_user_id = get_current_user_id();

// যাচাই করা প্রয়োজন:
// - ব্যবহারকারী যে কথোপকথন অ্যাক্সেস করছে তার অংশ
// - প্রেরক সঠিক ব্যবহারকারী
```

### ৫.২ Input Sanitization
```php
$message = sanitize_textarea_field($_POST['message']);
$conversation_id = absint($_POST['conversation_id']);
```

### ৫.৩ Referral Verification
```php
// চ্যাট শুরু করার আগে:
public function can_chat($user_1, $user_2) {
    // উভয় ইউজার এক্সিস্ট করে?
    // তাদের মধ্যে referral সম্পর্ক আছে?
    // কেউ ব্লক করেনি?
}
```

---

## ৬. ইমপ্লিমেন্টেশন পদ্ধতি

### Phase 1: ডাটাবেস ও কোর লজিক
1. টেবিল তৈরি (plugin activation)
2. `ChatManager` ক্লাস তৈরি করা
3. `MessageHandler` ক্লাস তৈরি করা

### Phase 2: AJAX Endpoints
1. Conversation CRUD
2. Message CRUD
3. Notification ম্যানেজমেন্ট

### Phase 3: Frontend Interface
1. চ্যাট মোডাল/পেজ তৈরি করা
2. AJAX ক্লায়েন্ট স্ক্রিপ্ট
3. Real-time আপডেট

### Phase 4: উন্নত ফিচার
1. টাইপিং ইন্ডিকেটর
2. মেসেজ সার্চ
3. ফাইল শেয়ারিং (optional)
4. ভয়েস/ভিডিও চ্যাট (integration with Zoom)

---

## ৭. বর্তমান সিস্টেমের সাথে একীকরণ

### ৭.১ Gamification Plugin এর সাথে
- চ্যাট এক্টিভিটি জন্য পয়েন্ট দেওয়ার সুযোগ
- মেসেজ পাঠানোর জন্য অ্যাকশন লগ করা

### ৭.২ User Profile এর সাথে
- চ্যাট বোতাম যোগ করা (referral partners এর জন্য)
- Chat indicator showing unread count

### ৭.৩ Theme এর সাথে
- Tailwind CSS ব্যবহার করে স্টাইল করা
- বর্তমান UI প্যাটার্ন অনুসরণ করা

---

## ৮. ডেটা ফ্লো ডায়াগ্রাম

```
User A (Sender)
    ↓
    └─→ [AJAX: send_message] 
        ↓
        └─→ [ChatManager: Verify relationship]
            ↓ ✓
            └─→ [MessageHandler: Process message]
                ↓
                └─→ [Database: wp_chat_messages]
                    ↓
                    └─→ [User B receives via AJAX polling]
                        ↓
                        └─→ [mark_as_read when read]
```

---

## সংক্ষিপ্ত সারসংক্ষেপ

| বিষয় | বর্তমান | প্রয়োজনীয় |
|------|--------|-----------|
| **Referral সম্পর্ক** | ✅ User Meta এ স্টোর (one-way) | ✅ ব্যবহার করা যায় কিন্তু verification প্রয়োজন |
| **চ্যাট টেবিল** | ❌ নেই | ✅ তৈরি করতে হবে |
| **মেসেজ স্টোরেজ** | ❌ নেই | ✅ তৈরি করতে হবে |
| **AJAX এন্ডপয়েন্টস** | ✅ পদ্ধতি আছে | ✅ চ্যাট জন্য নতুন তৈরি করতে হবে |
| **UI Interface** | ✅ Tailwind + Bootstrap | ✅ সম্প্রসারিত করতে হবে |
| **Real-time Updates** | ⚠️ Polling এর মাধ্যমে | ✅ AJAX polling ব্যবহার করতে হবে |

# MM Referral Chat Plugin

একটি WordPress প্লাগইন যা referral কানেকশনের উপর ভিত্তি করে ব্যবহারকারীদের মধ্যে চ্যাট সুবিধা প্রদান করে।

## বৈশিষ্ট্য

- ✅ **Referral-based Messaging**: শুধুমাত্র যাদের মধ্যে রেফারেল সম্পর্ক আছে তারা চ্যাট করতে পারে
- ✅ **Real-time Conversations**: AJAX polling এর মাধ্যমে রিয়েল-টাইম আপডেট
- ✅ **Message History**: সমস্ত বার্তা ডাটাবেসে সংরক্ষিত
- ✅ **Unread Count**: পড়া হয়নি এমন বার্তার সংখ্যা দেখায়
- ✅ **User Avatars**: প্রোফাইল ফটো সহ চ্যাট ইন্টারফেস
- ✅ **Responsive Design**: মোবাইল এবং ডেস্কটপে কাজ করে
- ✅ **Security**: Nonce verification এবং user authentication

## ইনস্টলেশন

1. এই ফোল্ডার `/wp-content/plugins/` এ স্থাপন করুন
2. WordPress admin এ যান এবং প্লাগইনটি অ্যাক্টিভেট করুন
3. ডেটাবেস টেবিল স্বয়ংক্রিয়ভাবে তৈরি হবে

## কীভাবে কাজ করে

### Referral Verification
প্লাগইনটি দুটি ব্যবহারকারী চ্যাট করতে পারে কিনা তা যাচাই করে `UserProfileData::getReferredUsersBy()` ব্যবহার করে।

দুজন ইউজার চ্যাট করতে পারে যদি:
- একজন অন্যজনকে রেফার করেছে, অথবা
- তাদের মধ্যে রেফারেল সম্পর্ক আছে

### ডাটাবেস স্ট্রাকচার

#### wp_chat_conversations
```
id (PRIMARY KEY)
user_1_id (BIGINT)
user_2_id (BIGINT)
created_at (DATETIME)
updated_at (DATETIME)
is_archived (TINYINT)
```

#### wp_chat_messages
```
id (PRIMARY KEY)
conversation_id (BIGINT, FOREIGN KEY)
sender_id (BIGINT)
message (LONGTEXT)
is_read (TINYINT)
read_at (DATETIME)
created_at (DATETIME)
```

### AJAX Endpoints

#### 1. Conversations পান
```
POST /wp-admin/admin-ajax.php?action=mm_chat_get_conversations
Parameters:
  - nonce: wp_create_nonce('mm_chat_nonce')
  - limit: 20 (optional)
  - offset: 0 (optional)
```

#### 2. Partners পান (রেফারেল পার্টনার)
```
POST /wp-admin/admin-ajax.php?action=mm_chat_get_partners
Parameters:
  - nonce: wp_create_nonce('mm_chat_nonce')
  - limit: 20 (optional)
```

#### 3. Messages পান
```
POST /wp-admin/admin-ajax.php?action=mm_chat_get_messages
Parameters:
  - nonce: wp_create_nonce('mm_chat_nonce')
  - conversation_id: [conversation_id]
  - limit: 50 (optional)
```

#### 4. Message পাঠান
```
POST /wp-admin/admin-ajax.php?action=mm_chat_send_message
Parameters:
  - nonce: wp_create_nonce('mm_chat_nonce')
  - conversation_id: [conversation_id]
  - message: [message_text]
```

#### 5. Conversation শুরু করুন
```
POST /wp-admin/admin-ajax.php?action=mm_chat_start_conversation
Parameters:
  - nonce: wp_create_nonce('mm_chat_nonce')
  - user_id: [other_user_id]
```

#### 6. Unread Count পান
```
POST /wp-admin/admin-ajax.php?action=mm_chat_get_unread_count
Parameters:
  - nonce: wp_create_nonce('mm_chat_nonce')
```

## Frontend ইন্টারফেস

### Chat Toggle Button
সাইটের নিচের ডান কোণে একটি চ্যাট বোতাম রয়েছে।

### Chat Window Features
1. **Conversations Tab**: সমস্ত চ্যাট কথোপকথন
2. **Add Chat Tab**: নতুন চ্যাট শুরু করতে রেফারেল পার্টনার খুঁজুন
3. **Message History**: সম্পূর্ণ চ্যাট ইতিহাস দেখুন
4. **Real-time Updates**: নতুন বার্তা স্বয়ংক্রিয়ভাবে আসে

## Hooks এবং Filters

কাস্টমাইজেশনের জন্য future updates এ যোগ করা হবে।

## Security

- ✅ WordPress nonce verification
- ✅ User authentication check
- ✅ User authorization (কেবলমাত্র তাদের নিজস্ব কথোপকথন দেখতে পারে)
- ✅ Message sanitization
- ✅ SQL injection prevention

## সাপোর্ট করা ক্লাস

এই প্লাগইনটি নিম্নলিখিত কাস্টম ক্লাসের উপর নির্ভর করে:

### UserProfileData
`/wp-content/themes/mm/inc/UserProfileData.php`

যদি এই ক্লাস উপলব্ধ না থাকে, প্লাগইনটি fallback meta query ব্যবহার করে।

## Troubleshooting

### চ্যাট সুইচ দেখা যাচ্ছে না?
- নিশ্চিত করুন আপনি লগ ইন করছেন
- ব্রাউজার কনসলে JavaScript errors দেখুন

### বার্তা পাঠানো কাজ করছে না?
- Nonce token যাচাই করুন
- AJAX URL সঠিক আছে কিনা নিশ্চিত করুন
- WordPress debug log দেখুন (`wp-content/debug.log`)

### Referral সম্পর্ক কাজ করছে না?
- `get_user_meta($user_id, 'referrer', true)` এর মান যাচাই করুন
- UserProfileData ক্লাস সঠিকভাবে লোড হচ্ছে কিনা চেক করুন

## ভবিষ্যত উন্নতি

- [ ] WebSocket এর মাধ্যমে রিয়েল-টাইম আপডেট
- [ ] ফাইল শেয়ারিং
- [ ] মেসেজ সার্চ
- [ ] টাইপিং ইন্ডিকেটর
- [ ] অফলাইন বার্তা সঞ্চয়
- [ ] ভয়েস/ভিডিও কল (Zoom integration)
- [ ] মেসেজ রিয়েকশন

## লাইসেন্স

MIT License

## লেখক

MM Team

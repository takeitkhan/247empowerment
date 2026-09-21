# Frontend Chat Bubble Implementation Guide

## Overview
The MM AI Chat plugin includes a fully functional **frontend chat bubble** that visitors can click to chat with your AI. The chat bubble communicates with REST API endpoints to provide a seamless chat experience.

## Architecture

### How It Works: Flow Diagram

```
Visitor → Click Chat Bubble (💬)
    ↓
JavaScript Initializes
    ↓
API Call: POST /chat/initiate
    ↓
Session Created (conversation_id generated)
    ↓
Session Stored in Browser LocalStorage
    ↓
Chat Window Opens with Welcome Message
    ↓
Visitor Types Message → Click Send
    ↓
API Call: POST /chat/message
    ↓
AI Response via OpenAI
    ↓
Message Displayed in Chat
    ↓
Visitor can Escalate → API Call: POST /chat/escalate/...
```

## File Structure

```
public/
├── class-widget.php              # HTML structure for chat widget
├── class-enqueue.php             # JavaScript & CSS loading
└── assets/
    ├── js/
    │   └── chat-widget.js        # Main widget logic (API calls)
    └── css/
        └── chat-widget.css       # Widget styling
```

## Core Components

### 1. **Widget HTML** (`class-widget.php`)
- Renders chat bubble button (💬)
- Renders chat window container
- Displays message area and input field
- Auto-loads on `wp_footer` hook

### 2. **JavaScript Handler** (`chat-widget.js`)
- Manages all user interactions
- Makes API calls using jQuery AJAX
- Handles session management
- Shows typing indicators & loading states

### 3. **CSS Styling** (`chat-widget.css`)
- Modern gradient design
- Responsive mobile layout
- Smooth animations
- Professional UI/UX

## API Endpoints Used by Frontend

### 1. **Initialize Chat Session**
```
POST /wp-json/mm-ai-chat/v1/chat/initiate
Headers: X-WP-Nonce: <nonce>
```
**Called when:** User opens chat for first time  
**Returns:** `conversation_id`, `session_id`  
**Stored in:** Browser LocalStorage

### 2. **Send Message**
```
POST /wp-json/mm-ai-chat/v1/chat/message
Headers: X-WP-Nonce: <nonce>
Body: {
  "conversation_id": "conv_abc123",
  "message": "User message here"
}
```
**Called when:** User sends message  
**Returns:** AI response from OpenAI  
**Display:** Shown in chat window with typing indicator

### 3. **Load Message History**
```
GET /wp-json/mm-ai-chat/v1/chat/messages/{conversation_id}
Headers: X-WP-Nonce: <nonce>
```
**Called when:** User reopens chat window (if session exists)  
**Returns:** All previous messages in conversation  
**Restores:** Full conversation history

### 4. **Escalate to Offline**
```
POST /wp-json/mm-ai-chat/v1/chat/escalate/offline
Headers: X-WP-Nonce: <nonce>
Body: {
  "conversation_id": "conv_abc123",
  "question": "Question text",
  "email": "user@example.com"
}
```
**Called when:** User clicks "Ask Offline"  
**Purpose:** Submit question for email response

### 5. **Escalate to Agent**
```
POST /wp-json/mm-ai-chat/v1/chat/escalate/agent
Headers: X-WP-Nonce: <nonce>
Body: {
  "conversation_id": "conv_abc123",
  "reason": "User requested live agent"
}
```
**Called when:** User clicks "Chat with Agent"  
**Purpose:** Connect to live support agent

## JavaScript Functions

### Main Functions

| Function | Purpose | Calls API |
|----------|---------|-----------|
| `init()` | Initialize widget | No |
| `toggleWidget()` | Show/hide chat | No |
| `openWidget()` | Display chat window | No |
| `initializeChat()` | Start new session | ✅ POST `/chat/initiate` |
| `loadMessages()` | Restore conversation | ✅ GET `/chat/messages/{id}` |
| `sendMessage()` | Send user message | ✅ POST `/chat/message` |
| `escalateOffline()` | Submit offline question | ✅ POST `/chat/escalate/offline` |
| `escalateToAgent()` | Request live agent | ✅ POST `/chat/escalate/agent` |
| `pollForAgent()` | Check for agent reply | ✅ GET `/chat/messages/{id}` |

### Helper Functions

| Function | Purpose |
|----------|---------|
| `displayMessage()` | Show message in chat |
| `displaySystemMessage()` | Show system notifications |
| `displayTypingIndicator()` | Show "AI is typing..." |
| `scrollToBottom()` | Auto-scroll to latest message |
| `formatTime()` | Format timestamp |
| `sanitizeHtml()` | Prevent XSS attacks |

## Session Management

### LocalStorage Storage
```javascript
localStorage.mm_ai_chat_session = {
  "conversationId": "conv_abc123xyz",
  "sessionId": 1,
  "mode": "ai"  // or "agent"
}
```

**Persists:** Until user clears browser cache  
**Used for:** Restoring conversation on page reload  
**Expires:** When conversation is closed or cleared

## Features

### ✅ Implemented Features

1. **AI Chat** - Real-time conversation with OpenAI
2. **Message History** - Persistent conversation storage
3. **Session Recovery** - Auto-restore on page reload
4. **Typing Indicators** - Shows when AI is responding
5. **Offline Questions** - Submit for email response
6. **Live Agent Escalation** - Connect to support team
7. **KB Integration** - Show relevant knowledge base items
8. **Responsive Design** - Works on mobile/tablet/desktop
9. **Nonce Security** - CSRF protection
10. **Error Handling** - Graceful error messages

### 🎨 UI/UX Features

- **Smooth Animations** - Slide, fade, and bounce effects
- **Modern Gradient** - Purple gradient styling
- **Responsive Layout** - Mobile-first design
- **Loading States** - Clear visual feedback
- **Accessibility** - Proper color contrast
- **Professional Design** - Modern chat interface

## Configuration

### Enable/Disable Widget

Go to **AI Chat > Settings** in WordPress admin:

1. **Enable Chat Widget** - Toggle on/off
2. **Widget Position** - Choose corner position
3. **Model Selection** - Pick AI model (GPT-4o, etc.)
4. **Temperature** - Adjust response creativity
5. **Escalation** - Enable/disable agent escalation
6. **Offline Questions** - Enable/disable offline mode

### Environment Variables

```php
// In chat-widget.js, available via mmAiChatData:
mmAiChatData.restUrl          // API base URL
mmAiChatData.nonce            // CSRF nonce token
mmAiChatData.userId           // Current user ID
mmAiChatData.escalationEnabled // Agent feature enabled
mmAiChatData.offlineEnabled    // Offline questions enabled
```

## Security

### Nonce Verification
All API calls include nonce header:
```javascript
headers: {
  'X-WP-Nonce': mmAiChatData.nonce
}
```

### XSS Prevention
HTML content is sanitized:
```javascript
sanitizeHtml(text)  // Escapes HTML entities
```

### CSRF Protection
WordPress checks nonce on all POST requests automatically.

## Troubleshooting

### Chat Widget Not Appearing
- Check: **Settings > Enable Chat Widget** is toggled ON
- Check: OpenAI API key is configured
- Check: Browser console for JavaScript errors

### Messages Not Sending
- Check: Network tab for failed API calls
- Check: Nonce is valid (page might be too old)
- Check: OpenAI API key is valid

### LocalStorage Not Working
- Check: Browser allows localStorage
- Check: Private/Incognito mode (doesn't persist)
- Check: Browser storage quota

### Agent Not Connecting
- Check: Agent is logged in and available
- Check: Escalation is enabled in settings
- Check: Agent dashboard is active

## Development Notes

### Adding Custom Features

To add custom functionality, modify `chat-widget.js`:

```javascript
// Add new button in widget
$(document).on('click', '.custom-button', function() {
    // Your code here
    self.sendMessage();  // Example: send message
});

// Add new API endpoint call
$.ajax({
    url: mmAiChatData.restUrl + '/your-custom-endpoint',
    type: 'POST',
    headers: { 'X-WP-Nonce': mmAiChatData.nonce },
    // ...
});
```

### Styling Customization

To customize appearance, modify `chat-widget.css`:

```css
/* Change gradient color */
.mm-ai-chat-toggle-btn {
    background: linear-gradient(135deg, #YOUR_COLOR1 0%, #YOUR_COLOR2 100%);
}

/* Change widget width/height */
.mm-ai-chat-widget {
    width: 450px;
    height: 600px;
}
```

## Performance Optimization

1. **Lazy Loading** - Widget scripts load only on pages with visitors
2. **LocalStorage Cache** - Conversation history cached locally
3. **Polling Optimization** - Agent polling every 5 seconds with max attempts
4. **Message Pagination** - Limit initial message load
5. **Compression** - CSS/JS files minifiable

## Browser Compatibility

- ✅ Chrome/Edge (Chromium) 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ iOS Safari 14+
- ✅ Android Chrome 90+

## Testing the Chat Bubble

### Manual Testing Steps

1. **Visit website frontend** (not admin)
2. **See chat bubble** (💬) in bottom-right corner
3. **Click bubble** → Chat window opens
4. **Type message** → "How does this work?"
5. **Click Send** → AI responds within 5 seconds
6. **Reload page** → Conversation history appears
7. **Click Escalate** → See offline/agent options

### API Testing with cURL

```bash
# Initialize chat
curl -X POST http://pet.test/wp-json/mm-ai-chat/v1/chat/initiate \
  -H "X-WP-Nonce: YOUR_NONCE"

# Send message (replace CONV_ID)
curl -X POST http://pet.test/wp-json/mm-ai-chat/v1/chat/message \
  -H "X-WP-Nonce: YOUR_NONCE" \
  -d '{"conversation_id":"conv_abc123","message":"Hello"}'
```

## Summary

The frontend chat bubble is a **complete, production-ready** implementation that:

✅ Uses REST API endpoints for all communication  
✅ Handles session management automatically  
✅ Shows real-time AI responses  
✅ Supports escalation to agents  
✅ Provides offline question submission  
✅ Includes modern UI with smooth animations  
✅ Works on all devices and browsers  
✅ Includes security (nonce, XSS prevention)  
✅ Is fully configurable in WordPress admin  

**Result:** Visitors can chat with your AI directly from any page on your website! 🚀

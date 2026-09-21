# MM AI Chat Plugin - README

## Overview

**MM AI Chat** is a comprehensive WordPress plugin that provides an intelligent chat widget with AI-powered responses and human agent takeover capabilities. It integrates with OpenAI's API to provide smart, context-aware customer support directly on your WordPress site.

## Features

### Core Features
- ✅ **AI Chat Widget** - Beautiful, responsive chat interface embedded on your site
- ✅ **OpenAI Integration** - GPT-4, GPT-4 Turbo, or GPT-3.5 Turbo models
- ✅ **Knowledge Base RAG System** - Context-aware responses based on your custom FAQ/documentation
- ✅ **Live Agent Escalation** - Seamlessly transfer conversations to human agents
- ✅ **Offline Question Submission** - Capture user inquiries when no agents are available
- ✅ **Agent Dashboard** - Real-time chat management for support staff
- ✅ **Admin Settings** - Easy configuration of OpenAI credentials and behavior
- ✅ **REST API** - Full API for chat operations, escalation, and management
- ✅ **Session Management** - Persistent conversations with user tracking
- ✅ **Message History** - Complete conversation logs for audit and training

### Technical Features
- 🔒 **Secure API Key Storage** - Encrypted OpenAI credentials
- 📊 **Session Analytics** - Track chat statistics and metrics
- 🔄 **Retry Logic** - Automatic retry on rate limits with exponential backoff
- 🧠 **Smart Context Retrieval** - Keyword-based KB article matching
- 👥 **Multi-User Support** - Independent sessions per user
- 📱 **Responsive Design** - Works perfectly on mobile and desktop
- ⚡ **Performance Optimized** - Minimal database queries and efficient caching

## Installation

### Prerequisites
- WordPress 6.0 or higher
- PHP 7.4 or higher
- MySQL 5.7 or higher
- OpenAI API account with available credits

### Step 1: Upload Plugin

```bash
# Option A: Via FTP
Upload the mm-ai-chat folder to /wp-content/plugins/

# Option B: Via WordPress Admin
Dashboard → Plugins → Add New → Upload Plugin → Select mm-ai-chat.zip
```

### Step 2: Activate Plugin

Dashboard → Plugins → MM AI Chat → Activate

### Step 3: Configure OpenAI

1. Navigate to **Dashboard → AI Chat → Settings**
2. Enter your OpenAI API key (obtain from https://platform.openai.com/api-keys)
3. Select your preferred model (GPT-4o recommended)
4. Adjust temperature setting (0.7 is good default)
5. Click **Test Connection** to verify credentials
6. Save Settings

### Step 4: Set Up Knowledge Base

1. Go to **Dashboard → AI Chat → Knowledge Base**
2. Click **Add New**
3. Create FAQ/documentation items:
   - **Category**: e.g., "Billing", "Technical", "Account"
   - **Question**: The user question
   - **Answer**: The AI response
   - **Keywords**: Comma-separated terms (for matching)
   - **Priority**: Lower = higher priority (1-100)
4. Save and repeat for all items

### Step 5: Configure Settings

Return to **Settings** and:
- ☑️ Enable the chat widget
- Choose widget position (bottom-right, bottom-left, top-right, top-left)
- Enable/disable escalation options:
  - Live agent escalation
  - Offline question submission
- Customize system prompt

## Usage

### For Visitors/Users

1. **Chat Widget** - Look for the purple chat button in the corner
2. **Click to Open** - Click the button to expand the chat
3. **Type Message** - Enter your question
4. **Send** - Press Send or Enter
5. **Escalate** - If AI can't help, use escalation options:
   - **Chat with Agent** - Connect to live support
   - **Ask Offline** - Submit question to be answered later

### For Agents/Support Staff

1. **Login to WordPress Admin**
2. **Go to Dashboard → AI Chat**
3. **Pending Chats Tab** - Accept waiting conversations
4. **Active Chats Tab** - Manage active agent conversations
5. **Offline Questions Tab** - Answer submitted questions
6. **Send Reply** - Type and send messages to users

### For Administrators

1. **Settings Page** - Configure plugin behavior and credentials
2. **Knowledge Base** - Manage FAQ items that the AI uses
3. **Dashboard** - View statistics and chat activity
4. **API Documentation** - Access REST API endpoint details

## REST API Endpoints

### Base URL
```
https://yoursite.com/wp-json/mm-ai-chat/v1
```

### Chat Endpoints

#### Initiate Chat
```
POST /chat/initiate
Headers: nonce
Body: { user_id, page_context }
Response: { conversation_id, session_id, mode, status, message }
```

#### Send Message
```
POST /chat/message
Body: { conversation_id, message, nonce }
Response: { ai_response, metadata, kb_sources }
```

#### Get Messages
```
GET /chat/messages/{conversation_id}
Response: { conversation_id, messages[] }
```

#### Escalate Offline
```
POST /chat/escalate/offline
Body: { conversation_id, question, nonce }
Response: { success, question_id, message }
```

#### Escalate to Agent
```
POST /chat/escalate/agent
Body: { conversation_id, reason, nonce }
Response: { status, message, agent_id, estimated_wait_time_seconds }
```

#### Close Chat
```
POST /chat/close
Body: { conversation_id, reason, nonce }
Response: { success, message }
```

### Admin Endpoints

#### Get Sessions
```
GET /admin/sessions?status=waiting_for_agent&limit=20
Response: { sessions[] }
```

#### Accept Session
```
POST /admin/sessions/{session_id}/accept
Response: { success, agent_id }
```

#### Get Offline Questions
```
GET /admin/offline-questions?status=pending
Response: { questions[] }
```

#### Answer Offline Question
```
POST /admin/offline-questions/{question_id}/answer
Body: { answer }
Response: { success, message }
```

#### Test API Key
```
POST /admin/test-api-key
Body: { api_key }
Response: { success, message }
```

### Knowledge Base Endpoints

#### Get KB Items
```
GET /admin/knowledge-base?search=term&category=billing&limit=20
Response: { total, items[] }
```

#### Create KB Item
```
POST /admin/knowledge-base
Body: { category, question, answer, keywords, priority, is_active }
Response: { success, id }
```

#### Update KB Item
```
PUT /admin/knowledge-base/{kb_id}
Body: { category, question, answer, keywords, priority, is_active }
Response: { success }
```

#### Delete KB Item
```
DELETE /admin/knowledge-base/{kb_id}
Response: { success }
```

## Database Schema

### Tables Created

#### wp_ai_chat_sessions
```sql
- id: Session ID
- conversation_id: Unique conversation identifier
- user_id: WordPress user ID
- agent_id: Assigned agent ID (NULL if unassigned)
- status: active, waiting_for_agent, agent_assigned, closed, archived
- mode: ai, agent, offline_submission
- initiated_at: Timestamp
- last_message_at: Timestamp
- closed_at: Timestamp
- metadata: JSON with page context
```

#### wp_ai_chat_messages
```sql
- id: Message ID
- session_id: Foreign key to sessions
- message_id: Unique message identifier
- sender_type: user, ai, agent
- sender_id: User ID of sender
- content: Message text
- openai_response_metadata: JSON with token counts
- created_at: Timestamp
```

#### wp_ai_knowledge_base
```sql
- id: KB item ID
- kb_id: Unique KB identifier
- category: Category name
- question: Question/title
- answer: Answer content (HTML)
- keywords: Searchable keywords
- priority: 1-100 (lower = higher priority)
- is_active: 0 or 1
- created_by: User ID
- created_at: Timestamp
```

#### wp_ai_offline_questions
```sql
- id: Question ID
- question_id: Unique identifier
- session_id: Related session (optional)
- user_id: Question author
- question: Question text
- answer: Agent response (NULL until answered)
- answered_by: Agent ID who answered
- status: pending, answered, resolved
- created_at: Timestamp
```

#### wp_ai_agent_assignments
```sql
- id: Assignment ID
- agent_id: Agent user ID
- session_id: Session ID
- assigned_at: Timestamp
- accepted_at: Timestamp
- closed_at: Timestamp
- duration_seconds: Chat duration
- is_active: 0 or 1
```

## Settings & Options

### Stored in wp_options

```php
mm_ai_chat_enabled                      // Plugin enabled (1/0)
mm_ai_chat_openai_api_key              // Encrypted API key
mm_ai_chat_openai_model                // Selected model
mm_ai_chat_openai_temperature          // Temperature setting (0-2)
mm_ai_chat_widget_position             // Widget position
mm_ai_chat_escalation_enabled          // Live agent escalation (1/0)
mm_ai_chat_offline_questions_enabled   // Offline submission (1/0)
mm_ai_chat_agent_notifications_enabled // Agent alerts (1/0)
mm_ai_chat_kb_system_prompt            // Custom AI instructions
```

## Hooks & Filters

### Actions

```php
// When a user requests an agent
do_action('mm_ai_chat_user_requested_agent', $session)

// When session is closed
do_action('mm_ai_chat_session_closed', $session, $reason)

// When offline question is submitted
do_action('mm_ai_chat_offline_question_submitted', $data)

// When offline question is answered
do_action('mm_ai_chat_offline_question_answered', $data)
```

### Filters

Future version will support filters for:
- Message processing
- KB retrieval
- Response formatting
- Agent assignment logic

## Troubleshooting

### Widget Not Showing
- Check that plugin is enabled in Settings
- Verify page is not in admin area (widget only shows on frontend)
- Check browser console for JavaScript errors

### API Key Error
- Verify API key format (should start with "sk-")
- Check OpenAI account has available credits
- Test connection from Settings page
- Ensure not using organization API keys

### Chat Not Responding
- Check that OpenAI API key is valid
- Verify Knowledge Base has at least one item
- Check server logs for errors
- Ensure server has outbound HTTPS access

### Messages Not Saving
- Verify database tables were created (check wp_ai_chat_sessions)
- Check WordPress database user has INSERT permissions
- Review error logs in wp-content/debug.log

### Agent Not Receiving Messages
- Ensure agent user has "mm_ai_chat_agent" capability
- Check that agent is assigned to correct session
- Verify agent is logged into WordPress

## Performance Tips

1. **Limit Knowledge Base Size**
   - Use relevant keywords to speed up retrieval
   - Archive old/unused FAQ items
   - Keep answers concise

2. **Optimize Conversation History**
   - System limits to last 10 messages for context
   - Older messages are still stored for audit

3. **API Rate Limits**
   - Plugin includes exponential backoff retry logic
   - Consider usage tier for your API key

4. **Database Maintenance**
   - Archive closed chats periodically
   - Delete offline questions after 30 days
   - Optimize wp_ai_chat_messages table regularly

## Security Considerations

- ✅ API keys encrypted at rest using WordPress AUTH_KEY
- ✅ All API endpoints require nonce verification
- ✅ Agent capabilities restricted to users with role
- ✅ SQL queries use prepared statements
- ✅ User input sanitized and escaped
- ✅ Rate limiting on API calls

**Important**: Never share your OpenAI API key or disable nonce verification.

## Support & Updates

For issues or feature requests:
1. Check this README
2. Review plugin settings
3. Check debug logs
4. Contact plugin author

## Changelog

### Version 1.0.0
- Initial release
- AI chat with OpenAI integration
- Knowledge Base with RAG system
- Live agent escalation
- Offline question submission
- Admin dashboard
- Complete REST API
- Session management
- Message history

## License

GPL v2 or later

## Author

247 Empowerment
https://247empowerment.local

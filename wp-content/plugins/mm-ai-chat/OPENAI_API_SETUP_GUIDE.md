# OpenAI API Setup Guide for MM AI Chat

## 🔐 Understanding OpenAI Credentials

### What You Actually Need

On OpenAI's dashboard, you'll see different types of credentials. Here's what you actually need:

#### ✅ **API Key** (THIS IS WHAT WE NEED)
- **Format**: Starts with `sk-` followed by characters
- **Example**: `sk-proj-abc123xyz...`
- **Purpose**: Authentication for your chat application
- **Location**: [API Keys Page](https://platform.openai.com/account/api-keys)
- **Status**: ⚠️ **REQUIRED** - Without this, the chat won't work

#### ⚠️ **Organization ID** (OPTIONAL)
- **Format**: Starts with `org-` followed by characters
- **Example**: `org-abc123xyz`
- **Purpose**: Used if you have multiple organizations
- **Status**: Optional - Most users don't need this

#### ❌ **Other IDs** (DO NOT USE FOR OUR PLUGIN)
- **Tracking ID**: Used for tracking requests, NOT for authentication
- **Secret Key**: If you see this, it's for a different service (not OpenAI)
- **Other credentials**: Ignore these - we only need the API Key

---

## 📝 Step-by-Step Setup

### Step 1: Create OpenAI Account
1. Visit **https://platform.openai.com/signup**
2. Sign up with email or existing account
3. Complete verification

### Step 2: Create API Key
1. Go to **https://platform.openai.com/account/api-keys**
2. Click **"Create new secret key"**
3. Name it (e.g., "WordPress Chat Plugin")
4. Copy the key (⚠️ **Only shown once!**)

### Step 3: Set Up Billing
1. Go to **https://platform.openai.com/account/billing/overview**
2. Add payment method
3. Set usage limits for safety
4. Check rates: GPT-4o costs less than older models

### Step 4: Enter Key in WordPress
1. Go to **AI Chat > Settings** in WordPress admin
2. Paste API Key into **"OpenAI API Key"** field
3. Select model: **GPT-5.5** (newest), GPT-4o, or GPT-4 Turbo
4. Click **"Test Connection"** button
5. Save settings

---

## 🎯 What Goes Where in Our Plugin

```
┌─────────────────────────────────────────┐
│ OpenAI Dashboard                        │
├─────────────────────────────────────────┤
│ API Key: sk-proj-xxxxx                  │  ← COPY THIS
│ Organization ID: org-xxxxx              │     (Optional)
│ Tracking ID: xxxxx                      │     (Don't use)
│ Secret Key: xxxxx (if shown)            │     (Don't use)
└─────────────────────────────────────────┘
         ↓ (Copy API Key)
┌─────────────────────────────────────────┐
│ WordPress > AI Chat > Settings          │
├─────────────────────────────────────────┤
│ OpenAI API Key: [sk-proj-xxxxx      ] ✓ │ ← PASTE HERE
│                                          │
│ Model:         [GPT-5.5              ] │ ← Choose model
│                [GPT-4o                  │
│                [GPT-4 Turbo             │
│                                          │
│ Temperature:   [0.7                  ] │ ← Leave default
│                                          │
│ [Test Connection]                       │ ← Click to verify
└─────────────────────────────────────────┘
```

---

## 🔍 Finding Your API Key

### In OpenAI Dashboard:

1. **Sign in** to https://platform.openai.com
2. **Click your avatar** (bottom left)
3. **Select "Account"**
4. **Click "API keys"** in sidebar
5. **See your keys** - Keys starting with `sk-` are API keys

### Viewing an Existing Key:

- Click **"View"** button on a key
- Copy the full key (it will show as `sk-...`)
- This is what you need

### Creating New Key:

1. Click **"Create new secret key"**
2. Set name (optional): "WordPress MM AI Chat"
3. Copy immediately (you won't see it again!)
4. Store in password manager

---

## ⚠️ Common Mistakes

### ❌ **Mistake 1: Using Tracking ID**
```
WRONG: Tracking ID: xxxx...
RIGHT: API Key: sk-proj-xxx...
```

### ❌ **Mistake 2: Using Organization ID**
```
WRONG: Organization ID: org-xxx...
RIGHT: API Key: sk-proj-xxx...
```
(Unless you explicitly need to restrict by organization)

### ❌ **Mistake 3: Incomplete Key**
```
WRONG: sk-proj-abc (partial)
RIGHT: sk-proj-abcdefghijklmnopqrstuvwxyz (full key)
```

### ❌ **Mistake 4: Using Expired Key**
- Once used, keys are fine
- But if explicitly revoked, they won't work
- Check if key status shows **"Active"**

---

## ✅ Verify It Works

### Test from WordPress:
1. Go to **AI Chat > Settings**
2. Paste your API Key
3. Click **"Test Connection"** button
4. Should show: ✅ "API key is valid and working"

### If Test Fails:

| Error | Solution |
|-------|----------|
| **Invalid API key** | Copy full key including `sk-proj-` prefix |
| **Billing not set up** | Add payment method on OpenAI |
| **Rate limit exceeded** | Wait a minute, try again |
| **Model not found** | Check model name matches OpenAI |

---

## 🔒 Security Best Practices

### ✅ **Do This:**
- ✅ Store key in password manager
- ✅ Create separate key for production
- ✅ Rotate keys periodically
- ✅ Set usage limits on OpenAI dashboard
- ✅ Use environment variables for sensitive keys

### ❌ **Don't Do This:**
- ❌ Share your API key publicly
- ❌ Commit key to Git/GitHub
- ❌ Use same key on multiple sites
- ❌ Keep keys in code files
- ❌ Screenshot key and email it

---

## 💰 Cost Considerations

### Current OpenAI Pricing (as of 2026):

| Model | Input Cost | Output Cost |
|-------|-----------|-----------|
| **GPT-5.5** | $0.10/1K tokens | $0.30/1K tokens |
| **GPT-4o** | $0.03/1K tokens | $0.06/1K tokens |
| **GPT-4 Turbo** | $0.01/1K tokens | $0.03/1K tokens |
| **GPT-3.5 Turbo** | $0.0005/1K tokens | $0.0015/1K tokens |

### Estimate Usage:
- Average message = 100-200 tokens
- 1000 messages = ~$3-$30 depending on model
- Set **usage limits** to avoid surprises

### How to Set Limits:
1. Go to **https://platform.openai.com/account/billing/limits**
2. Set "Hard limit" (e.g., $10/month)
3. API will stop working after limit reached

---

## 🎯 Model Selection Guide

### **GPT-5.5** (Latest - Recommended)
- **Best for**: Latest features, highest quality
- **Cost**: Higher (~$0.40/1K tokens)
- **Speed**: Fast
- **Use if**: You want the best responses

### **GPT-4o** (Current Default)
- **Best for**: Good balance of quality and cost
- **Cost**: Low (~$0.09/1K tokens)
- **Speed**: Very fast
- **Use if**: Budget is a concern

### **GPT-4 Turbo**
- **Best for**: Legacy compatibility
- **Cost**: Very low (~$0.04/1K tokens)
- **Speed**: Fast
- **Use if**: Older integrations required

### **GPT-3.5 Turbo**
- **Best for**: Simplest responses
- **Cost**: Minimal (~$0.002/1K tokens)
- **Speed**: Fastest
- **Use if**: Extreme budget constraints

---

## 🔧 Advanced Configuration

### Optional: Organization ID
If you have multiple organizations on OpenAI:

1. Find Organization ID on dashboard
2. Add to WordPress via settings (new field)
3. Requests will be billed to that organization

### Optional: Custom Model
If OpenAI releases new models:

1. Go to **AI Chat > Settings**
2. Edit field manually (or we'll add it)
3. Use model ID from OpenAI (e.g., `gpt-5-preview`)

---

## 📞 Troubleshooting

### API Key Test Fails

**Error: "Invalid API key"**
```
Solution:
1. Copy full key from OpenAI dashboard
2. Verify it starts with "sk-"
3. Check for extra spaces
4. Try creating new key
```

**Error: "Billing not set up"**
```
Solution:
1. Visit https://platform.openai.com/account/billing/overview
2. Add credit card
3. Verify card is accepted
4. Wait 5 minutes
5. Test connection again
```

**Error: "Rate limit exceeded"**
```
Solution:
1. Wait 60 seconds
2. Check usage limits set
3. Reduce message frequency
4. Upgrade API plan
```

### Chat Not Working After Setup

**Symptom: Chat window opens but AI doesn't respond**
```
Debug steps:
1. Check browser console (F12)
2. Check "Test Connection" passes
3. Verify API key in settings still correct
4. Check plugin is enabled
5. Try different model
```

---

## 📋 Setup Checklist

- [ ] Created OpenAI account at platform.openai.com
- [ ] Generated API key (starts with `sk-`)
- [ ] Added payment method for billing
- [ ] Set usage limits for safety
- [ ] Copied full API key (with `sk-proj-` prefix)
- [ ] Logged into WordPress admin
- [ ] Went to AI Chat > Settings
- [ ] Pasted API key in correct field
- [ ] Selected model (GPT-5.5 recommended)
- [ ] Clicked "Test Connection" - ✅ Success
- [ ] Saved settings
- [ ] Tested chat bubble on frontend
- [ ] Chat responds with AI messages

---

## ✅ Success Indicators

When setup correctly:

✅ Settings page shows "Connected" status  
✅ Test Connection button returns success  
✅ Chat bubble appears on website frontend  
✅ Messages send and receive responses  
✅ Dashboard shows active sessions  
✅ OpenAI API is being called successfully  

---

## 🚀 Next Steps

1. **Set up Knowledge Base** - Go to **AI Chat > Knowledge Base** to add Q&A
2. **Configure Escalation** - Enable agents/offline mode in Settings
3. **Customize System Prompt** - Tell AI how to behave
4. **Monitor Usage** - Check OpenAI dashboard for costs
5. **Test Features** - Try escalation, offline questions, etc.

---

## 📚 Resources

- **OpenAI API Docs**: https://platform.openai.com/docs/
- **Model Details**: https://platform.openai.com/docs/models
- **Pricing**: https://openai.com/pricing
- **Status**: https://status.openai.com
- **Support**: https://help.openai.com

---

## ❓ FAQ

**Q: What if I lose my API key?**  
A: Create a new one in OpenAI dashboard. Old key becomes inactive.

**Q: Can I use free credits?**  
A: Trial credits work! Check your OpenAI account billing page.

**Q: Is my key secure?**  
A: We encrypt it and store securely. Never shown in plain text after save.

**Q: Can I change model after setup?**  
A: Yes! Go to Settings > Model and select different one anytime.

**Q: What's the difference between sk- and org- prefixes?**  
A: `sk-` = API key (use this), `org-` = Organization ID (optional)

**Q: Can I use same key on multiple websites?**  
A: Technically yes, but not recommended. Create separate keys per site.

---

**Need help?** Check the API Documentation in **AI Chat > API Documentation** menu!

# Migration: mm-ai-chat Initial Schema

## Description
Creates the initial database schema for the MM AI Chat plugin including all necessary tables for sessions, messages, knowledge base, offline questions, and agent assignments.

## Tables Created

1. `wp_ai_chat_sessions` - Main conversation sessions
2. `wp_ai_chat_messages` - All messages (user, AI, agent)
3. `wp_ai_knowledge_base` - FAQ/documentation for RAG system
4. `wp_ai_offline_questions` - Questions submitted when no agents available
5. `wp_ai_agent_assignments` - Agent workload tracking

## Migration Notes

- All tables use InnoDB storage engine for transaction support
- UTF-8 collation for international character support
- Indexes on frequently queried columns (user_id, status, created_at)
- Foreign key constraints where applicable
- JSON columns for flexible metadata storage

## Rollback

If needed, tables are removed on plugin deactivation via custom cleanup code.

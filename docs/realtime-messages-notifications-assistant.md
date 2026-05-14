# Realtime Messages, Notifications, Assistant (Local-ready)

## What was fake before
- `MessagesController` returned preview arrays only.
- `NotificationsController` returned empty static array.
- `assistant.tsx` generated frontend-only placeholder answers.
- Realtime channels existed only for generic branch/user events.

## What is implemented now
- Real branch-scoped messaging:
  - Tables: `conversations`, `conversation_participants`, `messages`
  - Actions: create conversation, send message, mark read
  - Realtime event: `MessageSent` on private conversation channel
- Real notifications:
  - DB notifications table
  - Listing + unread count + mark one read + mark all read + delete
  - Operational notifications dispatched from real order/kitchen/invoice/inventory/subscription flows
  - Broadcast notifications to authenticated user private channel
- Real assistant (read-only):
  - Tables: `assistant_conversations`, `assistant_messages`
  - Backend provider abstraction:
    - `AssistantProviderInterface`
    - `DeterministicAssistantProvider` (local-safe default)
  - Stores user/assistant messages in DB
  - Realtime event for assistant replies
  - No ERP mutation from assistant routes
- Reverb channel authorization tightened for:
  - branch
  - user
  - conversation participant
  - assistant conversation owner only

## Local run steps
1. Install deps:
   - `composer install`
   - `npm ci`
2. Configure env:
   - copy `.env.example` to `.env`
3. Migrate:
   - `php artisan migrate`
4. Start full local stack:
   - `composer run dev`

Build / verification:
- `composer run build`
- `composer run verify`

## Tests
Run:
- `php artisan test`

Added tests:
- `MessagesWorkflowTest`
- `NotificationsWorkflowTest`
- `AssistantWorkflowTest`
- `RealtimeChannelAuthorizationTest`
- `InvoiceSequenceTest`

## Limitations
- Assistant provider is deterministic/local and intentionally read-only.
- Messaging UI currently focuses on core workflow (no rich attachments).
- Realtime assumes Reverb running locally with configured keys/host.

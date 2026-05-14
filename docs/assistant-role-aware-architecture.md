# Assistant Role-Aware Architecture

## Goals

- Keep the assistant read-only for product data.
- Scope all context to the authenticated user and branch.
- Use the real role model from the codebase: `admin`, `manager`, `waiter`, `cashier`, `kitchen`.
- Support Arabic and English without exposing hidden prompts, raw context, or unrestricted database data.

## Final Architecture

### HTTP layer

- `app/Http/Controllers/AssistantController.php`
  - Validates requests.
  - Verifies conversation ownership and branch scope.
  - Delegates message handling to actions.
  - Returns user-friendly errors only.

### Action layer

- `app/Modules/Assistant/Actions/CreateAssistantConversation.php`
  - Creates a branch-scoped assistant conversation for the authenticated user.
- `app/Modules/Assistant/Actions/SendAssistantMessage.php`
  - Saves the user message with `user_id` attribution.
  - Deduplicates by optional `client_message_id`.
  - Calls `AssistantRuntime`.
  - Saves the assistant reply with metadata.
  - Dispatches `AssistantReplyCreated` when realtime is enabled.

### Runtime / orchestration

- `app/Modules/Assistant/Support/AssistantRuntime.php`
  - Detects intent.
  - Runs prompt-injection checks.
  - Builds safe role-aware context.
  - Selects provider output or deterministic fallback.
  - Enforces response policy.
  - Emits audit logs.

### Guardrails and context

- `AssistantIntentDetector`
  - Detects real product intents in English and Arabic.
  - Covers dashboard, orders, tables, kitchen, invoices, reports, inventory, menu, users, branch settings, messages, notifications, and help.
- `AssistantContextBuilder`
  - Builds only branch-scoped, role-allowed summaries.
  - Uses aggregated metrics and small operational summaries.
  - Adds denied-context reasons when the user asks for a module they cannot access.
- `AssistantPromptGuard`
  - Blocks attempts to reveal the system prompt, dump hidden context, impersonate higher roles, or bypass permissions.
- `AssistantResponsePolicy`
  - Replaces unsafe-looking provider output with safe fallback wording.
- `AssistantFallbackResponder`
  - Provides useful summaries when Ollama is unavailable or fails.
- `AssistantAuditLogger`
  - Logs user, role, branch, conversation, intent, provider, denied-context flag, latency, and outcome.

### Provider behavior

- `OllamaAssistantProvider`
  - Receives only safe serialized context and recent conversation history.
- `DeterministicAssistantProvider`
  - Returns an empty provider response so runtime can produce a user-friendly safe fallback.
- `OllamaClient`
  - Uses configurable base URL, model, and timeout.
  - Does not expose raw prompt/context in user-facing errors.

## Real User Types Discovered

The repository defines roles in `app/Enums/UserRole.php` and seeds them via `database/seeders/RoleSeeder.php`.

- `admin`
- `manager`
- `waiter`
- `cashier`
- `kitchen`

No additional role or permission records are defined in the repository today. Access control is enforced primarily by route middleware and branch scoping.

## Role Capability Matrix

| Role | Accessible modules from codebase | Restricted modules | Sensitive data assistant must deny |
| --- | --- | --- | --- |
| `admin` | dashboard, tables, orders, kitchen, invoices, menu, inventory, reports, messages, notifications, assistant, users, branch settings, plans, subscription actions | none inside current branch | cross-branch data only |
| `manager` | dashboard, tables, orders, kitchen, invoices, menu, inventory, reports, messages, notifications, assistant, branch settings | users, plans, admin-only subscription actions | cross-branch data, admin-only plan management |
| `waiter` | dashboard, tables, orders, messages, notifications, assistant | kitchen, invoices, menu, inventory, reports, users, branch settings, plans | cross-branch data, other staff orders, financial analytics, staff management |
| `cashier` | dashboard, tables, orders index/show, invoices, messages, notifications, assistant | kitchen, menu, inventory, reports, users, branch settings, plans | cross-branch data, staff management, report-level analytics |
| `kitchen` | dashboard, kitchen, messages, notifications, assistant | tables management, orders module, invoices, menu, inventory, reports, users, branch settings, plans | cross-branch data, financial data, staff management |

## Assistant Behavior By Role

### Admin

- Can request branch-wide operational and reporting summaries for the current branch.
- Can request staff counts, branch settings state, menu/inventory summaries, and high-level financial metrics already supported by the product.
- Cannot access data from other branches.

### Manager

- Can request branch operations, report summaries, kitchen queue, stock alerts, and branch settings guidance.
- Cannot access admin-only plan/staff-management surfaces.

### Waiter

- Can request own order summaries, visible table state, and dashboard-visible branch KPIs.
- Revenue analytics and staff data are denied with an explicit explanation.

### Cashier

- Can request invoice status, ready-to-bill flow guidance, cashier-visible orders, and dashboard-visible KPIs.
- Report-level analytics remain denied.

### Kitchen

- Can request kitchen queue context and dashboard-visible operational summaries.
- Billing, reports, inventory, and staff data remain denied.

## Data Access Rules

- The assistant never receives unrestricted database dumps.
- Context is always scoped to `conversation.branch_id` and authenticated `user_id`.
- Only recent messages from the same assistant conversation are sent to the provider.
- Context is aggregated wherever possible:
  - dashboard counts
  - status counts
  - invoice/revenue summaries
  - low-stock item summaries
  - staff counts by role
- Denied requests return explicit limitation text instead of hidden data.

## Denied Data Rules

- Cross-branch data is always blocked.
- Staff-management context is blocked unless the user can access the real users module.
- Report-level revenue analytics are blocked unless the user can access the real reports module.
- Branch configuration and plan context are blocked unless the user can access real branch settings.
- Prompt-injection attempts are blocked before provider execution.

## Rate Limits

- Named limiter: `assistant`
- Config key: `ASSISTANT_RATE_LIMIT`
- Applied to:
  - `assistant.conversations.store`
  - `assistant.messages.store`
  - `assistant.panel.messages.store`

## Prompt Injection Protections

- English and Arabic pattern checks for:
  - revealing system/developer prompts
  - dumping hidden context or raw JSON
  - bypassing permissions
  - acting as another role
  - broad sensitive data extraction
- Blocked prompts return a safe refusal and are audited.

## Realtime Behavior

- Assistant replies broadcast on `assistant.{conversationId}` using `AssistantReplyCreated`.
- Channel auth is restricted to the conversation owner only.
- Full assistant page reloads the conversation on broadcast.
- Panel requests use `client_message_id` to reduce duplicate message creation.

## Provider Fallback Behavior

- If Ollama is disabled, unavailable, empty, or throws, runtime returns:
  - English: “The AI engine is temporarily unavailable, but I can still summarize what is safely available.”
  - Arabic equivalent with the same operational meaning.
- The fallback uses safe context only.
- Internal errors are not shown to end users.

## Known Limitations

- The codebase currently has role middleware but no repository-defined fine-grained permission catalog beyond roles.
- The assistant remains read-only for product data and does not execute business mutations.
- Arabic fallback text is strong, but provider quality still depends on the configured Ollama model.
- The current application is single-branch per user in practice, so assistant scoping follows that model.

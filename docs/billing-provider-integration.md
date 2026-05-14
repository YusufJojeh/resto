# Stripe billing integration (RestoCafe)

This application uses a **custom, branch-scoped** billing layer—not Cashier. Operational access is decided only by **`SubscriptionAccessEvaluator`**; Stripe only mutates `branches` lifecycle fields and **`plan_id`** so entitlements stay authoritative in PHP (`Plan` rows + feature/limit enforcement).

## Configuration

| Env / config | Purpose |
|--------------|---------|
| `BILLING_ENABLED` | Must be `true` for checkout + webhook processing paths. |
| `BILLING_PROVIDER` | Currently `stripe`. |
| `BILLING_CURRENCY` | Lowercased currency for display; Stripe prices carry their own currency. |
| `STRIPE_SECRET_KEY` | Required for checkout + cancel-at-period-end API calls. |
| `STRIPE_WEBHOOK_SECRET` | Signing secret from the Stripe webhook endpoint. |
| `STRIPE_CHECKOUT_SUCCESS_URL` / `STRIPE_CHECKOUT_CANCEL_URL` | Passed to Checkout; success URL should include `{CHECKOUT_SESSION_ID}` so operators can deep-link if needed. |

`config/billing.php` centralizes these values. Missing keys do not crash the app; checkout and webhooks return graceful errors when not configured.

## Architecture

- **`App\Support\Billing\Contracts\BillingProviderContract`** — Checkout session creation (Stripe implementation in `StripeBillingProvider`).
- **`App\Support\Billing\Contracts\StripeSubscriptionGatewayContract`** — Subscription retrieve, cancel-at-period-end, **`createBillingPortalSession`** (same `StripeBillingProvider` class).
- **`CreateCheckoutSession`** — Validates env readiness; delegates to provider.
- **`CreateBillingPortalSession`** — Validates `BillingConfiguration::billingPortalAvailable()`; delegates **`createBillingPortalSession`** with a safe `return_url` back into Branch Settings (`?tab=subscription`).
- **`StripeWebhookToPatchMapper`** — Maps Stripe events → **`ProviderSubscriptionPatch`** DTOs (no React/business rules in the mapper beyond field mapping).
- **`ApplyProviderSubscriptionPatch`** — Resolves `Branch`, merges updates, records **`BranchSubscriptionAuditRecorder`** with `source=stripe`.
- **`ProcessBillingWebhook`** — Verifies signature, **idempotent** insert into `billing_webhook_events`, applies patches in a transaction.

## Checkout

- **Route:** `POST /settings/branch/billing/checkout` (`branch.billing.checkout`), **admin only**, **outside** operational `branch.subscription` gate so purchasing can happen when access is blocked.
- **Input:** `plan_id` only (server resolves `plan_id` fresh; never trusts curated UI lists blindly).
- **Server predicates:** billing enabled + Stripe secret + success/cancel URLs (`BillingConfiguration::checkoutAvailable()`), plan **active**, **non-empty** `provider_price_id`, user is admin (`StartBranchBillingCheckoutRequest` plus defensive checks in **`BranchBillingController`** before session creation).
- **Metadata** on the session & subscription: `branch_id`, `plan_id`, `user_id`, `app_environment`.

## Customer portal

- **Route:** `POST /settings/branch/billing/portal` (`branch.billing.portal`), **admin only**, same placement as Checkout (outside the operational subscription gate).
- **Predicates:** `BillingConfiguration::billingPortalAvailable()`, branch has **`provider_customer_id`**, user is admin.
- Opens Stripe’s hosted **Customer Portal** (payment methods + invoice history + whatever capabilities you enable inside Stripe Dashboard for that portal configuration). **`return_url`** points at **`/settings/branch?tab=subscription`** so operators drop back onto the Subscription tab locally.
- **Source of truth** for subscription state stays **webhooks** — portal-driven changes rely on Stripe emitting the mapped events already handled by **`ProcessBillingWebhook`**.
- Managers / staff never see the launcher (UI guarded by **`can_manage_subscription`**, routing guarded by **`role:admin`**).

## Webhook

- **Route:** `POST /billing/stripe/webhook` (`billing.stripe.webhook`), **CSRF-exempt** in `bootstrap/app.php`.
- **Verification:** `Stripe\Webhook::constructEvent` using `STRIPE_WEBHOOK_SECRET`.
- **Idempotency:** Unique index on (`provider`, `provider_event_id`). Retries do not re-apply or duplicate audit rows for the same Stripe event.

### Event mapping (minimum)

| Stripe event | Local effect (summary) |
|--------------|-------------------------|
| `checkout.session.completed` | Retrieve subscription; set provider ids, dates, `subscription_status`, **`plan_id`** from price; trusted `branch_id` hint only for first link; subscription id wins over bad hints later. |
| `customer.subscription.created` / `updated` | Sync status, dates, `plan_id` from first subscription item price id. |
| `customer.subscription.deleted` | Map to `canceled` vs `expired` using period end vs `now`; **does not clear `plan_id`** (historical tier). |
| `invoice.payment_failed` | `past_due`; anchors from invoice/subscription for grace (`SubscriptionAccessEvaluator`). |
| `invoice.payment_succeeded` | `active`; refresh periods; clears trial via patch flag where mapped. |

Provider status normalization lives in **`SubscriptionStatusLookup`** (e.g. `active` + `cancel_at_period_end` → local **`canceled`** for evaluator rules).

### Security notes

- No secrets on the frontend; `/settings/branch` passes **`billing`** (operator flags **only**) plus **`billing_plans`** (subset of Stripe-purchasable rows with price ids visible to admins for debugging), **`plans_for_assignment`**, **`display_plans`**, **`current_plan`**, **`billing_configured`**, **`can_start_checkout`**, **`can_open_billing_portal`**. Assignment rows expose **`has_provider_price`** instead of embedding Stripe ids unnecessarily.
- **`BranchSettingsController`** strips `provider_*` from **`branch`** for managers; admins still view ids for linkage debugging.

## Audit

Webhook and manual edits both append `branch_subscription_changes` with **`source`**, optional **`provider`**, **`provider_event_id`**, and nullable **`actor_id`** for Stripe-driven rows.

## Manual fallback

Administrators retain **`branch.subscription.update`** and full plan CRUD (whitelisted past subscription middleware). Stripe is additive, not replacing manual controls.

## Local webhook testing

1. Set env variables and `BILLING_ENABLED=true`.
2. Install [Stripe CLI](https://stripe.com/docs/stripe-cli), run `stripe listen --forward-to http://localhost:8000/billing/stripe/webhook`.
3. Use `stripe trigger` or complete a Checkout session in test mode.
4. Confirm `billing_webhook_events.processed_at` and `branch_subscription_changes` rows.

## Production checklist

- [ ] Dedicated webhook endpoint secret per environment (`STRIPE_WEBHOOK_SECRET`).
- [ ] `STRIPE_CHECKOUT_*` URLs use HTTPS production hosts.
- [ ] Plans have **`provider_price_id`** populated for tiers that sell online.
- [ ] Stripe Dashboard **[Customer Portal](https://stripe.com/docs/customer-management)** configured for desired self-service features (payments, cancellations, invoices) before launching the Portal button broadly.
- [ ] Operators understand grace / trial behavior in `docs/subscription-access-slice.md`.

## Related

- **`docs/subscription-access-slice.md`** — Lifecycle matrix, grace, manual admin, entitlement vs subscription access.

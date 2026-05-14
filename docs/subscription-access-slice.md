# SaaS subscription access & plans slice

## Overview

This slice combines **billing/commercial access status** (`subscription_status`, dates, branch activity) with an internal **plans & entitlements layer** (`plans` table + `branch.plan_id`). **Stripe checkout and webhooks** are documented in [`billing-provider-integration.md`](billing-provider-integration.md).

---

## Responsibilities split

### 1. Subscription status (commercial gate)

- Controlled by `App\Support\Subscription\SubscriptionAccessEvaluator` (surfaced as `Branch::subscriptionAccessAssessment()`, `hasSubscriptionAccess()`, `subscriptionAccessReason()`, `subscriptionAccessExplanation()`).
- Inputs: `subscription_status`, `trial_ends_at`, `subscription_ends_at`, `current_period_ends_at`, `is_active`, plus `config('subscriptions.grace_days')`.
- `EnsureBranchSubscriptionAccess` (`branch.subscription` middleware) blocks operational routes when evaluation denies access — except **`branch.edit` / `branch.update` for managers and admins** (so subscription and Stripe tooling stay reachable during blocks), plus **admin-only** plan CRUD routes, **`branch.subscription.update`**, and **`branch.billing.checkout|cancel|portal`** exemptions (Stripe routes are also mounted **outside** the operational middleware group today—the whitelist keeps them unblockable if that wiring ever shifts).
- This answers: “Is this branch paying / allowed on the platform at all?”

### 2. Plan entitlements (tier gate)

- When `branch.plan_id` is **null**, the tenant is treated as **grandfathered**: every declared feature behaves as enabled and quantitative limits do not apply.
- When `branch.plan_id` points at a `plans` row, the backend resolves **features** (boolean flags) and **limits** (optional integers) purely from PHP models — React must not encode rules.
- This answers: “Inside an active subscription, what modules and capacities does this tier include?”

---

## Subscription lifecycle & access matrix

| `subscription_status` | Operational access | Notes |
|----------------------|--------------------|--------|
| `active` | Allowed | While `branches.is_active` is true. |
| `trialing` | Allowed while `trial_ends_at` is null or in the future | No trial end ⇒ clock-based trial does not auto-expire. |
| `past_due` | Allowed until billing anchor + grace | Anchor = `subscription_ends_at ?? current_period_ends_at`. Access while `now <= anchor + grace_days` (`config/subscriptions.php`, env `SUBSCRIPTION_GRACE_DAYS`). Missing anchor ⇒ denied. |
| `canceled` | Allowed while `now <= current_period_ends_at` (else `subscription_ends_at`) | Cancel-at-period-end semantics. Both null ⇒ denied. |
| `expired` | Denied | Terminal. |
| `suspended` | Denied | Administrative / risk hold. |
| `is_active = false` on branch | Denied | Overrides subscription status. |

**Machine-safe codes** (`subscriptionAccessReason()`): include `active`, `trial_active`, `trial_expired`, `past_due_grace`, `past_due_expired`, `canceled_until_period_end`, `canceled_expired`, `expired`, `suspended`, `inactive_branch`. Users without a branch get `no_branch` from middleware payloads.

**Human text** for UI: `subscriptionAccessExplanation()` (also exposed as shared `subscription.reason`).

**Stripe webhook mapping (implemented — see billing doc):** events are normalized into patches applied on the authoritative `branches.provider_subscription_id` row (with safe metadata hints for first linkage). Operators still tune edge cases manually when needed.

---

## Plan model

Table `plans`:

| Column | Purpose |
|--------|---------|
| `name`, `slug` | Human title + stable identifier (`slug` unique, syncs into legacy `branches.subscription_plan`). |
| `description` | Optional marketing / internal notes. |
| `price_amount`, `billing_interval` | Nullable placeholders (`month`, `year`) until billing integration ships. |
| `provider_price_id` | Stripe price id (**required only** for Stripe Checkout / purchasable catalog rows). Manual assignment ignores this column. |
| `is_active` | Controls manual assignability — inactive tiers cannot be newly assigned unless the branch **already** has that tier (historic / sunset handling). Stripe checkout rejects inactive rows entirely server-side. |
| `sort_order` | Admin ordering for pickers & listings. |
| `features` (JSON object) | `string → bool` entries from `PlanFeatureKey`. Missing keys behave as **false**. |
| `limits` (JSON object) | `PlanLimitKey → int`. Absent keys mean **no hard cap**. |

Branches retain nullable FK `plan_id` (see migrations) plus lifecycle columns:

| Column | Role |
|--------|------|
| `trial_ends_at` | Trial clock for `trialing`. |
| `subscription_ends_at` | Billing / commercial anchor; also fallback end for canceled; primary anchor for Past Due grace. |
| `current_period_ends_at` | Preferred end instant for canceled access; participates in Past Due anchor fallback ordering. |
| `provider_*` nullable strings (`provider_name`, `provider_customer_id`, `provider_subscription_id`) | Wired for Stripe Checkout + webhook correlation (`provider_price_id` on `plans`). |

Legacy `subscription_plan` string stays in sync when a plan id is applied.

---

## Plan catalog facets (manual vs Stripe)

Branches → **Branch settings · Subscription** receive three distinct Laravel-driven lists plus a **current-plan snapshot**:

| Payload | Audience | Backend rule |
|---------|----------|--------------|
| `plans_for_assignment` | admins | `Plan::assignableForBranch($branchPlanId)` — every **active** plan **plus** the branch’s existing plan row even if **`is_active` is false** (so sunsets stay selectable / visible for retention). Rows expose **`has_provider_price`** booleans rather than implying checkout eligibility. |
| `billing_plans` | admins | **`Plan::purchasable()`**: **active AND** **`provider_price_id` present**. Stripe checkout + validation use the same predicates **plus** `BillingConfiguration::checkoutAvailable()`. Lists are never authoritative on their own—the controller re-validates `plan_id` on POST. |
| `display_plans` | admins + managers | **`Plan::active()`** `{id,name,slug}` only — safe tier labels without Stripe internals. Used for read-only UX copy (“active catalog tiers”). |
| `current_plan` | admins + managers | Snapshot from `branch.plan` including **`is_active`** so archived tiers surface as **“Current inactive plan”** without offering them as *new* purchases. |

Operational access stays **`SubscriptionAccessEvaluator`**-only; these lists merely shape administration and checkout—not entitlements computed in JS.

---

## Feature entitlement format

**Storage:** JSON object of explicit booleans keyed by known constants in `App\Support\Subscription\PlanFeatureKey` (e.g. `orders`, `kitchen`, `reports`, `menu_management`, …).

**Checks (backend):**

- `Branch::canUseFeature(string $key): bool`
  - `plan_id === null` → `true` for all keys (grandfathering).
  - Otherwise only returns `true` when the plan row exists and `$plan->features[$key] === true`.

**Route enforcement example:** `EnsureBranchPlanFeature` (`plan.feature:{key}`) redirects to the dashboard with flash error if the feature flag is off. Kitchen routes require `kitchen`, `/reports` requires `reports`.

Frontend reads **computed summaries** via shared `subscription.entitlement_summary`; no enforcement logic lives in React.

---

## Limit format

**Storage:** JSON object keyed by `App\Support\Subscription\PlanLimitKey` (`max_users`, `max_tables`, `max_menu_items`, `max_daily_orders`, `max_branches`, …) with non-negative integers.

Helpers:

- `Branch::getPlanLimit(string $key): ?int`
- `Branch::hasPlanLimit(string $key): bool`
- `Branch::isAtOrOverPlanLimit(string $key, int $usageCount): bool`

Example enforcement points:

- Creating staff (`UserController@store`) respects `max_users` against **active** users per branch.
- Creating tables / menu items (`TableController@store`, `MenuItemController@store`) respect `max_tables` / `max_menu_items`.
- Creating orders (`OrderController@store`) counts today’s orders and applies `max_daily_orders`.

`max_branches` is **documentation-only today** (`PlanLimitKey::isEnforced()` is false) because the UX is branch-scoped tenants.

Branches without a plan bypass enforced limits (`getPlanLimit` → `null`).

---

## Audit logging (`branch_subscription_changes`)

Each manual subscription update logs branch id and actor user id, plus before/after values for subscription status, `trial_ends_at`, `subscription_ends_at`, `current_period_ends_at`, `plan_id`, and **access snapshot** (`old_access_allowed`, `new_access_allowed`, `old_access_reason`, `new_access_reason` using machine-safe codes). This preserves a trace of lifecycle edits separate from entitlement flags.

---

## Manual administration UI

### Branch Settings → Subscription tab

- Read-only overview for everyone with subscription metadata + entitlement summary chips + configured caps (**enforced limits** vs **informational-only** chips where applicable).
- Admins additionally edit billing/access lifecycle (`past_due`, `canceled`, etc.), `trial_ends_at`, `subscription_ends_at`, `current_period_ends_at`, and plan tier; UI shows backend **allowed/blocked**, **reason code**, and human explanation alongside dates — no checkout UI.

### Settings → Subscription plans (`/settings/plans`)

Admin-only CRUD screens (`plans/index`, `plans/form`). Plans cannot be deleted while still referenced by branches (`PlanSettingsController@destroy` blocks with flash error).

---

## Connecting future billing providers

1. **Persist provider IDs** on branches or a dedicated `billing_subscriptions` aggregate as needed (e.g. Stripe customer + subscription ids).
2. **Webhooks** translate provider events into updates of `subscription_status`, trial/subscription end dates, **and** attaching the correct local `plan_id` once payment succeeds.
3. **Map remote prices to local tiers** using `plans.provider_price_id` (and similar columns per vendor). Checkout resolves the purchased price → internal `Plan` → set `branch.plan_id` + refresh `subscription_plan` slug.
4. **Keep entitlements authoritative in PHP** — provider metadata should not duplicate feature matrices client-side.

---

## API / routes (high level)

| Area | Route names | Notes |
|------|-------------|-------|
| Branch subscription PATCH | `branch.subscription.update` | Admin only. |
| Plan CRUD | `plans.*` | Admin only, under `branch.subscription` stack. |
| Kitchen | `kitchen.*` | Adds `plan.feature:kitchen`. |
| Reports | `reports.index` | Adds `plan.feature:reports`. |

---

## Tests

```bash
php artisan test --filter=Plan
php artisan test tests/Feature/RestoCafe/PlanAndEntitlementsTest.php
php artisan test tests/Unit/Models/BranchPlanEntitlementsTest.php
php artisan test tests/Feature/RestoCafe/SubscriptionLifecycleRulesTest.php
php artisan test tests/Unit/Models/BranchSubscriptionTest.php
php artisan test tests/Feature/RestoCafe/SubscriptionAccessTest.php
php artisan test tests/Feature/RestoCafe/BillingStripeTest.php
```

---

## What this slice still does **not** do

- Entitlements are **not** computed in JavaScript; React only displays server summaries.
- No customer portal, proration engine, tax engine, or invoicing sync with Stripe (in-app **order invoices** remain separate).
- `max_branches` remains informational until multi-branch accounts ship.

---

## Next recommended slice

1. **Stripe Customer Portal / payment method management** (safe deep links or hosted portal).
2. **Operational usage dashboards** surfacing limit proximity (`max_users`, `max_tables`, `max_menu_items`, `max_daily_orders`).
3. **Multi-tenant branching** before enforcing `max_branches` mechanically.

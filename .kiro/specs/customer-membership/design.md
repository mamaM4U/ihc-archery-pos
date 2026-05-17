# Design Document: Customer Membership

## Overview

Sistem membership untuk IHC Archery yang memungkinkan penjualan paket latihan panahan berbasis kuota sesi. Terintegrasi dengan sistem transaksi dan customer portal yang sudah ada.

## Architecture

### Database Schema

#### Table: `membership_plans`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint unsigned PK | Auto-increment |
| name | varchar(255) | Nama plan (e.g., "4 Sesi/Bulan - Belum Punya Alat") |
| category | varchar(50) | registration, trial, monthly_no_equipment, monthly_with_equipment, family |
| price | bigint | Harga dalam rupiah |
| duration_days | int | Durasi membership dalam hari (30 untuk bulanan, 0 untuk registration) |
| session_quota | int | Jumlah sesi per periode (0 untuk registration) |
| description | text nullable | Deskripsi plan |
| equipment_provided | boolean | Apakah alat disediakan |
| family_members | int default 1 | Jumlah anggota keluarga (untuk paket family) |
| is_active | boolean default true | Status aktif/nonaktif |
| created_at | timestamp | |
| updated_at | timestamp | |

#### Table: `customer_memberships`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint unsigned PK | Auto-increment |
| customer_id | bigint unsigned FK | Referensi ke customers |
| membership_plan_id | bigint unsigned FK | Referensi ke membership_plans |
| transaction_id | bigint unsigned FK nullable | Referensi ke transactions |
| start_date | date | Tanggal mulai |
| end_date | date | Tanggal berakhir |
| session_quota | int | Total kuota sesi untuk periode ini |
| session_used | int default 0 | Jumlah sesi yang sudah digunakan |
| status | varchar(20) | active, expired, pending |
| created_at | timestamp | |
| updated_at | timestamp | |

#### Table: `session_usages`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint unsigned PK | Auto-increment |
| customer_membership_id | bigint unsigned FK | Referensi ke customer_memberships |
| customer_id | bigint unsigned FK | Referensi ke customers |
| checked_in_by | bigint unsigned FK nullable | User (admin/kasir) yang mencatat |
| checked_in_at | timestamp | Waktu check-in |
| notes | text nullable | Catatan tambahan |
| created_at | timestamp | |
| updated_at | timestamp | |

### Models

#### MembershipPlan

```php
// app/Models/MembershipPlan.php
class MembershipPlan extends Model
{
    protected $fillable = [
        'name', 'category', 'price', 'duration_days',
        'session_quota', 'description', 'equipment_provided',
        'family_members', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'duration_days' => 'integer',
            'session_quota' => 'integer',
            'equipment_provided' => 'boolean',
            'family_members' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function customerMemberships(): HasMany
    {
        return $this->hasMany(CustomerMembership::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
```

#### CustomerMembership

```php
// app/Models/CustomerMembership.php
class CustomerMembership extends Model
{
    protected $fillable = [
        'customer_id', 'membership_plan_id', 'transaction_id',
        'start_date', 'end_date', 'session_quota',
        'session_used', 'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'session_quota' => 'integer',
            'session_used' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function membershipPlan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function sessionUsages(): HasMany
    {
        return $this->hasMany(SessionUsage::class);
    }

    public function getRemainingSessionsAttribute(): int
    {
        return max(0, $this->session_quota - $this->session_used);
    }

    public function getRemainingDaysAttribute(): int
    {
        return max(0, now()->startOfDay()->diffInDays($this->end_date, false));
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        return $this->status === 'active' && $this->remaining_days <= 7;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeForCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }
}
```

#### SessionUsage

```php
// app/Models/SessionUsage.php
class SessionUsage extends Model
{
    protected $fillable = [
        'customer_membership_id', 'customer_id',
        'checked_in_by', 'checked_in_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
        ];
    }

    public function customerMembership(): BelongsTo
    {
        return $this->belongsTo(CustomerMembership::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }
}
```

### Services

#### MembershipService

```php
// app/Services/MembershipService.php
class MembershipService
{
    public function activateMembership(Customer $customer, MembershipPlan $plan, ?Transaction $transaction = null): CustomerMembership;
    public function extendMembership(CustomerMembership $membership, MembershipPlan $plan, ?Transaction $transaction = null): CustomerMembership;
    public function checkIn(CustomerMembership $membership, ?int $checkedInBy = null, ?string $notes = null): SessionUsage;
    public function getActiveMembership(Customer $customer): ?CustomerMembership;
    public function hasRegistration(Customer $customer): bool;
    public function canCheckIn(CustomerMembership $membership): bool;
    public function expireOverdueMemberships(): int;
}
```

Key logic:
- `activateMembership`: Creates a new CustomerMembership. If customer already has active membership of same plan, delegates to `extendMembership`.
- `extendMembership`: Sets new start_date = current end_date + 1 day, calculates new end_date.
- `checkIn`: Validates remaining quota > 0 and membership is active, then creates SessionUsage and increments session_used.
- `expireOverdueMemberships`: Scheduled command to mark expired memberships.

### Controllers

#### Admin Side

```
app/Http/Controllers/Apps/MembershipPlanController.php   — CRUD for plans
app/Http/Controllers/Apps/MembershipController.php       — Member list, stats, check-in
```

#### Customer Portal Side

```
app/Http/Controllers/Customer/MembershipController.php   — View membership, purchase, history
```

### Routes

#### Admin Routes (web, auth middleware)

```php
Route::resource('membership-plans', MembershipPlanController::class);
Route::get('memberships', [MembershipController::class, 'index'])->name('memberships.index');
Route::get('memberships/stats', [MembershipController::class, 'stats'])->name('memberships.stats');
Route::post('memberships/check-in', [MembershipController::class, 'checkIn'])->name('memberships.check-in');
Route::get('memberships/daily-log', [MembershipController::class, 'dailyLog'])->name('memberships.daily-log');
```

#### Customer Portal Routes (customer auth middleware)

```php
Route::get('membership', [Customer\MembershipController::class, 'index'])->name('customer.membership');
Route::get('membership/plans', [Customer\MembershipController::class, 'plans'])->name('customer.membership.plans');
Route::post('membership/purchase', [Customer\MembershipController::class, 'purchase'])->name('customer.membership.purchase');
Route::get('membership/history', [Customer\MembershipController::class, 'history'])->name('customer.membership.history');
```

### Frontend Pages (Inertia + React)

#### Admin Pages

```
resources/js/Pages/Dashboard/MembershipPlans/Index.jsx
resources/js/Pages/Dashboard/MembershipPlans/Create.jsx
resources/js/Pages/Dashboard/MembershipPlans/Edit.jsx
resources/js/Pages/Dashboard/Memberships/Index.jsx
resources/js/Pages/Dashboard/Memberships/CheckIn.jsx
resources/js/Pages/Dashboard/Memberships/DailyLog.jsx
```

#### Customer Portal Pages

```
resources/js/Pages/Customer/Membership/Index.jsx      — Current membership status + session usage
resources/js/Pages/Customer/Membership/Plans.jsx      — Available plans to purchase
resources/js/Pages/Customer/Membership/History.jsx    — Past memberships
```

### Payment Integration

Membership purchases use the existing `Transaction` + `PaymentGatewayManager` flow:
1. A membership plan can be added as a line item in a transaction alongside regular products (e.g., busur, anak panah)
2. The transaction total includes both product prices and membership price
3. Maximum one membership plan per transaction
4. Use existing payment gateway (Midtrans/Xendit) for online payments
5. On webhook confirmation (payment_status = 'paid'), trigger `MembershipService::activateMembership()` if the transaction contains a membership item
6. Product fulfillment and membership activation happen independently after payment confirmation

### Scheduled Commands

```php
// app/Console/Commands/ExpireMemberships.php
// Runs daily via scheduler to mark expired memberships
```

### Webhook Extension

The existing `PaymentWebhookController` will be extended to check if a paid transaction contains a membership item and trigger activation:

```php
// After updating transaction status to 'paid':
// 1. Process regular product items (existing flow)
// 2. Check for membership item and activate if present
if ($transaction->hasMembershipItem()) {
    app(MembershipService::class)->activateMembership(
        $transaction->customer,
        $transaction->membershipPlan(),
        $transaction
    );
}
```

Note: A transaction can contain both regular products and a membership plan. The webhook handles both — product fulfillment continues as normal, and membership activation is triggered additionally when a membership item is detected.

## Correctness Properties

### Property 1: Session Quota Invariant
For any active CustomerMembership, `session_used + remaining_sessions == session_quota` must always hold true.

### Property 2: Check-in Rejection When Quota Exhausted
A check-in attempt on a membership where `session_used >= session_quota` must always be rejected.

### Property 3: Membership Status Consistency
A membership with `end_date < today` must always have status "expired" (after the scheduled command runs).

### Property 4: Registration Prerequisite
A monthly membership cannot be activated for a customer who does not have a "registration" type membership record.

### Property 5: Extension Date Continuity
When extending an active membership, the new start_date must equal the current end_date + 1 day, ensuring no gaps or overlaps.

### Property 6: Session Usage Count Consistency
The count of SessionUsage records for a CustomerMembership must always equal the `session_used` value on that membership.

## File Structure Summary

```
app/
├── Console/Commands/ExpireMemberships.php
├── Http/Controllers/
│   ├── Apps/
│   │   ├── MembershipPlanController.php
│   │   └── MembershipController.php
│   └── Customer/
│       └── MembershipController.php
├── Models/
│   ├── MembershipPlan.php
│   ├── CustomerMembership.php
│   └── SessionUsage.php
└── Services/
    └── MembershipService.php

database/
├── factories/
│   ├── MembershipPlanFactory.php
│   └── CustomerMembershipFactory.php
└── migrations/
    ├── xxxx_create_membership_plans_table.php
    ├── xxxx_create_customer_memberships_table.php
    └── xxxx_create_session_usages_table.php

resources/js/Pages/
├── Dashboard/
│   ├── MembershipPlans/
│   │   ├── Index.jsx
│   │   ├── Create.jsx
│   │   └── Edit.jsx
│   └── Memberships/
│       ├── Index.jsx
│       ├── CheckIn.jsx
│       └── DailyLog.jsx
└── Customer/
    └── Membership/
        ├── Index.jsx
        ├── Plans.jsx
        └── History.jsx

tests/Feature/
├── MembershipPlanTest.php
├── MembershipServiceTest.php
├── MembershipCheckInTest.php
├── CustomerMembershipPortalTest.php
└── MembershipWebhookTest.php
```

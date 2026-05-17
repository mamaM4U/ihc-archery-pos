# Tasks: Customer Membership

## Task 1: Database Schema & Models

- [x] 1.1 Create migration for `membership_plans` table
- [x] 1.2 Create migration for `customer_memberships` table
- [x] 1.3 Create migration for `session_usages` table
- [x] 1.4 Create MembershipPlan model with factory
- [x] 1.5 Create CustomerMembership model with factory
- [x] 1.6 Create SessionUsage model
- [x] 1.7 Add `memberships()` relationship to Customer model
- [x] 1.8 Run migrations and verify schema

## Task 2: Membership Service

- [x] 2.1 Create MembershipService class with `activateMembership` method
- [x] 2.2 Implement `extendMembership` method for renewals
- [x] 2.3 Implement `checkIn` method with quota validation
- [x] 2.4 Implement `getActiveMembership` and `hasRegistration` helper methods
- [x] 2.5 Implement `canCheckIn` validation logic
- [x] 2.6 Implement `expireOverdueMemberships` method
- [x] 2.7 Write feature tests for MembershipService

## Task 3: Admin - Membership Plan CRUD

- [x] 3.1 Create MembershipPlanController with index, create, store, edit, update, destroy
- [x] 3.2 Register admin routes for membership plans
- [x] 3.3 Create MembershipPlans/Index.jsx page
- [x] 3.4 Create MembershipPlans/Create.jsx page
- [x] 3.5 Create MembershipPlans/Edit.jsx page
- [x] 3.6 Write feature tests for MembershipPlanController

## Task 4: Admin - Membership Management & Check-in

- [x] 4.1 Create MembershipController with index (member list with filters)
- [x] 4.2 Implement check-in endpoint and logic
- [x] 4.3 Implement daily log endpoint
- [x] 4.4 Implement stats endpoint (active members, revenue, expiring soon)
- [x] 4.5 Register admin routes for membership management
- [x] 4.6 Create Memberships/Index.jsx page (member list)
- [x] 4.7 Create Memberships/CheckIn.jsx page
- [x] 4.8 Create Memberships/DailyLog.jsx page
- [x] 4.9 Write feature tests for MembershipController

## Task 5: Membership Sales Integration

- [x] 5.1 Add membership plan as selectable line item in TransactionController (alongside regular products)
- [x] 5.2 Implement validation: max 1 membership plan per transaction
- [x] 5.3 Extend PaymentWebhookController to detect membership item and activate membership on payment confirmation
- [x] 5.4 Write feature tests for mixed transaction (membership + products) and webhook activation

## Task 6: Customer Portal - Membership Display

- [x] 6.1 Create Customer/MembershipController with index (current membership status)
- [x] 6.2 Implement plans listing endpoint
- [x] 6.3 Implement purchase endpoint with payment gateway integration
- [x] 6.4 Implement history endpoint
- [x] 6.5 Register customer portal routes
- [x] 6.6 Create Customer/Membership/Index.jsx (status, remaining sessions, usage history)
- [x] 6.7 Create Customer/Membership/Plans.jsx (available plans)
- [x] 6.8 Create Customer/Membership/History.jsx (past memberships)
- [-] 6.9 Update Customer/Dashboard to show membership summary widget
- [x] 6.10 Write feature tests for Customer MembershipController

## Task 7: Scheduled Commands & Expiration

- [x] 7.1 Create ExpireMemberships artisan command
- [x] 7.2 Register command in scheduler
- [x] 7.3 Write feature test for expiration command

## Task 8: Seeder & Initial Data

- [x] 8.1 Create MembershipPlanSeeder with IHC Archery plans (registration, trial, monthly tiers, family)
- [x] 8.2 Add seeder to DatabaseSeeder

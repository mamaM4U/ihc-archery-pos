# Requirements Document

## Introduction

Fitur Customer Membership untuk IHC Archery memungkinkan penjualan paket membership latihan panahan kepada pelanggan. Membership berbasis kuota sesi latihan per bulan dengan berbagai tier (belum punya alat, sudah punya alat, paket keluarga). Sistem mencatat sisa kuota sesi, dan informasi membership ditampilkan di customer portal saat customer login.

## Glossary

- **Membership_Plan**: Paket membership yang dijual, memiliki nama, kategori (registration/trial/monthly), harga, jumlah sesi per bulan, dan keterangan apakah alat disediakan.
- **Customer_Membership**: Record yang menghubungkan Customer dengan Membership_Plan, mencatat periode aktif (bulan berjalan), sisa kuota sesi, dan status.
- **Session_Usage**: Record penggunaan sesi latihan oleh customer, mencatat tanggal dan waktu penggunaan.
- **Membership_Service**: Komponen backend yang mengelola logika bisnis membership (pembelian, aktivasi, pengurangan kuota, perpanjangan).
- **Customer_Portal**: Antarmuka web yang diakses customer setelah login menggunakan no_telp dan password.
- **Transaction_System**: Sistem penjualan yang sudah ada, mencatat transaksi melalui model Transaction.
- **Admin_Dashboard**: Antarmuka admin untuk mengelola data toko termasuk membership plans dan member.
- **Plan_Category**: Kategori plan yang membedakan jenis membership — registration (pendaftaran member), trial (coba 1 sesi), monthly_no_equipment (bulanan tanpa alat sendiri), monthly_with_equipment (bulanan dengan alat sendiri), family (paket keluarga).

## Requirements

### Requirement 1: Membership Plan Management

**User Story:** As an admin, I want to create and manage membership plans with different tiers and session quotas, so that I can offer various archery training packages.

#### Acceptance Criteria

1. THE Admin_Dashboard SHALL provide a CRUD interface for managing Membership_Plan records.
2. WHEN creating a Membership_Plan, THE Admin_Dashboard SHALL require: name, Plan_Category, price, session quota (number of sessions), duration in days, and description.
3. WHEN a Membership_Plan has Plan_Category "monthly_no_equipment" or "monthly_with_equipment", THE Membership_Service SHALL store the session quota as the monthly allowance.
4. WHEN a Membership_Plan has Plan_Category "trial", THE Membership_Service SHALL set the session quota to 1.
5. WHEN a Membership_Plan has Plan_Category "registration", THE Membership_Service SHALL treat the plan as a one-time registration fee with zero session quota.
6. THE Admin_Dashboard SHALL allow toggling a Membership_Plan between active and inactive status.
7. WHILE a Membership_Plan is inactive, THE Membership_Service SHALL prevent new purchases of that plan.
8. THE Admin_Dashboard SHALL allow specifying whether equipment is provided for a Membership_Plan.
9. WHEN a Membership_Plan has Plan_Category "family", THE Admin_Dashboard SHALL require specifying the number of family members included.

### Requirement 2: Selling Membership to Customer

**User Story:** As a cashier/admin, I want to sell a membership plan to a customer (optionally combined with other products in the same transaction), so that the customer can start using their archery training sessions.

#### Acceptance Criteria

1. WHEN a cashier initiates a membership sale, THE Transaction_System SHALL display available active Membership_Plans grouped by Plan_Category.
2. WHEN a membership is sold, THE Transaction_System SHALL record the sale as a transaction linked to the customer.
3. THE Transaction_System SHALL allow adding a Membership_Plan as a line item alongside regular products (e.g., busur, anak panah) in the same transaction.
4. WHEN a transaction contains both membership and product items, THE Transaction_System SHALL process payment for the combined total.
5. WHEN a membership transaction is completed with payment status "paid", THE Membership_Service SHALL create a Customer_Membership record with start date set to the current date and end date calculated from the plan duration.
6. WHEN a monthly membership is activated, THE Membership_Service SHALL set the remaining session quota to the plan's session quota value.
7. IF a customer does not have a "registration" type membership record, THEN THE Membership_Service SHALL require the customer to purchase a registration plan first before purchasing a monthly plan.
8. WHEN a membership is activated, THE Membership_Service SHALL record the associated transaction ID for audit purposes.
9. WHEN a transaction contains a membership item, THE Transaction_System SHALL limit to maximum one membership plan per transaction.

### Requirement 3: Session Usage Tracking

**User Story:** As an admin/cashier, I want to record when a customer uses a training session, so that the session quota is accurately tracked.

#### Acceptance Criteria

1. WHEN a customer checks in for a training session, THE Membership_Service SHALL decrement the remaining session quota by 1.
2. IF a customer's remaining session quota is 0, THEN THE Membership_Service SHALL reject the check-in and display a message indicating no remaining sessions.
3. WHEN a session is recorded, THE Membership_Service SHALL create a Session_Usage record with the customer ID, date, and time.
4. THE Admin_Dashboard SHALL provide an interface to record session check-ins by selecting or searching for a customer.
5. WHEN a Membership_Plan has Plan_Category "family", THE Membership_Service SHALL decrement the session quota by 1 for each family member who checks in under that membership.
6. IF a customer has a "trial" membership, THEN THE Membership_Service SHALL allow exactly 1 session usage.

### Requirement 4: Membership Status and Expiration

**User Story:** As a system, I want to track membership periods and expiration, so that expired memberships are correctly identified.

#### Acceptance Criteria

1. THE Membership_Service SHALL calculate membership status as "active" when the current date is between start date and end date (inclusive) and the membership has been paid.
2. THE Membership_Service SHALL calculate membership status as "expired" when the current date is past the end date.
3. THE Membership_Service SHALL provide a method to check if a given customer has an active membership with remaining session quota.
4. WHEN a Customer_Membership reaches its end date, THE Membership_Service SHALL mark the status as expired regardless of remaining session quota.
5. THE Membership_Service SHALL calculate membership status as "pending" when payment has not been confirmed.

### Requirement 5: Membership Display in Customer Portal

**User Story:** As a customer, I want to see my membership information when I log in, so that I know my remaining sessions and membership status.

#### Acceptance Criteria

1. WHEN a customer logs in to the Customer_Portal, THE Customer_Portal SHALL display the customer's active membership information on the dashboard.
2. WHEN a customer has an active membership, THE Customer_Portal SHALL show: plan name, remaining session quota, total session quota, start date, end date, and remaining days until expiration.
3. WHEN a customer has no active membership, THE Customer_Portal SHALL display a message indicating no active membership with a link to purchase one.
4. THE Customer_Portal SHALL display a history of session usage for the current membership period.
5. WHEN a membership is within 7 days of expiration, THE Customer_Portal SHALL display a warning notification to the customer.
6. THE Customer_Portal SHALL display the customer's membership history (past and current memberships).

### Requirement 6: Membership Purchase from Customer Portal

**User Story:** As a customer, I want to purchase or renew a membership from the customer portal, so that I can manage my membership without visiting the location.

#### Acceptance Criteria

1. THE Customer_Portal SHALL display available active Membership_Plans grouped by Plan_Category with prices and session quotas.
2. WHEN a customer selects a plan to purchase, THE Customer_Portal SHALL create a transaction and redirect to the payment gateway.
3. WHEN payment is confirmed via webhook, THE Membership_Service SHALL activate the membership for the customer.
4. IF payment fails or expires, THEN THE Membership_Service SHALL not activate the membership and THE Customer_Portal SHALL display a payment failure message.
5. WHEN a customer's current membership is expiring within 7 days or has expired, THE Customer_Portal SHALL show a renewal option for the same plan.

### Requirement 7: Membership Renewal and Extension

**User Story:** As a customer, I want to renew my membership before it expires, so that I can continue training without interruption.

#### Acceptance Criteria

1. WHEN a customer renews a membership while the current one is still active, THE Membership_Service SHALL create a new membership period starting from the day after the current end date.
2. WHEN a customer renews a membership after expiration, THE Membership_Service SHALL create a new membership period starting from the current date.
3. THE Membership_Service SHALL not carry over unused session quota from an expired membership to a new period.
4. WHEN a renewal transaction is completed, THE Membership_Service SHALL send a confirmation that includes the new period dates and session quota.

### Requirement 8: Admin Membership Overview

**User Story:** As an admin, I want to see membership statistics and member lists, so that I can monitor the business performance.

#### Acceptance Criteria

1. THE Admin_Dashboard SHALL provide a list view of all Customer_Membership records with filtering by status (active, expired, pending) and Plan_Category.
2. THE Admin_Dashboard SHALL display membership statistics including: total active members, total revenue from membership sales, members expiring within 7 days, and session utilization rate.
3. WHEN viewing a customer's detail, THE Admin_Dashboard SHALL show the customer's membership history and session usage log.
4. THE Admin_Dashboard SHALL provide a daily check-in log showing all session usages for a selected date.

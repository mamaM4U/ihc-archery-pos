# PRD: IHC Archery - Club Management System

## 1. Project Overview

**Product Name:** IHC Archery App
**Type:** Single-organization scheduling & member management system
**Target Users:** IHC Archery Club members, coaches, and admin
**Tech Stack:** DartFrog (Backend), Flutter (Mobile), PostgreSQL (Database)

## 2. Core Concept

Aplikasi mobile untuk manage club panahan IHC Archery dengan sistem scheduling berbasis template dan dual booking flow (member-initiated atau coach-initiated).

**Key Simplifications dari TargetHood:**
- Single organization (IHC Archery only)
- No multi-tenant architecture
- Simplified role hierarchy
- No invite code system (admin creates users directly)

## 3. User Roles & Permissions

### 3.1 Role Hierarchy

```
Admin (Club Management)
    ↓
Coach (Training Management)
    ↓
Guardian/Wali (Member Supervision)
    ↓
Member (Athletes)
```

### 3.2 Role Capabilities

**Admin:**
- Full system access
- Create & manage coaches
- Create & manage members
- View all schedules & bookings
- View reports & statistics

**Coach:**
- Create weekly schedule templates
- Add/manage members assigned to them
- Assign guardians to members
- Book slots for members (force booking)
- Approve/reject member bookings
- View & manage all their bookings
- Record member health/fitness data

**Guardian/Wali:**
- View member schedules (members they supervise)
- Approve coach-initiated bookings for their member
- View member data (read-only)
- Cannot book slots

**Member:**
- View available schedules
- Book available slots (self-booking)
- Approve coach-initiated bookings
- View own bookings
- View own health/fitness data

## 4. Core Features

### 4.1 User Management

**users Table:**
- id, name, email, password, phone
- role: admin, coach, guardian, member
- avatar, is_active
- timestamps

**user_relationships (For Guardian-Member link):**
- id, guardian_id (FK → users), member_id (FK → users)
- can_approve_booking (boolean)
- timestamps

**coach_members (For Coach-Member assignment):**
- id, coach_id (FK → users), member_id (FK → users)
- assigned_at
- timestamps

### 4.2 Scheduling System (CORE FEATURE)

**Konsep:**
1. Coach creates **weekly template** (pattern mingguan)
2. System **auto-generates slots** when accessed (lazy generation)
3. Members book slots OR coach books for members
4. Approval flow: pending → approved/rejected

**Database Tables:**

**coach_weekly_templates:**
- id, coach_id (FK → users)
- template_name, booking_open_days (e.g., 7 for H-7)
- is_active, notes
- timestamps

**template_slots (Pattern Definition):**
- id, template_id (FK → coach_weekly_templates)
- day_of_week (0-6, Sunday-Saturday)
- session_name (e.g., "Pagi", "Sore", "Malam")
- start_time, end_time (HH:MM format)
- location, max_capacity, duration_minutes
- timestamps

**schedule_slots (Generated Slots):**
- id, coach_id (FK → users)
- template_slot_id (nullable, FK → template_slots)
- slot_date (specific date), session_name
- start_time, end_time, location
- max_capacity, current_bookings
- status: available, cancelled, full
- source: template, manual
- timestamps

**slot_registrations (Bookings):**
- id, slot_id (FK → schedule_slots), member_id (FK → users)
- registration_order (1, 2, 3... for FCFS)
- status: pending, pending_member_approval, approved, rejected, cancelled
- initiated_by (FK → users) - who created this booking
- approved_by (FK → users), approved_at
- notes
- timestamps

### 4.3 Dual Booking Flow

**Flow 1: Member-Initiated (Normal)**
```
1. Member browses available slots
2. Member books slot → status: "pending"
3. Coach approves/rejects → status: "approved"/"rejected"
```

**Flow 2: Coach-Initiated (Force Booking)**
```
1. Coach books slot for specific member → status: "pending_member_approval"
2. Member/Guardian approves → status: "approved"
3. Member/Guardian rejects → status: "rejected"
```

**Special Rules for Coach-Initiated:**
- Can bypass booking window (book any future date)
- Can override capacity (overbooking allowed)
- Perfect for walk-ins or special arrangements

### 4.4 Member Data Tracking

**member_data Table:**
- id, member_id (FK → users), recorded_by (FK → users)
- weight (kg), height (cm), body_fat (%)
- notes, recorded_at
- timestamps

**Use Case:**
- Coach records member progress
- Member views their own history
- Guardian can view member they supervise

### 4.5 Notifications (Future Enhancement)

**notifications Table:**
- id, user_id, title, body
- type: booking_pending, booking_approved, booking_rejected, new_schedule
- is_read, data (JSON)
- timestamps

## 5. Key User Flows

### 5.1 Admin Creates Coach

```
1. Admin logs in
2. Navigate to User Management
3. Create new user with role: "coach"
4. Coach receives credentials
```

### 5.2 Coach Creates Member

```
1. Coach logs in
2. Navigate to My Members
3. Add new member
4. Optionally assign guardian
```

### 5.3 Coach Creates Schedule

```
1. Coach creates weekly template:
   {
     "template_name": "Jadwal Januari 2025",
     "booking_open_days": 7,
     "slots": [
       {
         "day_of_week": 1, // Senin
         "session_name": "Pagi",
         "start_time": "08:00",
         "end_time": "10:00",
         "location": "Lapangan A",
         "max_capacity": 5
       },
       {
         "day_of_week": 1, // Senin
         "session_name": "Sore",
         "start_time": "16:00",
         "end_time": "18:00",
         "location": "Lapangan A",
         "max_capacity": 5
       }
     ]
   }

2. System auto-generates slots when member accesses schedule
3. Slots available for booking based on booking_open_days
```

### 5.4 Member Books Slot

```
1. Member opens app
2. Views available schedules (next 7 days)
3. Selects available slot → Books
4. Status: "pending"
5. Coach receives notification
6. Coach approves → Status: "approved"
7. Member receives confirmation
```

### 5.5 Coach Books for Member

```
1. Coach opens member's schedule
2. Selects slot → "Book for Member"
3. Status: "pending_member_approval"
4. Member/Guardian receives notification
5. Member/Guardian approves → Status: "approved"
```

## 6. API Endpoints

### 6.1 Authentication
- POST /api/auth/login
- POST /api/auth/logout
- GET /api/auth/me
- POST /api/auth/change-password

### 6.2 Admin
- GET /api/admin/users
- POST /api/admin/users
- PUT /api/admin/users/{id}
- DELETE /api/admin/users/{id}
- GET /api/admin/dashboard (statistics)

### 6.3 Coach
- GET /api/coach/templates
- POST /api/coach/templates
- PUT /api/coach/templates/{id}
- DELETE /api/coach/templates/{id}
- GET /api/coach/schedule
- POST /api/coach/schedule/manual-slot (create one-off slot)
- PATCH /api/coach/schedule/{slot_id}/cancel
- GET /api/coach/registrations/pending
- PATCH /api/coach/registrations/{id}/approve
- PATCH /api/coach/registrations/{id}/reject
- POST /api/coach/registrations/book-for-member
- GET /api/coach/members
- POST /api/coach/members
- POST /api/coach/members/{id}/data (record health data)

### 6.4 Member
- GET /api/member/schedule (available slots)
- POST /api/member/schedule/{slot_id}/book
- GET /api/member/bookings (my registrations)
- DELETE /api/member/bookings/{id}/cancel
- PATCH /api/member/bookings/{id}/approve (for coach-initiated)
- PATCH /api/member/bookings/{id}/reject (for coach-initiated)
- GET /api/member/my-data

### 6.5 Guardian
- GET /api/guardian/members (members they supervise)
- GET /api/guardian/members/{id}/schedule
- GET /api/guardian/members/{id}/bookings
- PATCH /api/guardian/bookings/{id}/approve
- PATCH /api/guardian/bookings/{id}/reject
- GET /api/guardian/members/{id}/data

## 7. Database Schema

```sql
-- Users
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role VARCHAR(20) NOT NULL CHECK (role IN ('admin', 'coach', 'guardian', 'member')),
    avatar VARCHAR(255),
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Guardian-Member Relationships
CREATE TABLE user_relationships (
    id SERIAL PRIMARY KEY,
    guardian_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    member_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    can_approve_booking BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(guardian_id, member_id)
);

-- Coach-Member Assignments
CREATE TABLE coach_members (
    id SERIAL PRIMARY KEY,
    coach_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    member_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    assigned_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(coach_id, member_id)
);

-- Weekly Templates
CREATE TABLE coach_weekly_templates (
    id SERIAL PRIMARY KEY,
    coach_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    template_name VARCHAR(255) NOT NULL,
    booking_open_days INTEGER DEFAULT 7,
    is_active BOOLEAN DEFAULT true,
    notes TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Template Slots (Pattern)
CREATE TABLE template_slots (
    id SERIAL PRIMARY KEY,
    template_id INTEGER REFERENCES coach_weekly_templates(id) ON DELETE CASCADE,
    day_of_week INTEGER NOT NULL CHECK (day_of_week BETWEEN 0 AND 6),
    session_name VARCHAR(50) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    location VARCHAR(255),
    max_capacity INTEGER DEFAULT 5,
    duration_minutes INTEGER,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Generated Schedule Slots
CREATE TABLE schedule_slots (
    id SERIAL PRIMARY KEY,
    coach_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    template_slot_id INTEGER REFERENCES template_slots(id) ON DELETE SET NULL,
    slot_date DATE NOT NULL,
    session_name VARCHAR(50) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    location VARCHAR(255),
    max_capacity INTEGER DEFAULT 5,
    current_bookings INTEGER DEFAULT 0,
    status VARCHAR(20) DEFAULT 'available' CHECK (status IN ('available', 'cancelled', 'full')),
    source VARCHAR(20) DEFAULT 'template' CHECK (source IN ('template', 'manual')),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(coach_id, slot_date, session_name)
);

-- Slot Registrations (Bookings)
CREATE TABLE slot_registrations (
    id SERIAL PRIMARY KEY,
    slot_id INTEGER REFERENCES schedule_slots(id) ON DELETE CASCADE,
    member_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    registration_order INTEGER NOT NULL,
    status VARCHAR(30) DEFAULT 'pending' 
        CHECK (status IN ('pending', 'pending_member_approval', 'approved', 'rejected', 'cancelled')),
    initiated_by INTEGER REFERENCES users(id),
    approved_by INTEGER REFERENCES users(id),
    approved_at TIMESTAMP,
    notes TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Member Health/Fitness Data
CREATE TABLE member_data (
    id SERIAL PRIMARY KEY,
    member_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    recorded_by INTEGER REFERENCES users(id),
    weight DECIMAL(5,2),
    height DECIMAL(5,2),
    body_fat DECIMAL(5,2),
    notes TEXT,
    recorded_at TIMESTAMP DEFAULT NOW(),
    created_at TIMESTAMP DEFAULT NOW()
);

-- Notifications (Future)
CREATE TABLE notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    body TEXT,
    type VARCHAR(50),
    is_read BOOLEAN DEFAULT false,
    data JSONB,
    created_at TIMESTAMP DEFAULT NOW()
);
```

## 8. Business Rules

### 8.1 Slot Generation (Lazy Loading)
- Slots are NOT pre-generated via cron
- Generated on-demand when member accesses schedule
- Check if slot exists for date → if not, generate from active template
- Cache generated slots in schedule_slots table

### 8.2 Booking Window
- `booking_open_days = 7` → slots available from today until H+7
- Coach force-booking bypasses this rule

### 8.3 Capacity Management
- `max_capacity` defined in template_slots
- `current_bookings` tracked in schedule_slots
- Slot status changes to "full" when `current_bookings >= max_capacity`
- Coach force-booking can override (allow overbooking)

### 8.4 Registration Order (FCFS)
- First registration for a slot gets `registration_order = 1`
- Second gets 2, third gets 3, etc.
- Used for waitlist if needed

### 8.5 Approval Flow
- Member-initiated: Requires coach approval
- Coach-initiated: Requires member/guardian approval
- Guardian can only approve for members they're assigned to

## 9. Mobile App Screens

### 9.1 Common Screens
- Splash Screen
- Login Screen
- Profile Screen (view & edit)
- Change Password
- Settings (theme, notifications)

### 9.2 Member Screens
- Home/Dashboard
  - Upcoming bookings
  - Quick book button
- Schedule Browser
  - Calendar view
  - Available slots list
  - Book button
- My Bookings
  - Pending, Approved, Past tabs
  - Cancel booking
  - Approve coach-initiated
- My Progress
  - Weight/height history
  - Charts

### 9.3 Coach Screens
- Home/Dashboard
  - Today's schedule
  - Pending approvals count
- Schedule Management
  - Template list
  - Create/edit template
  - View generated slots
  - Create manual slot
- Pending Approvals
  - List with approve/reject buttons
  - Batch approve
- My Members
  - Member list
  - Add member
  - View member details
  - Record member data
  - Book for member

### 9.4 Guardian Screens
- Home/Dashboard
  - Members they supervise
- Member Schedule
  - View member's bookings
  - Approve/reject coach bookings
- Member Progress
  - View member's data history

### 9.5 Admin Screens
- Home/Dashboard
  - Statistics cards
  - Quick actions
- User Management
  - User list with filters
  - Add/edit user
  - Activate/deactivate
- Reports (Future)
  - Booking statistics
  - Active members

## 10. MVP Scope

### Phase 1 (MVP)
- [x] Authentication (Login)
- [x] Theme system (Light/Dark)
- [ ] User profile
- [ ] Coach: Create template
- [ ] Coach: View schedule
- [ ] Member: View schedule
- [ ] Member: Book slot
- [ ] Coach: Approve booking

### Phase 2
- [ ] Coach: Book for member
- [ ] Member: Approve coach booking
- [ ] Guardian: View & approve
- [ ] Member data tracking
- [ ] Notifications (in-app)

### Phase 3
- [ ] Push notifications
- [ ] Reports & statistics
- [ ] Export data
- [ ] Admin dashboard

---

**Document Version:** 2.0
**Last Updated:** 2025-12-12
**Author:** IHC Archery Team

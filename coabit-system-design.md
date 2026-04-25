# Coabit — System Design Document

---

## Functional Requirements

### Auth

1. User should be able to register with email and password
2. User should receive an email verification link after registration and must verify before accessing protected routes
3. User should be able to log in and receive an access token + refresh token
4. User should be able to refresh their access token using a valid refresh token
5. User should be able to request a password reset via email
6. User should be able to reset their password via a time-limited token sent to their email

---

### Listing

1. User should be able to create a listing (owner role required)
2. User should be able to update their own listing
3. User should be able to soft-delete (deactivate) their own listing
4. User should be able to search for listings filtered by: state, LGA, listing type, price range, number of rooms, toilets, parking space
5. User should be able to fetch all active listings (paginated)
6. User should be able to fetch all listings belonging to them (paginated)
7. User should be able to view a single listing and get its full details including images
8. Listing should support a status lifecycle: `draft → active → closed → archived`
9. When an application is accepted, the listing status should automatically transition to `closed`
10. User should be able to upload images for their listing (via pre-signed URL or direct upload to media service)

---

### Application

1. User should be able to apply for an active listing (one application per user per listing enforced)
2. User must have a complete profile before applying
3. User should be able to see their outgoing applications and their current statuses
4. Owner should be able to see all incoming applications for their listings
5. Owner should be able to accept or reject an application
6. When an owner accepts an application:
   - That application transitions to `accepted`
   - All other `pending` applications for the same listing transition to `rejected` atomically (single DB transaction)
   - The listing transitions to `closed`
7. Applicant should be notified (in-app notification) when their application is accepted or rejected
8. User should be able to withdraw their own pending application
9. Owner should be able to view the applicant's profile when reviewing an application

---

### Profile

1. User should be able to create their profile
2. User should be able to update their profile
3. User should be able to upload a profile photo
4. Owner should be able to view an applicant's public profile to aid decision-making
5. Profile completeness is enforced as a gate before a user can submit an application
6. Profile completeness is determined by the presence of: full name, phone number, occupation, employment status, monthly income range, and a profile photo

---

### Notifications

1. User should receive an in-app notification when their application is accepted or rejected
2. User should receive an in-app notification when a new application is submitted on their listing
3. User should be able to fetch their notifications (paginated, unread first)
4. User should be able to mark notifications as read

---

### Communications (optional)

1. Owner and applicant should be able to message each other once an application is accepted (cohabit confirmed)
2. Messaging is scoped to a Conversation — one Conversation per accepted owner-applicant pair
3. User should be able to fetch all their conversations
4. User should be able to fetch all messages within a conversation (paginated)
5. User should be able to send a message within a conversation
6. Message delivery should support real-time via WebSocket or SSE

---

## Non-Functional Requirements

1. **Consistency** — Application acceptance must be atomic: accept one, reject others, close listing in a single transaction. No two applicants can be accepted for the same listing.
2. **High availability** — Listing view and search endpoints should be highly available. Read replicas and/or caching (Redis) should serve these paths.
3. **Read-heavy workload** — The system is read-heavy (browsing/searching listings). Caching strategy with appropriate TTLs and cache invalidation on listing updates should be implemented.
4. **Scalability** — The system should handle traffic spikes (e.g. end-of-year relocation season) via horizontal scaling of stateless services and a queue-backed notification/email delivery system.
5. **Rate limiting** — Listing creation, application submission, and message sending should be rate-limited per user to prevent abuse.
6. **Auth security** — Access tokens should be short-lived (15 min). Refresh tokens should be long-lived (7–30 days), stored securely (httpOnly cookie or hashed in DB), and rotated on each use.
7. **Media storage** — Images (listing photos, profile photos) should be stored in a dedicated media service (e.g. Cloudinary or S3 + CDN). The API handles upload URLs; raw files never transit the application server.
8. **Privacy / compliance** — User data handling should comply with the Nigeria Data Protection Regulation (NDPR). Users must consent to data collection at registration.
9. **Search** — Basic filtered queries on indexed fields work at early scale. Plan for migration to a dedicated search engine (e.g. Typesense or Elasticsearch) as listing volume grows.
10. **Audit log** — Sensitive state transitions (application accepted/rejected, listing closed) should be logged with actor, timestamp, and previous state for traceability.

---

## Core Entities

1. **User**
2. **Listing**
3. **Application**
4. **Profile**
5. **Notification**
6. **Conversation** *(optional)*
7. **Message** *(optional)*

---

## Data Models

### User
```
id             UUID / ObjectId
email          string (unique)
password_hash  string
role           enum: tenant | owner | both
email_verified boolean (default: false)
refresh_token  string (hashed, nullable)
created_at     timestamp
updated_at     timestamp
```

### Listing
```
id              UUID / ObjectId
owner_id        ref → User
title           string
description     string
type            enum: self-contain | flat | room | duplex | ...
state           string
lga             string
address         string
price           number
rooms           number
toilets         number
parking_space   boolean
images          string[] (media URLs)
status          enum: draft | active | closed | archived
available_from  date
created_at      timestamp
updated_at      timestamp
```

### Application
```
id           UUID / ObjectId
listing_id   ref → Listing
applicant_id ref → User
status       enum: pending | accepted | rejected | withdrawn
created_at   timestamp
updated_at   timestamp

UNIQUE CONSTRAINT: (listing_id, applicant_id)
```

### Profile
```
id                  UUID / ObjectId
user_id             ref → User (unique)
full_name           string
phone_number        string
date_of_birth       date
gender              enum: male | female | prefer_not_to_say
occupation          string
employment_status   enum: employed | self_employed | student | unemployed
monthly_income_range enum: below_100k | 100k_300k | 300k_500k | above_500k
bio                 string (optional)
photo_url           string (nullable)
is_complete         boolean (computed)
created_at          timestamp
updated_at          timestamp
```

### Notification
```
id         UUID / ObjectId
user_id    ref → User
type       enum: application_received | application_accepted | application_rejected
payload    object (listing_id, application_id, message text)
read       boolean (default: false)
created_at timestamp
```

### Conversation *(optional)*
```
id           UUID / ObjectId
listing_id   ref → Listing
owner_id     ref → User
applicant_id ref → User
created_at   timestamp

UNIQUE CONSTRAINT: (listing_id, owner_id, applicant_id)
```

### Message *(optional)*
```
id              UUID / ObjectId
conversation_id ref → Conversation
sender_id       ref → User
content         string
read            boolean (default: false)
created_at      timestamp
```

---

## API Design

### Auth

| Method | Route | Description | Response |
|--------|-------|-------------|----------|
| POST | `/auth/register` | Register new user | `user` |
| POST | `/auth/verify-email` | Verify email with token | `{ message }` |
| POST | `/auth/login` | Login | `{ user, accessToken, refreshToken }` |
| POST | `/auth/refresh` | Refresh access token | `{ accessToken }` |
| POST | `/auth/forgot-password` | Request password reset email | `{ message }` |
| POST | `/auth/reset-password` | Reset password with token | `{ message }` |
| POST | `/auth/logout` | Invalidate refresh token | `{ message }` |

---

### Listing

| Method | Route | Description | Response |
|--------|-------|-------------|----------|
| POST | `/listing` | Create listing | `listing` |
| PATCH | `/listing/:id` | Update own listing | `listing` |
| DELETE | `/listing/:id` | Soft-delete (deactivate) own listing | `listing` |
| GET | `/listing` | Fetch all active listings (paginated) | `{ data: listing[], meta }` |
| GET | `/listing/me` | Fetch current user's listings (paginated) | `{ data: listing[], meta }` |
| GET | `/listing/:id` | Fetch single listing (full detail) | `listing` |
| GET | `/listing/search` | Search listings by filters | `{ data: listing[], meta }` |
| POST | `/listing/:id/upload` | Get pre-signed URL for image upload | `{ uploadUrl, fileUrl }` |

**Search query params:** `state`, `lga`, `type`, `min_price`, `max_price`, `rooms`, `toilets`, `parking_space`, `page`, `limit`

---

### Application

| Method | Route | Description | Response |
|--------|-------|-------------|----------|
| POST | `/application` | Apply for a listing | `application` |
| GET | `/application` | Fetch user's applications (`?type=incoming` or `?type=outgoing`) | `{ data: application[], meta }` |
| GET | `/application/:id` | Fetch single application | `application` |
| PATCH | `/application/:id/withdraw` | Withdraw own application | `application` |
| PATCH | `/application/:id/accept` | Accept an application (owner only) | `application` |
| PATCH | `/application/:id/reject` | Reject an application (owner only) | `application` |

**POST /application body:** `{ listing_id }`

---

### Profile

| Method | Route | Description | Response |
|--------|-------|-------------|----------|
| POST | `/profile` | Create profile for current user | `profile` |
| GET | `/profile/me` | Get current user's profile | `profile` |
| PATCH | `/profile/me` | Update current user's profile | `profile` |
| GET | `/profile/:userId` | View another user's public profile | `profile` |
| POST | `/profile/photo` | Upload profile photo (pre-signed URL) | `{ uploadUrl, photoUrl }` |

---

### Notifications

| Method | Route | Description | Response |
|--------|-------|-------------|----------|
| GET | `/notification` | Fetch user's notifications (paginated) | `{ data: notification[], meta }` |
| PATCH | `/notification/:id/read` | Mark a notification as read | `notification` |
| PATCH | `/notification/read-all` | Mark all notifications as read | `{ message }` |

---

### Messaging *(optional)*

| Method | Route | Description | Response |
|--------|-------|-------------|----------|
| GET | `/conversation` | Fetch user's conversations | `{ data: conversation[], meta }` |
| GET | `/conversation/:id/messages` | Fetch messages in a conversation (paginated) | `{ data: message[], meta }` |
| POST | `/conversation/:id/messages` | Send a message in a conversation | `message` |

---

## Authorization Rules

| Resource | Rule |
|----------|------|
| Create listing | Authenticated user only |
| Update / delete listing | Owner of the listing only |
| Create application | Authenticated user, profile must be complete, listing must be active, no existing application for the same listing |
| Accept / reject application | Owner of the listing the application belongs to |
| Withdraw application | Applicant who created the application, status must be `pending` |
| View applicant profile | Owner of a listing that has a pending/accepted application from that applicant |
| Send message | Participant in the conversation (owner or applicant of the accepted pair) |
| View conversation | Participant in the conversation only |

---

## Key Flows

### Application acceptance (atomic)
```
BEGIN TRANSACTION
  1. Verify requesting user is the listing owner
  2. Set application.status = accepted
  3. Set all other applications for same listing where status = pending → rejected
  4. Set listing.status = closed
  5. Create notifications for accepted applicant and all rejected applicants
  6. (Optional) Create Conversation record for owner + accepted applicant
COMMIT
```

### Profile completeness gate
```
Before POST /application:
  1. Fetch applicant's profile
  2. Check is_complete flag (or compute: full_name, phone, occupation, employment_status, income_range, photo_url all present)
  3. If incomplete → 403 with message: "Complete your profile before applying"
```

### Token refresh flow
```
POST /auth/refresh
  1. Extract refresh token from httpOnly cookie or Authorization header
  2. Hash and compare against stored refresh_token in User record
  3. If valid and not expired → issue new access token + rotate refresh token
  4. If invalid or expired → 401, force re-login
```

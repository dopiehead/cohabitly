# Coabit API Manual

## Base URL

```
https://your-domain.com/api
```

All protected routes require a JWT access token in the `Authorization` header:

```
Authorization: Bearer <access_token>
```

---

## Auth

### POST /api/auth/register

Register a new user.

**Body**

```json
{
    "username": "John Doe",
    "email": "john@example.com",
    "password": "secret123",
    "role": "tenant",
    "phone": "08012345678",
    "location": "Lagos",
    "lga": "Ikeja",
    "address": "12 Allen Avenue",
    "gender": "male"
}
```

**Response `201`**

```json
{
    "status": true,
    "message": "Registration successful. Please verify your email."
}
```

---

### POST /api/auth/verify-email

Verify email address using the token sent to the user's inbox.

**Body**

```json
{ "token": "<verification_token>" }
```

**Response `200`**

```json
{ "status": true, "message": "Email verified successfully" }
```

---

### POST /api/login

Login and receive tokens.

**Body**

```json
{ "user_email": "john@example.com", "password": "secret123" }
```

**Response `200`**

```json
{
    "status": true,
    "access_token": "<jwt>",
    "refresh_token": "<raw_refresh_token>",
    "user": {
        "id": 1,
        "email": "john@example.com",
        "roles": ["ROLE_USER"],
        "role": "tenant"
    }
}
```

---

### POST /api/auth/refresh

Rotate the refresh token and get a new access token.

**Body**

```json
{ "refresh_token": "<raw_refresh_token>" }
```

**Response `200`**

```json
{
    "status": true,
    "access_token": "<new_jwt>",
    "refresh_token": "<new_raw_refresh_token>"
}
```

---

### POST /api/auth/forgot-password

Request a password reset email.

**Body**

```json
{ "email": "john@example.com" }
```

**Response `200`**

```json
{
    "status": true,
    "message": "If that email exists, a reset link has been sent."
}
```

---

### POST /api/auth/reset-password

Reset password using the token from the email.

**Body**

```json
{ "token": "<reset_token>", "password": "newSecret123" }
```

**Response `200`**

```json
{ "status": true, "message": "Password reset successfully" }
```

---

### POST /api/auth/logout

Invalidate the current refresh token. Requires auth.

**Response `200`**

```json
{ "status": true, "message": "Logged out successfully" }
```

---

## Listing

### POST /api/listing

Create a new listing. Requires auth.

**Body**

```json
{
    "title": "2 Bedroom Flat in Lekki",
    "description": "Spacious and well ventilated",
    "location": "Lekki Phase 1",
    "state": "Lagos",
    "lga": "Eti-Osa",
    "type": "flat",
    "price": 850000,
    "rooms": 2,
    "bathrooms": 2,
    "toilets": 2,
    "parking_space": true,
    "available_from": "2026-06-01",
    "images": ["https://cdn.example.com/img1.jpg"]
}
```

**Response `201`** — returns the created listing object.

---

### PATCH /api/listing/:id

Update your own listing. Requires auth.

Send only the fields you want to change. Same fields as POST.

**Response `200`** — returns updated listing.

---

### DELETE /api/listing/:id

Soft-delete (archive) your own listing. Requires auth.

**Response `200`** — returns listing with `status: archived`.

---

### GET /api/listing

Fetch all active listings. Public.

**Query params:** `page` (default 1), `limit` (default 10)

**Response `200`**

```json
{
    "status": true,
    "data": [{ "id": 1, "title": "...", "status": "active", "...": "..." }],
    "meta": { "page": 1, "limit": 10, "total": 42, "pages": 5 }
}
```

---

### GET /api/listing/me

Fetch your own listings (all statuses). Requires auth.

**Query params:** `page`, `limit`

**Response `200`** — same shape as above.

---

### GET /api/listing/search

Search active listings by filters. Public.

**Query params:**

| Param           | Type    | Description                                      |
| --------------- | ------- | ------------------------------------------------ |
| `q`             | string  | Full-text search on title, description, location |
| `state`         | string  | e.g. `Lagos`                                     |
| `lga`           | string  | e.g. `Ikeja`                                     |
| `type`          | string  | `flat`, `room`, `self-contain`, `duplex`         |
| `min_price`     | number  | Minimum price                                    |
| `max_price`     | number  | Maximum price                                    |
| `rooms`         | number  | Number of rooms                                  |
| `toilets`       | number  | Number of toilets                                |
| `parking_space` | boolean | `true` or `false`                                |
| `page`          | number  | Page number                                      |
| `limit`         | number  | Results per page                                 |

**Response `200`** — same shape as listing index.

---

### GET /api/listing/:id

Fetch a single listing. Public.

**Response `200`**

```json
{
    "status": true,
    "data": {
        "id": 1,
        "title": "2 Bedroom Flat",
        "state": "Lagos",
        "lga": "Eti-Osa",
        "type": "flat",
        "price": 850000,
        "rooms": 2,
        "bathrooms": 2,
        "toilets": 2,
        "parking_space": true,
        "images": [],
        "status": "active",
        "available_from": "2026-06-01",
        "owner_id": 5,
        "created_at": "2026-04-25T10:00:00+00:00",
        "updated_at": "2026-04-25T10:00:00+00:00"
    }
}
```

---

### POST /api/listing/:id/upload

Upload an image to a listing (multipart). Requires auth. Owner only.

**Form field:** `image` (file)

**Response `200`**

```json
{ "status": true, "data": { "url": "https://res.cloudinary.com/..." } }
```

---

## Application

All application routes require auth.

### POST /api/application

Apply for a listing. Profile must be complete. One application per listing.

**Body**

```json
{ "listing_id": 1 }
```

**Response `201`**

```json
{
    "status": true,
    "data": {
        "id": 10,
        "listing_id": 1,
        "applicant_id": 3,
        "status": "pending",
        "created_at": "...",
        "updated_at": "..."
    }
}
```

**Error cases**

- `403` — profile incomplete
- `409` — already applied
- `422` — listing not active

---

### GET /api/application

List applications.

**Query params:**

| Param   | Values                            | Description                                                            |
| ------- | --------------------------------- | ---------------------------------------------------------------------- |
| `type`  | `outgoing` (default) / `incoming` | outgoing = your applications; incoming = applications on your listings |
| `page`  | number                            |                                                                        |
| `limit` | number                            |                                                                        |

**Response `200`** — paginated list of application objects.

---

### GET /api/application/:id

Fetch a single application. Accessible by the applicant or the listing owner.

---

### PATCH /api/application/:id/withdraw

Withdraw your own pending application.

**Response `200`** — application with `status: withdrawn`.

---

### PATCH /api/application/:id/accept

Accept an application. Owner only. Atomic operation:

- Accepts this application
- Rejects all other pending applications for the same listing
- Closes the listing
- Sends in-app notifications to all affected applicants
- Creates a Conversation between owner and accepted applicant

**Response `200`** — application with `status: accepted`.

---

### PATCH /api/application/:id/reject

Reject a single application. Owner only.

**Response `200`** — application with `status: rejected`.

---

## Profile

All profile routes require auth.

### POST /api/profile

Create your profile.

**Body**

```json
{
    "full_name": "John Doe",
    "phone_number": "08012345678",
    "date_of_birth": "1995-03-15",
    "gender": "male",
    "occupation": "Software Engineer",
    "employment_status": "employed",
    "monthly_income_range": "300k_500k",
    "bio": "Looking for a quiet place near the island."
}
```

**Employment status values:** `employed`, `self_employed`, `student`, `unemployed`

**Monthly income range values:** `below_100k`, `100k_300k`, `300k_500k`, `above_500k`

**Response `201`** — profile object with `is_complete` flag.

---

### GET /api/profile/me

Get your own profile.

---

### PATCH /api/profile/me

Update your profile. Send only changed fields.

---

### GET /api/profile/:userId

View another user's public profile. Returns limited fields (no income, phone).

---

### POST /api/profile/photo

Upload a profile photo (multipart). Sets `photo_url` and recomputes `is_complete`.

**Form field:** `photo` (file)

**Response `200`**

```json
{ "status": true, "data": { "photo_url": "https://res.cloudinary.com/..." } }
```

**Profile completeness** is determined by the presence of all of: `full_name`, `phone_number`, `occupation`, `employment_status`, `monthly_income_range`, `photo_url`.

---

## Notifications

All notification routes require auth.

### GET /api/notification

Fetch your notifications. Unread first, then by date descending.

**Query params:** `page`, `limit` (default 20)

**Response `200`**

```json
{
    "status": true,
    "unread_count": 3,
    "data": [
        {
            "id": 1,
            "type": "application_received",
            "payload": {
                "listing_id": 5,
                "application_id": 10,
                "message": "A new application was submitted."
            },
            "read": false,
            "created_at": "..."
        }
    ],
    "meta": { "page": 1, "limit": 20 }
}
```

**Notification types:** `application_received`, `application_accepted`, `application_rejected`

---

### PATCH /api/notification/:id/read

Mark a single notification as read.

**Response `200`**

```json
{ "status": true, "data": { "id": 1, "read": true } }
```

---

### PATCH /api/notification/read-all

Mark all your notifications as read.

**Response `200`**

```json
{ "status": true, "message": "All notifications marked as read" }
```

---

## Conversations & Messaging

All conversation routes require auth. Only participants (owner + accepted applicant) can access a conversation.

### GET /api/conversation

List your conversations.

**Query params:** `page`, `limit`

**Response `200`**

```json
{
    "status": true,
    "data": [
        {
            "id": 1,
            "listing_id": 5,
            "owner_id": 2,
            "applicant_id": 7,
            "created_at": "..."
        }
    ],
    "meta": { "page": 1, "limit": 10 }
}
```

---

### GET /api/conversation/:id/messages

Fetch messages in a conversation. Marks messages as read. Returns a Mercure JWT for real-time subscription.

**Query params:** `page`, `limit` (default 30)

**Response `200`**

```json
{
    "status": true,
    "mercure_jwt": "<token>",
    "data": [
        {
            "id": 1,
            "sender_id": 2,
            "content": "Hello!",
            "read": true,
            "created_at": "..."
        }
    ],
    "meta": { "page": 1, "limit": 30, "total": 12, "pages": 1 }
}
```

**Real-time:** Subscribe to the Mercure topic `conversation/{id}` using the returned `mercure_jwt`.

---

### POST /api/conversation/:id/messages

Send a message in a conversation. Publishes to Mercure in real-time.

**Body**

```json
{ "content": "Is the apartment still available?" }
```

**Response `201`** — the created message object.

---

## Error Responses

All errors follow this shape:

```json
{ "status": false, "message": "Descriptive error message" }
```

| Code  | Meaning                                  |
| ----- | ---------------------------------------- |
| `400` | Bad request / missing fields             |
| `401` | Unauthenticated                          |
| `403` | Forbidden / profile incomplete           |
| `404` | Resource not found                       |
| `409` | Conflict (duplicate)                     |
| `422` | Unprocessable (invalid state transition) |
| `500` | Server error                             |

---

## Listing Status Lifecycle

```
draft → active → closed → archived
```

- Listings are created as `draft`. Publish by PATCHing `status: active`.
- When an application is accepted, the listing automatically transitions to `closed`.
- Soft-deleting a listing sets it to `archived`.

---

## Running Migrations

```bash
php bin/console doctrine:migrations:migrate
```

---

## Environment Variables

| Variable                | Description                  |
| ----------------------- | ---------------------------- |
| `DATABASE_URL`          | PostgreSQL connection string |
| `JWT_SECRET_KEY`        | Path to JWT private key      |
| `JWT_PUBLIC_KEY`        | Path to JWT public key       |
| `JWT_PASSPHRASE`        | JWT key passphrase           |
| `MERCURE_URL`           | Mercure hub internal URL     |
| `MERCURE_PUBLIC_URL`    | Mercure hub public URL       |
| `MERCURE_JWT_SECRET`    | Mercure JWT signing secret   |
| `CLOUDINARY_CLOUD_NAME` | Cloudinary cloud name        |
| `CLOUDINARY_API_KEY`    | Cloudinary API key           |
| `CLOUDINARY_API_SECRET` | Cloudinary API secret        |
| `MAILER_DSN`            | Mailer transport DSN         |

# Executive Cricket Club (ECC) API Documentation

**Version:** v1
**Base URL:** `{{base_url}}/api/v1` (e.g., `http://localhost:8000/api/v1`)

## Overview

The ECC API is a RESTful service built with Laravel. It uses JWT (JSON Web Tokens) for authentication.

### Authentication
- **Type:** Bearer Token
- **Header:** `Authorization: Bearer <token>`
- **Token Acquisition:** Obtained via `/auth/register` or `/auth/login`.

### Common Headers
| Header | Value | Required | Description |
| :--- | :--- | :--- | :--- |
| `Accept` | `application/json` | Yes | Ensures JSON responses. |
| `Content-Type` | `application/json` | Yes | For POST/PUT/PATCH requests. |
| `Authorization` | `Bearer <token>` | Yes | For protected endpoints. |

### Response Envelope
The API uses a standardized response format:

```json
{
  "success": true,
  "message": "Operation successful.",
  "data": { ... },
  "meta": null,
  "errors": null
}
```

### Error Format
For validation errors (422) or other failures:

```json
{
  "success": false,
  "message": "Validation Error",
  "data": null,
  "meta": null,
  "errors": {
    "field_name": ["Error message 1"]
  }
}
```

---

## Integration Flow

The typical mobile integration flow follows this sequence:

1.  **Auth**: User registers (`POST /auth/register`) or logs in (`POST /auth/login`).
2.  **OTP**: User requests Phone OTP (`POST /auth/request-otp`) and verifies it (`POST /auth/verify-otp`).
3.  **Application Entry**: Check for existing application (`GET /membership-application/current`).
4.  **Profile Building**:
    *   Update Personal Details (`PATCH .../personal-details`)
    *   Update Cricket Profile (`PATCH .../cricket-profile`) - *Use Meta endpoints to get options.*
    *   Submit Collector Intent (`PATCH .../collector-intent`) - *Triggers Tier Recommendation.*
5.  **Selection**: User selects a tier (`POST .../select-tier`).
6.  **Payment**: User confirms payment (`POST .../payment/confirm`).
7.  **Submission**: User submits final application (`POST .../submit`).

---

## Endpoints

### Auth

#### POST /auth/register
Register a new user and start an application.
*   **Auth:** No
*   **Body:**
    ```json
    {
        "name": "Test User",
        "email": "test@example.com",
        "phone": "+1234567890",
        "password": "password",
        "password_confirmation": "password"
    }
    ```
*   **Success Response:** Returns Access Token and Application ID.

#### POST /auth/request-otp
Request a phone verification OTP.
*   **Auth:** Yes
*   **Body:**
    ```json
    {
        "phone": "+1234567890"
    }
    ```

#### POST /auth/verify-otp
Verify the received OTP.
*   **Auth:** Yes
*   **Body:**
    ```json
    {
        "phone": "+1234567890",
        "otp": "123456"
    }
    ```

#### POST /auth/login
Login for existing users.
*   **Auth:** No
*   **Body:**
    ```json
    {
        "email": "test@example.com",
        "password": "password"
    }
    ```

#### GET /auth/me
Get current user details.
*   **Auth:** Yes

#### POST /auth/refresh
Refresh the JWT token.
*   **Auth:** Yes

#### POST /auth/logout
Invalidate the current token.
*   **Auth:** Yes

---

### Membership Applications

#### GET /membership-application/current
Get the active application for the logged-in user.
*   **Auth:** Yes
*   **Success Response:** Returns full application object.

#### PATCH /membership-applications/{id}/personal-details
Update basic profile info.
*   **Auth:** Yes
*   **Body:**
    ```json
    {
        "full_name": "Test User",
        "date_of_birth": "1990-01-01",
        "country": "India",
        "city": "Mumbai"
    }
    ```

#### PATCH /membership-applications/{id}/cricket-profile
Update cricket preferences. Use **Codes** from Meta endpoints.
*   **Auth:** Yes
*   **Body:**
    ```json
    {
        "preferred_formats": ["TEST", "ODI", "T20"],
        "eras": ["ODI_90S_ERA", "MODERN_ERA"]
    }
    ```

#### PATCH /membership-applications/{id}/collector-intent
Update collecting habits. Triggers algorithmic Tier Recommendation.
*   **Auth:** Yes
*   **Body:**
    ```json
    {
        "has_acquired_memorabilia_before": true,
        "focus": "RARITY",
        "investment_horizon": "Y10_PLUS",
        "interests": ["Match Worn Gear", "Autographs"]
    }
    ```
*   **Success Response:** Includes `recommended_tier` object.

#### POST /membership-applications/{id}/select-tier
Select a specific membership tier.
*   **Auth:** Yes
*   **Body:**
    ```json
    {
        "tier_id": 3
    }
    ```

#### POST /membership-applications/{id}/payment/confirm
Process payment (Mock/Stripe).
*   **Auth:** Yes
*   **Body:**
    ```json
    {
        "method": "card",
        "amount": 50000,
        "cardholder_name": "Test User",
        "last4": "4242"
    }
    ```

#### POST /membership-applications/{id}/submit
Finalize the application.
*   **Auth:** Yes
*   **Success Response:** Application status updates to `submitted` or `active`.

---

### Membership Tiers

#### GET /membership-tiers
List all available tiers.
*   **Auth:** Yes (Inherited)

#### GET /membership-tiers/{id}
Get details for a specific tier.
*   **Auth:** Yes (Inherited)

---

### Membership Status

#### GET /membership/status
Check current approval/membership status.
*   **Auth:** Yes

---

### Meta

#### GET /meta/cricket-profile-options
Get valid codes and labels for Cricket Profile.
*   **Auth:** No

#### GET /meta/collector-intent-options
Get valid codes and labels for Collector Intent.
*   **Auth:** No

---

### Admin

#### POST /admin/broadcast/test
Trigger a test WebSocket broadcast.
*   **Auth:** Yes (Admin Role)

#### PATCH /admin/memberships/{id}/approve
Approve a pending membership.
*   **Auth:** Yes (Admin Role)

#### PATCH /admin/memberships/{id}/reject
Reject a membership application.
*   **Auth:** Yes (Admin Role)
*   **Body:**
    ```json
    {
        "reason": "Incomplete application"
    }
    ```

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

#### POST /auth/login/otp/request
Request OTP for login (Dummy).
*   **Auth:** No
*   **Body:**
    ```json
    {
        "phone": "+1234567890"
    }
    ```

#### POST /auth/login/otp/verify
Verify OTP and Login (Dummy).
*   **Auth:** No
*   **Body:**
    ```json
    {
        "phone": "+1234567890",
        "otp": "123456"
    }
    ```
*   **Success Response:** Returns Access Token (same as login).

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

---

## Archive

The **Archive** is a curated collection of memorabilia, experiences, and exclusive content. Access to items is restricted based on the user's **Membership Tier**.

### A. Overview

The permission system operates on three levels:
1.  **Category Level**: High-level grouping (e.g., "Rare Gear", "Experiences"). If a user cannot access a Category, they generally cannot see its products.
2.  **Product Level**: Individual items. Products can be **Public** (open to all) or **Restricted** (limited to specific tiers).
3.  **Attachment Level**: Specific content within a product (documents, secrets, videos). A Product might be open, but its premium attachments requires a higher tier.

#### Glossary of Terms
| Term | Description |
| :--- | :--- |
| **is_accessible / is_open** | Boolean flag indicating if the current user can view the full content/attachments. |
| **lock_message** | Human-readable reason why content is locked (e.g., "Upgrade to Gold to view"). |
| **recommended_upgrade** | Object containing details about the tier required to unlock the content. |
| **early_access_enabled** | If `true`, higher tiers get access *before* the public `go_live_at` date. |
| **go_live_at** | The official launch timestamp. Before this time, only Early Access users can enter. |
| **include_locked** | Query parameter to show items the user *cannot* access (useful for showcasing premium value). |

---

### B. Authentication

All Archive endpoints require a valid JWT.

*   **Header**: `Authorization: Bearer <access_token>`
*   **Failed Auth (401)**:
    ```json
    {
        "message": "Unauthenticated."
    }
    ```

---

### C. Data Models

#### 1. Category Object
| Field | Type | Nullable | Description |
| :--- | :--- | :--- | :--- |
| `id` | int | No | Unique ID. |
| `title` | string | No | Display title. |
| `slug` | string | No | URL-friendly slug. |
| `description` | string | Yes | Short summary. |
| `image_url` | string | Yes | Absolute URL to cover image. |
| `visibility` | string | No | `public` or `restricted`. |
| `is_accessible` | bool | No | `true` if user can enter this category. |
| `created_at` | string | No | ISO 8601 timestamp. |

#### 2. Product Object
| Field | Type | Nullable | Description |
| :--- | :--- | :--- | :--- |
| `id` | int | No | Unique ID. |
| `title` | string | No | Product name. |
| `slug` | string | No | URL-friendly slug. |
| `category_id` | int | No | Parent Category ID. |
| `category_title` | string | Yes | Parent Category Name. |
| `description_unlocked` | string | Yes | Teaser text (always visible). |
| `description_locked` | string | Yes | Full details (**HIDDEN** if `is_open: false`). |
| `price` | object | No | `{ min: int, max: int, currency: string }` |
| `is_live` | bool | No | `true` if product is currently live (launched). |
| `go_live_at` | string | Yes | Official launch timestamp. |
| `go_live_formatted` | string | Yes | Formatted date string (e.g., "01 Jan 2024"). |
| `is_open` | bool | No | `true` if user can access full details. |
| `recommended_upgrade` | object | Yes | Upgrade info if locked (see below). |
| `images` | array | No | Array of image objects `{id, url, sort_order}`. |
| `attachments` | array | No | List of files/content (see below). |

#### 3. Attachment Object
Returned within Product.
| Field | Type | Nullable | Description |
| :--- | :--- | :--- | :--- |
| `id` | int | No | Unique ID. |
| `type` | string | No | `line` (text), `kv` (key-value), `rich` (markdown). |
| `heading` | string | Yes | Optional title (e.g., "Installation Guide"). |
| `is_accessible` | bool | No | `true` if this specific attachment is unlocked. |
| `lock_message` | string | Yes | Reason if locked (e.g., "Platinum Tier Required"). |
| `recommended_upgrade` | object | Yes | Upgrade suggestion if locked. |
| `line_text` | string | Yes | **HIDDEN** if locked. Content for `line` type. |
| `kv_key` | string | Yes | **HIDDEN** if locked. Label for `kv` type. |
| `kv_value` | string | Yes | **HIDDEN** if locked. Value for `kv` type. |
| `body` | string | Yes | **HIDDEN** if locked. Markdown content for `rich` type. |

#### 4. Recommended Upgrade Object
| Field | Type | Description |
| :--- | :--- | :--- |
| `type` | string | `tier_restriction` or `early_access`. |
| `tier_id` | int | ID of the tier needed. |
| `tier_name` | string | Name of the tier (e.g., "Platinum"). |
| `access_at` | string | (Early Access only) When this tier gets access. |
| `message` | string | User-friendly CTA (e.g., "Get early access now"). |

---

### D. Endpoints

#### 1. List Categories
`GET /archive/categories`

List all categories. Use `include_locked=true` to show categories the user *cannot* access (greyed out in UI).

| Param | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `include_locked` | bool | `false` | If `true`, returns inaccessible categories with `is_accessible: false`. |
| `search` | string | null | Filter by title. |
| `per_page` | int | 15 | Pagination limit. |

**Success (200) - Mixed Access:**
```json
{
    "success": true,
    "message": "Categories fetched successfully.",
    "data": [
        {
            "id": 1,
            "title": "General Memorabilia",
            "visibility": "public",
            "is_accessible": true
        },
        {
            "id": 5,
            "title": "VIP Experiences",
            "visibility": "restricted",
            "is_accessible": false
        }
    ],
    "meta": { ... }
}
```

#### 2. Get Category Details
`GET /archive/categories/{id}`

**Responses:**
*   **200 OK**: detailed object.
*   **403 Forbidden**: User does not have the required tier.
*   **404 Not Found**: Invalid ID.

#### 3. List Products
`GET /archive/products`

Retrieve a paginated list of products.

| Param | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `category_id` | int | null | **Required/Recommended** to filter by category. Checks `archive_category_id`. |
| `page` | int | 1 | Page number. |

**Validation Error (422):**
If `category_id` is provided but non-numeric.

**Success (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 101,
            "title": "Signed Bat 2011",
            "is_open": true,
            "price": { "min": 50000, "max": 60000, "currency": "INR" },
            "recommended_upgrade": null
        },
        {
            "id": 102,
            "title": "Private Dinner",
            "is_open": false,
            "recommended_upgrade": {
                "type": "tier_restriction",
                "tier_name": "Platinum",
                "message": "Exclusive to Platinum members."
            }
        }
    ],
    "meta": { ... }
}
```

#### 4. Get Product Details
`GET /archive/products/{id}`

Fetches full details including images and attachments.
**IMPORTANT**: By default, locked attachments are **hidden** (not returned). Pass `include_locked_attachments=true` to see them as locked placeholders.

| Param | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `include_locked_attachments` | bool | `false` | If `true`, returns locked attachments with `is_accessible: false` and a `lock_message`. |

**Success (Mixed Attachment Access):**
In this example, the product is open, but one attachment is premium.
```json
{
    "success": true,
    "data": {
        "id": 101,
        "title": "Signed Bat 2011",
        "is_open": true,
        "description_locked": "This bat was used in the finals...",
        "attachments": [
            {
                "id": 10,
                "type": "rich",
                "heading": "Certificate of Authenticity",
                "is_accessible": true,
                "body": "Markdown content here..."
            },
            {
                "id": 11,
                "type": "kv",
                "heading": "Owner History",
                "is_accessible": false,
                "lock_message": "Available to Gold Tier and above.",
                "body": null
            }
        ]
    }
}
```

---

### E. Access Rules & Logic

1.  **Strict Categories**: If `Category.is_accessible` is false, the user cannot see *any* products within it (unless listing all products globally, where they appear locked).
2.  **Product Access**:
    *   **Public**: Open to everyone.
    *   **Restricted**: Requires user's tier ID to be in the allowed list (Hierarchy or Specific).
    *   **Early Access**:
        *   Product has `go_live_at` in the future.
        *   If `early_access_enabled` is true, specific tiers have an `access_start_time`.
        *   If `now() >= access_start_time`, they get in.
        *   If `now() < access_start_time`, they see a lock + "Coming soon" or "Starts in 2 days".
3.  **Upgrade Recommendations**:
    *   The API calculates the **best** missing tier.
    *   If current tier is Silver, and product requires Platinum, it recommends Platinum.
    *   If checking Early Access, it recommends the tier that grants access **soonest** (or immediately).
4.  **Attachments**:
    *   **Inherit**: Same access as Product.
    *   **Restricted**: Attachment can be restricted to a **subset** of the Product's allowed tiers.
    *   *Example*: Product is open to Gold & Platinum. "Secret Video" attachment is Platinum ONLY. Gold users see the product, but the video is locked.

### F. Concierge Ledger
The Concierge Ledger tracks the user's enquiry history for archive items.

#### 1. List Ledger Items
`GET /archive/concierge-ledger`

Paginated list of unique items the user has enquired about, ordered by the latest enquiry.

**Success (200):**
```json
{
    "data": [
        {
            "item": {
                "id": 101,
                "title": "Vintage Bat",
                "primary_image_url": "https://..."
            },
            "enquiry_summary": {
                "last_enquiry_id": 5,
                "last_enquiry_status": "new",
                "last_enquiry_created_at": "2024-02-12T10:00:00Z",
                "enquiries_count_for_item": 3
            },
            "created_at": "2024-02-12T10:00:00Z"
        }
    ],
    "meta": { "pagination": { ... } }
}
```

#### 2. Get Item History
`GET /archive/concierge-ledger/{id}`

Get detailed history for a specific item. Returns 404 if the user has never enquired about this item.

**Success (200):**
```json
{
    "data": {
        "item": { ... }, // Full Archive Product Resource
        "enquiries": [
            {
                "id": 5,
                "status": "new",
                "message": "Is this available?",
                "created_at": "2024-02-12T10:00:00Z"
            }
        ]
    }
}
```

---

### F. Client UI Guidelines (Flutter)

*   **Product Cards**:
    *   Check `is_open`.
    *   **True**: Show thumbnail + price + "View".
    *   **False**: Show generic icon/blur + Lock Icon + `recommended_upgrade.message` (e.g., "Platinum Exclusive").
*   **Product Detail Screen**:
    *   If `is_open` is False: Show only `description_unlocked` (teaser). Display a large "Unlock this Item" card using `recommended_upgrade` data.
    *   If `is_open` is True: Show full `description_locked`, images, and attachments list.
*   **Attachments List**:
    *   Always request with `?include_locked_attachments=true` to show users what they are missing.
    *   Render unlocked items normally (Markdown/Text).
    *   Render locked items as a "Premium Content" row with a Lock icon and the `lock_message`.

---

### G. Testing Checklist

1.  **Auth Token**: Ensure you are using a token for a user with a known membership (e.g., "Standard").
2.  **Categories**:
    *   Call `GET /archive/categories?include_locked=true`.
    *   Verify you see both true/false accessibility.
3.  **Products**:
    *   Call `GET /archive/products?category_id=X`.
    *   Try with an empty/invalid category ID to ensure 422 or empty list.
4.  **Locked Product**:
    *   Find a product restricted to a higher tier.
    *   Verify `is_open: false` and `recommended_upgrade` is present.
    *   Verify `description_locked` is NULL.
5.  **Attachments**:
    *   Call `GET /archive/products/{id}?include_locked_attachments=true`.
    *   Verify you see attachments with `is_accessible: false`.
    *   Verify the `lock_message` matches the restriction (e.g., "Requires Gold").


---

## Auctions

The **Auctions** module allows users to bid on exclusive items. It features real-time bidding, auto-bidding (proxy), and tiered access control similar to the Archive.

### A. Overview

- **Real-time**: Bids and status updates are broadcast via Pusher.
- **Access Control**: Tiers determine who can **view** (existence), **view clear** (unblurred), **bid**, and **auto-bid**.
- **Auto-Bid**: System automatically places bids on behalf of the user up to a max amount. Restricted by tier.

### B. Enumerations

#### 1. Lot Status (`status`)
| Value | Description |
| :--- | :--- |
| `draft` | Not visible to users. |
| `upcoming` | Visible, countdown to start. |
| `live` | Bidding is active. |
| `ended` | Time expired, processing winner. |
| `sold` | Winner confirmed. |
| `unsold` | No valid bids. |
| `cancelled` | Withdrawn by admin. |

#### 2. Access View Mode (`access.view_mode`)
| Value | Description |
| :--- | :--- |
| `clear` | Full access to details and images. |
| `blur` | Blurred images, partial details. |
| `blocked` | Access denied (hidden or placeholder). |

#### 3. Attachment Types (`type`)
Same as Archive: `line` (text), `kv` (key-value), `rich` (markdown), `file`.

### C. Endpoints

#### 1. List Auctions
`GET /auctions`

List active auctions.

| Param | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `status` | string | `live` | Filter by status (`live`, `upcoming`, `ended`). |
| `page` | int | 1 | Pagination. |

**Success (200):**
```json
{
    "data": [
        {
            "id": 1,
            "lot_no": "2026-001",
            "title": "Signed Bat",
            "status": "live",
            "current_bid": "50000.00",
            "bids_count_total": 5,
            "ends_at": "2026-02-01T12:00:00Z",
            "access": {
                "view_mode": "clear",
                "can_bid": true
            },
            "images": ["https://..."]
        }
    ],
    "meta": { ... }
}
```

#### 2. Get Auction Dossier
`GET /auctions/dossier`

Retrieve a paginated list of auctions the user has participated in, including status (Leading, Outbid, Won).

| Param | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `per_page` | int | 10 | Pagination limit. |

**Success (200):**
```json
{
    "data": [
        {
            "auction_id": 1,
            "lot_no": "2026-001",
            "title": "Signed Bat",
            "image_url": "https://...",
            "auction_status": "live",
            "dossier_status": "leading",
            "my_max_bid": {
                "amount": "50000.00",
                "currency": "INR"
            },
            "current_bid": {
                "amount": "50000.00",
                "currency": "INR"
            },
            "hammer_price": null,
            "sale": {
                "is_recorded": false,
                "winner_user_id": 123,
                "payment_status": "na",
                "payment_status_label": null
            },
            "labels": {
                "top_right": "LEADING",
                "line_1": "Current: ₹50,000",
                "line_2": null
            },
            "deep_link": {
                "type": "auction_detail",
                "id": 1
            }
        }
    ],
    "meta": { ... }
}
```

#### 3. Get Auction Details
`GET /auctions/{id}`

Get full details including bids and attachments.

**Success (200):**
```json
{
    "data": {
        "id": 1,
        "title": "Signed Bat",
        "description": "...",
        "current_bid": "50000.00",
        "bids": [
            {
                "amount": "50000.00",
                "time_human": "2 mins ago",
                "is_me": true,
                "bidder_label": "You"
            }
        ],
        "access": {
            "view_mode": "clear",
            "can_bid": true,
            "can_auto_bid": true,
            "actions": []
        }
    }
}
```

**Forbidden (403) - Restricted Access:**
Returns 403 but includes `data.access` to explain why.
```json
{
    "message": "Access Denied",
    "data": {
        "access": {
            "view_mode": "blur",
            "reason": "blurred",
            "actions": [
                {
                    "type": "upgrade_membership",
                    "label": "Upgrade for Clear View",
                    "target_tier": { ... }
                }
            ]
        }
    }
}
```

#### 3. Place Bid
`POST /auctions/{id}/bid`

**Body:**
```json
{
    "amount": 55000
}
```


---

## Contact

The **Contact** module allows authenticated users to send secure messages to the club concierge or support teams.

### 1. Get Contact Config
`GET /contact/config`

Returns configuration options for the contact form, including direct phone lines and available subjects.
**Note:** These values are configured via the Admin Dashboard.

**Success (200):**
```json
{
    "success": true,
    "data": {
        "direct_lines": [
            {
                "key": "club_concierge",
                "label": "Club Concierge",
                "type": "phone",
                "value": "+44 (0) 20 7123 4567"
            }
        ],
        "subjects": [
            { "key": "membership_upgrade", "label": "Membership Upgrade" },
            { "key": "dining_reservations", "label": "Dining Reservations" },
            { "key": "general_feedback", "label": "General Feedback" },
            { "key": "other", "label": "Other" }
        ]
    }
}
```

### 2. Submit Enquiry
`POST /contact/enquiries`

Submit a new general enquiry.

| Param | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `subject` | string | Yes | Must be one of the keys from `config`. |
| `message` | string | Yes | The enquiry content (min 5 chars). |

**Body:**
```json
{
    "subject": "membership_upgrade",
    "message": "I would like to upgrade to Platinum."
}
```

**Success (201):**
```json
{
    "success": true,
    "message": "Enquiry submitted successfully.",
    "data": {
        "id": 12,
        "subject": "membership_upgrade",
        "status": "new",
        "created_at": "2026-02-12T12:00:00Z"
    }
}
```

### 3. List My Enquiries
`GET /me/contact-enquiries`

List all enquires submitted by the current user.

**Success (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 12,
            "subject": "membership_upgrade",
            "message": "I would like to upgrade...",
            "status": "new",
            "admin_note": null,
            "created_at": "2026-02-12T12:00:00Z"
        }
    ],
    "meta": { "pagination": { ... } }
}
```


#### 4. Setup Auto-Bid
`POST /auctions/{id}/auto-bid`

Configure proxy bidding.

**Body:**
```json
{
    "max_bid": 100000,
    "increment_amount": 1000
}
```

**Success (200):**
```json
{
    "message": "Auto-bid configured successfully."
}
```

**Forbidden (403) - Auto-Bid Restricted:**
If user tier allows manual bidding but NO auto-bidding.
```json
{
    "message": "Access Denied: Auto-bidding is not enabled for your Membership Tier.",
    "actions": [
        {
            "type": "upgrade_membership",
            "label": "Upgrade to Enable Auto-Bid",
            "target_tier": { ... }
        }
    ]
}
```

#### 5. Cancel Auto-Bid
`DELETE /auctions/{id}/auto-bid`

Cancel active auto-bid.

**Success (200):**
```json
{
    "message": "Auto-bid cancelled successfully.",
    "data": {
        "lot_id": 123,
        "auto_bid": {
            "id": 45,
            "is_enabled": false,
            "status": "cancelled",
            "cancelled_at": "2026-01-25T12:00:00Z",
            "reason": "user_cancelled"
        }
    }
}
```

### D. Real-time (Pusher)

**Channel:** `private-auctions.lot.{id}`
**Events:**
- `bid.placed`: New bid update.
- `lot.update`: Status/Time/Price update.

**Auth Endpoint:** `POST /broadcasting/auth`
Standard Pusher auth. Requires Bearer Token. Send `socket_id` and `channel_name`.

---

## Profile

### Get Profile

**Endpoint:** `GET /api/v1/profile`
**Headers:**
- `Authorization: Bearer <token>`
- `Accept: application/json`

**Response:**
```json
{
    "success": true,
    "message": "Profile retrieved successfully.",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "full_name": "Johnathan Doe",
            "email": "john@example.com",
            "phone": "+1234567890",
            "member_code": "EXEC-001",
            "avatar_url": "https://api.ecc.com/storage/users/1/avatar/avatar.jpg",
            "avatar_required": false
        },
        "membership": {
            "tier": {
                "id": 1,
                "code": "platinum",
                "name": "Platinum",
                "is_active": true
            },
            "status": "active",
            "expires_at": "2026-12-31T00:00:00.000000Z"
        }
    },
    "meta": null,
    "errors": null
}
```

### Update Profile

**Endpoint:** `PATCH /api/v1/profile`
**Headers:**
- `Authorization: Bearer <token>`
- `Accept: application/json`
- `Content-Type: application/json`

**Body:**
```json
{
    "full_name": "Johnathan Doe Updated",
    "date_of_birth": "1990-01-01",
    "country": "UAE",
    "city": "Dubai",
    "phone": "+971501234567"
}
```

**Response:** Same as Get Profile (fresh data).

### Upload Avatar

**Endpoint:** `POST /api/v1/profile/avatar`
**Headers:**
- `Authorization: Bearer <token>`
- `Accept: application/json`
- `Content-Type: multipart/form-data`

**Body:**
- `avatar`: File (image/jpeg, image/png, etc.)

**Response:** Same as Get Profile (fresh data with new avatar_url).

### Get Membership Details

**Endpoint:** `GET /api/v1/profile/membership`
**Headers:**
- `Authorization: Bearer <token>`
- `Accept: application/json`

**Response:**
```json
{
    "success": true,
    "message": "Membership details retrieved.",
    "data": {
        "status": "active",
        "joined_at": "2025-01-01T00:00:00.000000Z",
        "expires_at": "2026-01-01T00:00:00.000000Z",
        "tier": {
            "id": 1,
            "code": "platinum",
            "name": "Platinum",
            "level": 1,
            "benefits": ["Benefit 1", "Benefit 2"]
        },
        "privileges": [
            {
                "id": 1,
                "key": "private_viewing",
                "name": "Private Viewing",
                "description": "Access to private viewings",
                "icon": "eye"
            }
        ]
    },
    "meta": null,
    "errors": null
}
```

---

## Shop: Checkout & Orders

This module handles User Address management, Cart Checkout pipeline, and Order Management for Shop purchases.

### A. Overview

-   **Checkout Flow**: User manages cart -> Reviews Checkout Summary -> Places Order -> Order is created (`pending_payment`) -> Payment Confirmed -> Order `paid`.
-   **Stock Logic**: Stock is deducted immediately upon successfully placing an order. If payment fails or order is cancelled, stock is restored.
-   **Pricing**: 
    -   Unit Price = `MAX(Product Base Price, MAX(Selected Variation Prices))`.
-   **Structure**: Shop Orders are distinct from Auction/Archive orders and support multiple line items.

### B. Endpoints

#### 1. User Addresses
Manage shipping and billing addresses.

**GET /shop/addresses**
List all saved addresses.
*   **Auth**: Yes
*   **Success (200)**:
    ```json
    {
        "success": true,
        "data": [
            {
                "id": 1,
                "label": "Home",
                "full_name": "Test User",
                "city": "Mumbai",
                "is_default": true,
                "type": "shipping"
            }
        ]
    }
    ```

**POST /shop/addresses**
Create a new address.
*   **Auth**: Yes
*   **Body**:
    ```json
    {
        "label": "Office",
        "full_name": "Test User",
        "phone": "+919876543210",
        "line1": "123 Business Park",
        "city": "Mumbai",
        "state": "Maharashtra",
        "postal_code": "400001",
        "country": "India",
        "is_default": false,
        "type": "shipping"
    }
    ```

**PATCH /shop/addresses/{id}**
Update an address.

**DELETE /shop/addresses/{id}**
Delete an address.

---

#### 2. Checkout Summary
**GET /shop/checkout/summary**
Get totals, fees, and stock validation before placing an order.
*   **Auth**: Yes
*   **Query Params**: `shipping_address_id` (optional, for calculating accurate shipping).
*   **Success (200)**:
    ```json
    {
        "success": true,
        "data": {
            "currency": "INR",
            "subtotal": 1500.00,
            "shipping_fee": 0.00,
            "tax_amount": 0.00,
            "discount_amount": 0.00,
            "total_amount": 1500.00,
            "items": [
                {
                    "title": "ICC Jersey",
                    "quantity": 1,
                    "unit_price": 1500.00,
                    "line_total": 1500.00,
                    "variation_values": [{ "id": 5, "caption": "Size M" }],
                    "stock_issues": []
                }
            ],
            "can_place_order": true
        }
    }
    ```

---

#### 3. Place Order
**POST /shop/checkout/place-order**
Convert Cart to Order and deduct stock.
*   **Auth**: Yes
*   **Body**:
    ```json
    {
        "shipping_address_id": 1,
        "billing_address_id": null,
        "billing_same_as_shipping": true,
        "notes": "Leave at front desk"
    }
    ```
*   **Success (201)**: Returns `ShopOrder` object.
*   **Error (409)**: Insufficient Stock. Returns message specifying which variation is out of stock.

---

#### 4. Orders
**GET /shop/orders**
List past orders (paginated).

**GET /shop/orders/{id}**
Get order details.
*   **Success (200)**:
    ```json
    {
        "success": true,
        "data": {
            "id": 101,
            "order_number": "SHP-20260205-ABC123",
            "status": "pending_payment",
            "payment_status": "unpaid",
            "totals": { "total_amount": 1500.00 },
            "shipping_address": { ... },
            "items": [ ... ]
        }
    }
    ```

---

#### 5. Order Actions

**POST /shop/orders/{id}/confirm-payment**
Mock endpoint to simulate payment gateway success.
*   **Auth**: Yes
*   **Body**: `{ "method": "mock_card", "reference": "REF123" }`
*   **Success (200)**: Order becomes `paid`.

**POST /shop/orders/{id}/cancel**
Cancel an **unpaid** order and restore stock.
*   **Auth**: Yes
*   **Body**: `{ "reason": "Changed mind" }`
*   **Success (200)**: Order becomes `cancelled`.



# ECC Mobile Notifications & Auction API Guide

## 1. Overview
The Executive Club Cricket (ECC) mobile app uses **Firebase Cloud Messaging (FCM)** for real-time auction updates. This system relies on a "Topic-based" architecture where the backend broadcasts events to specific auction topics, and the mobile app listens to them.

### Key Concepts
*   **Topics**: The app does **NOT** subscribe to topics directly via client-side code. Instead, the backend manages subscriptions based on user actions (toggling the bell icon).
*   **Topic Naming**: `ecc_auction_{lot_id}` (e.g., `ecc_auction_5`).
*   **Payloads**: All data payloads are **string-only** key-value pairs (FCM HTTP v1 requirement).
*   **Actor Filtering**: "Bid Placed" events include the `actor_user_id`. The client must check this ID and **ignore** the notification if it matches the current user's ID (to prevent buzzing yourself when you place a bid).

---

## 2. Authentication
All API requests require a valid **JWT Access Token**.

*   **Header**: `Authorization: Bearer <token>`
*   **Obtain Token**: Login via `/api/v1/auth/login` or Register via `/api/v1/auth/register`.

---

## 3. Device Token Lifecycle
The backend must know about the device's FCM token to manage subscriptions server-side.

### A. Register Token (On Login)
Call this immediately after login and whenever `FirebaseMessaging.instance.onTokenRefresh` fires.

**Endpoint**: `POST /api/v1/me/device-tokens`
**Body**:
```json
{
    "token": "fcm_token_string_here",
    "platform": "android", // or "ios"
    "device_id": "unique_device_id_optional"
}
```

**Behavior**:
*   If the token is new, it is saved.
*   **Auto-Sync**: The backend automatically subscribes this new token to all auction topics the user has enabled in their settings.

### B. Unregister Token (On Logout)
Call this when the user logs out to stop receiving notifications on this device.

**Endpoint**: `POST /api/v1/me/device-tokens/unregister`
**Body**:
```json
{
    "token": "fcm_token_string_here"
}
```

---

## 4. Auction Subscriptions
Users can toggle notifications for specific auctions (the "Bell" icon).

### A. Toggle Subscription
**Endpoint**: `PUT /api/v1/auctions/{id}/notification-subscription`
**Body**:
```json
{
    "enabled": true
}
```
**Response**:
```json
{
    "success": true,
    "message": "Subscribed to auction notifications.",
    "data": {
        "is_enabled": true
    }
}
```
**Backend Logic**: The server will immediately subscribe/unsubscribe **all** of the user's active device tokens from the FCM topic `ecc_auction_{id}`.

### B. List Subscriptions
**Endpoint**: `GET /api/v1/me/auction-notification-subscriptions`
**Response**:
```json
{
    "data": [
        {
            "id": 10,
            "auction_lot_id": 5,
            "is_enabled": true,
            "auction_lot": {
                "id": 5,
                "lot_no": "LOT-00005",
                "status": "live"
            }
        }
    ]
}
```

---

## 5. Push Payload Catalog (Current Implementation)
The backend sends **7 types** of notifications. Use the `type` field in `data` to route the user.

### Base Fields (Present in ALL Payloads)
*   `type`: (string) Event type code.
*   `lot_no`: (string) Display number (e.g., "LOT-00005").
*   `auction_lot_id`: (string) ID for API calls.
*   `event_id`: (string) Unique ID for deduplication (e.g., `bid_placed:301`).
*   `sent_at`: (string) ISO8601 timestamp.
*   `ends_at`: (string, optional) New ISO8601 end time (for timer sync).

### 1) auction_go_live
*   **Delivery**: Topic (`ecc_auction_{id}`)
*   **Triggers**: When auction status changes to `live`.
*   **Action**: Navigate to Auction Detail screen.
*   **Data Keys**: `status`

### 2) bid_placed
*   **Delivery**: Topic (`ecc_auction_{id}`)
*   **Triggers**: Any new valid bid.
*   **Client Rule**: **IGNORE** if `actor_user_id` == `currentUser.id`.
*   **Action**: Update "Current Bid" UI, animate change, update countdown if `new_ends_at` changed.
*   **Data Keys**: `bid_id`, `bid_amount`, `currency`, `actor_user_id`, `new_ends_at`

### 3) auto_bid_executed
*   **Delivery**: Direct User (Private)
*   **Triggers**: When the system auto-bids for you.
*   **Action**: Show private toast ("Your auto-bid was placed").
*   **Data Keys**: `bid_id`, `bid_amount`, `currency`, `status`

### 4) auction_reminder
*   **Delivery**: Topic (`ecc_auction_{id}`)
*   **Triggers**: 60m, 30m, 15m, 10m, 5m, 1m before end.
*   **Action**: Show urgent banner?
*   **Data Keys**: `minutes_remaining`, `status`

### 5) auction_ended
*   **Delivery**: Topic (`ecc_auction_{id}`)
*   **Triggers**: Timer hits zero (waiting for decision or results).
*   **Action**: Show "Auction Ended" state.
*   **Data Keys**: `status`

### 6) auction_results
*   **Delivery**: Topic (`ecc_auction_{id}`)
*   **Triggers**: Winner/Sold/Unsold breakdown is finalized.
*   **Action**: Show Results Modal / Ribbon.
*   **Data Keys**: `status` (sold/unsold), `final_price`, `currency`, `winner_user_id`, `winning_bid_id`

### 7) auction_winner
*   **Delivery**: Direct User (Private)
*   **Triggers**: You won the auction.
*   **Action**: Show "You Won!" confetti / modal.
*   **Data Keys**: `status`, `final_price`, `currency`, `winning_bid_id`

---

## 6. Client-Side Implementation Guide (Flutter)

### Recommended Routing
| Notification Type | Foreground Action | Background Tap Action |
|---|---|---|
| `auction_go_live` | In-app notification | Open Auction Detail |
| `bid_placed` | Update UI (Silent) | Open Auction Detail |
| `auto_bid_executed` | Toast "Auto-bid placed" | Open Auction Detail |
| `auction_reminder` | In-app notification | Open Auction Detail |
| `auction_ended` | Update UI to "Ended" | Open Auction Detail |
| `auction_results` | Show Results Ribbon | Open Auction Detail (Results State) |
| `auction_winner` | Full Screen "You Won" | Open Auction Detail (Winner View) |

### Deduplication
Use `data['event_id']` to prevent processing the same message twice (e.g., if received via both foreground listener and background handler).

### Missing Data?
If the payload is missing critical data (like specific product details), use the `auction_lot_id` from the payload to call:
`GET /api/v1/auctions/{id}`

---

## 7. Testing Checklist

### 1) Subscribe
1.  Login to Mobile App.
2.  Ensure `POST /me/device-tokens` is called.
3.  Go to an upcoming auction.
4.  Toggle "Notify Me" (Bell Icon) -> ON.
5.  Verify `PUT /auctions/{id}/notification-subscription` returns success.

### 2) Test Notification (CLI)
Ask backend dev to run:
```bash
php artisan fcm:test --topic=ecc_auction_{id} --title="Test" --body="Works?"
```
*   Verify app receives notification.

### 3) Trigger Real Lifecycle
1.  Set auction start/end time to 1 minute from now.
2.  Wait for scheduler.
3.  Verify `auction_go_live` or `auction_ended` is received.

---

## 8. API Reference

### Register Device Token
`POST /api/v1/me/device-tokens`
```json
{ "token": "...", "platform": "android" }
```

### Toggle Subscription
`PUT /api/v1/auctions/{id}/notification-subscription`
```json
{ "enabled": true }
```

### Unregister
`POST /api/v1/me/device-tokens/unregister`
```json
{ "token": "..." }
```

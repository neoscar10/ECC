# Mobile Realtime Handoff

**Generated on:** 2026-01-25  
**Project:** Executive Cricket Club (Laravel)

This document provides definitive technical details for connecting the mobile application to the realtime broadcasting layer.

---

## A) Broadcasting Setup Summary

The backend uses **Laravel Reverb**, which implements the Pusher Protocol. The mobile app should use a standard Pusher client (e.g., `pusher-websocket-flutter`) configured to point to the self-hosted Reverb server.

### 1. Driver & Client Config
- **Active Driver:** `reverb`
- **Protocol:** Pusher Compatible (Pusher Channels)
- **Authentication Scheme:** JWT (See Section C)

### 2. Connection Values
These values come from the backend environment configuration. The mobile app must configure the Pusher client with these specific override settings to reach the Reverb server instead of the public Pusher service.

| Key | Variable | Value / Notes |
| :--- | :--- | :--- |
| **App Key** | `REVERB_APP_KEY` | *(Get from existing .env)* |
| **Cluster** | `pusher_cluster` | `mt1` (Standard default, ignored by Reverb but required by client) |
| **Host** | `REVERB_HOST` | `localhost` (Dev) or Production IP/Domain |
| **Port** | `REVERB_PORT` | `8080` (Default) or `443` (if behind SSL) |
| **Scheme** | `REVERB_SCHEME` | `http` or `https` |
| **TLS/SSL** | - | Set `encrypted: true` if scheme is `https`. |

> **Note:** The web frontend (`bootstrap.js`) currently references `VITE_PUSHER_APP_KEY`. Verify if the production environment aliases `PUSHER_*` vars to Reverb values. For mobile, relying on `REVERB_*` intent is safer.

---

## B) Events & Channels Map

The following events are authoritative.

| Event Class | Broadcast Name (`.listen()`) | Channel Name (`.channel()`) | Type | Payload Keys |
| :--- | :--- | :--- | :--- | :--- |
| `MembershipUpdated` | `updated` | `admin.members` | Private | *(Empty/Lightweight)* |
| `AuctionTimelineEventCreated` | `timeline.created` | `auctions.lot.{lotId}` | Private | `lot_id`, `event_type`, `payload`, `created_at`, `actor_type`, `actor_id` |
| `AuctionStatusChanged` | `status.changed` | `auctions.lot.{lotId}` | Private | `lot_id`, `status`, `ended_at`, `winner_user_id` |
| `AuctionLotUpdated` | `lot.updated` | `auctions.lot.{lotId}` | Private | `lot_id`, `status`, `starts_at`, `ends_at`, `current_highest_bid` |
| `AuctionExtended` | `auction.extended` | `auctions.lot.{lotId}` | Private | `lot_id`, `new_ends_at`, `reason`, `extensions_used` |
| `AuctionBidPlaced` | `bid.placed` | `auctions.lot.{lotId}` | Private | `lot_id`, `amount`, `formatted_amount`, `bidder_id`, `current_highest_bid`, `ends_at` |

**Notes:**
*   All Auction events broadcast on the **same private channel**: `auctions.lot.{lotId}`.
*   Events implement `ShouldBroadcastNow`, meaning they are sent immediately without queue delays.

---

## C) Private Channel Auth (Critical)

**Current Status:**  
The project relies on `jwt-auth` for API authentication (`auth:api`).

**Issue Detected:**  
The default broadcasting auth route `POST /broadcasting/auth` is currently configured to use the `web` middleware (session-based). There is **no public API route** exposed for mobile users to authenticate private channels using a Bearer token.
*   *Existing Route:* `POST /api/v1/admin/broadcasting/auth` is restricted to Admins only.
*   *Required Route:* A generic `api/broadcasting/auth` for regular members is missing.

**Action Required:**  
See [Appendix: Minimal Fix](#appendix-minimal-fix) to enable mobile auth. Once fixed, the mobile app should use:

*   **Endpoint:** `POST /api/v1/broadcasting/auth` (or `/broadcasting/auth` depending on fix implementation)
*   **Headers:**
    *   `Authorization: Bearer <access_token>`
    *   `Accept: application/json`

**Authorization Logic (`routes/channels.php`):**
*   **Channel:** `auctions.lot.{lotId}`
*   **Logic:** Uses `AuctionAccessResolverService`. User can subscribe IF they have visibility permission for the lot.
*   **Parameter:** `lotId` (Integer)

---

## D) Example Flutter Subscription Snippets

*Assumes usage of `pusher_client` package.*

```dart
import 'package:pusher_client/pusher_client.dart';

// 1. Configure the Authorizer (With Bearer Token)
PusherAuth auth = PusherAuth(
  'https://your-api-domain.com/api/v1/broadcasting/auth', // See Section C regarding this URL
  headers: {
    'Authorization': 'Bearer $userAccessToken',
    'Accept': 'application/json',
  },
);

// 2. Configure Options for Reverb (Host/Port are critical)
PusherOptions options = PusherOptions(
  host: '192.168.1.5', // Replace with REVERB_HOST
  port: 8080,          // Replace with REVERB_PORT
  encrypted: false,    // Set to true if using HTTPS
  auth: auth,
  // clusters are ignored by Reverb but required by client
  cluster: 'mt1', 
);

// 3. Initialize Client
PusherClient pusher = PusherClient(
  'your-reverb-app-key', // REVERB_APP_KEY
  options,
  autoConnect: true,
);

// 4. Subscribe to Auction Lot
Channel channel = pusher.subscribe('private-auctions.lot.$lotId');

// 5. Listen for Bids
channel.bind('bid.placed', (PusherEvent? event) {
  print("Bid Placed Payload: ${event?.data}");
  // Update UI with new bid amount
});

// 6. Listen for Timer Function
channel.bind('auction.extended', (PusherEvent? event) {
  // Reset countdown timer to new_ends_at
});
```

---

## E) Verification Checklist

1.  **Backend Config**: Ensure `REVERB_APP_KEY`, `REVERB_APP_SECRET`, etc. are set in `.env`.
2.  **Reverb Server**: Ensure `php artisan reverb:start` is running.
3.  **Endpoint Fix**: Apply the fix in Appendix to expose `broadcasting/auth` to API users.
4.  **Test Tool**: Use the "Laravel Echo Test" tool or a simple JS script to attempt connection using a JWT token.
5.  **Traffic Flow**:
    *   Trigger event: `php artisan tinker` -> `event(new \App\Events\AuctionBidPlaced(...))`
    *   Verify Reverb log shows "Message received".
    *   Verify Mobile client receives event.

---

## Appendix: Minimal Fix

To allow mobile users (authenticated via JWT) to connect to private channels, you must register broadcast routes that use the `auth:api` middleware.

**Recommended Change in `routes/api.php`:**

Add this line inside the `v1` prefix group, but **outside** the admin group:

```php
// In routes/api.php, inside prefix('v1') group:

Broadcast::routes(['prefix' => 'api/v1', 'middleware' => ['auth:api']]);
```

*Note: This will register `POST /api/v1/broadcasting/auth` which expects the JWT Bearer token.*

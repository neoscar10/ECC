# Mobile CMS Integration Guide

This guide details how to consume the dynamic CMS Content Blocks API for the Executive Cricket Club mobile app.

## Overview

The CMS allows admins to configure:
- **Banners**: Static or actionable images.
- **Sliders**: Horizontal lists of Shop Products, Archive Items, or Auction Lots.
- **Text Cards**: Simple informational text.

All blocks are served via a unified API rooted at `/api/v1/content/`.

## Endpoints

### 1. Get Available Placements
fetches the list of active placement keys (e.g., `home`, `explore`, `promotions`).

**Request:**
`GET /api/v1/content/placements`

**Response:**
```json
{
  "success": true,
  "data": [
    "home",
    "explore",
    "profile"
  ]
}
```

### 2. Get Blocks for Placement
Fetches the ordered list of blocks for a specific screen/placement. Returns resolved access states and items.

**Request:**
`GET /api/v1/content/blocks?placement=home`

**Query Parameters:**
- `placement`: (required) The placement key.
- `include_items`: (optional, default `1`) Whether to include resolved slider items.
- `per_block_limit`: (optional, default `10`) Max items per slider.

**Authentication:** 
- **Optional**. 
- If valid Bearer Token provided: returns content visible to User's Tier.
- If No Token: returns content visible to Guests (Public).

**Response Structure (Block):**
```json
{
  "id": 1,
  "placement": "home",
  "type": "slider", // banner, slider, text, card
  "sort_order": 1,
  "is_active": true,
  "title": "New Arrivals",
  "subtitle": "Check out the latest gear",
  "badge_text": "New", // Optional badge
  "media": {
    "image_url": "https://...",
    "image_mobile_url": "https://..." // Optimized for mobile if available
  },
  "text_position": "below", // below, overlay (use for UI layout hint)
  "has_detail_page": false,
  "has_target": true, // present and true if link is configured as Target
  "target": { // ONLY if has_target == true
    "kind": "item", // category, item
    "source": "auctions", // shop, archive, auctions
    "id": 55,
    "label": "Lot 55 - Signed Bat"
  },
  "detail_endpoint": null, // If present, tap opens this API (or separate screen)
  "slider": { // ONLY if type == slider
    "mode": "category",
    "source": "shop",
    "item_limit": 10,
    "items": [ ...ItemCards... ]
  },
  "access": {
    "state": "public", // public, teaser, locked
    "is_allowed": true, // false if locked/teaser and you want to block interaction
    "show_teaser": false, // true = Show Blurred/Teaser UI
    "message": {
        "title": "Restricted View",
        "body": "Members Only",
        "icon": "lock" // lock, diamond
    },
    "actions": [ // CTA to Unlock
        {
            "type": "upgrade_membership",
            "label": "Upgrade",
            "deeplink": "/membership/tiers"
        }
    ]
  }
}
```

### 3. Get Single Block
**Request:**
`GET /api/v1/content/blocks/{id}`

returns the full block detail. If `has_detail_page` is true, this endpoint might return additional `body_text` content if allowed.

---

## Unified Item Card Schema (Slider Items)

All items in `slider.items` array follow a consistent schema, determined by `kind`.

**Common Fields:**
```json
{
  "kind": "shop_product", // shop_product, archive_item, auction_lot, static_slide
  "id": 101,
  "title": "Gray-Nicolls Bat",
  "image_url": "https://...",
  "price_label": "INR 5,000",
  "price_meta": {
    "currency": "INR",
    "amount": 5000,
     // OR range_min/max for Archive
     // OR current_bid/starting_price for Auction
  },
  "status": "active", // live, upcoming, ended (for auctions)
  "detail_endpoint": "/api/v1/shop/products/101" // Use this for tap navigation
}
```

## UI Handling Rules

1. **Access State**:
   - `public`: Render normally.
   - `teaser`: Render with Blurred Image + Lock Icon/Badge overlay. Tap triggers `access.actions`.
   - `locked`: Render placeholder/locked card. Tap triggers `access.actions`.

2. **Text Position**:
   - `overlay`: Render Title/Subtitle ON TOP of image.
   - `below`: Render Title/Subtitle BELOW image.

3. **Slider Handling**:
   - Iterate `slider.items`.
   - Use `kind` to determine specific card style (e.g. Auction might show timer badge).
   - On Tap: GET `detail_endpoint`.

4. **Deep Linking**:
   - `actions[0].deeplink` should be handled by app router (e.g. open Paywall).

# ECC Auctions Postman Collection

This directory contains the Postman collection and environment variables for testing the Executive Cricket Club (ECC) Auctions API.

## Files
- `ECC.postman_collection.json`: The Request Collection.
- `ECC.local.postman_environment.json`: The Environment variables (Local).

## Setup
1. Open Postman.
2. Click **Import** (top left).
3. Drag and drop both JSON files from this folder.
4. Select the `ECC.local` environment from the environment dropdown (top right).

## Configuration
Before making requests, you need to set your `jwt_token`.
1. Log in to your app (via frontend or existing Auth API).
2. Copy the Bearer token.
3. In Postman, click the "Eye" icon (Environment Quick Look) next to the environment dropdown.
4. Paste the token into the `jwt_token` `Current Value` field.

## Testing Flow
The collection is designed to be interactive:
1. **List Lots**: Run this request first. It will fetch the latest live auctions and **automatically populate** the `lot_id` variable in your environment with the ID of the first lot found.
2. **Get Lot Detail**: Uses the `{{lot_id}}` set by the previous step to show full details.
3. **Place Bid**: Places a test bid on the `{{lot_id}}`. *Warning: Ensure you are bidding on a test lot.*
4. **Set/Update Auto-Bid**: Configures auto-bidding for the current lot.

## Common Errors
- `401 Unauthenticated`: Your `jwt_token` is missing or expired.
- `403 Forbidden`: 
  - Your Membership Tier does not allow visibility/bidding on this lot.
  - Or you do not have permission to place auto-bids.
- `422 Unprocessable Content`: Validation error (e.g., bid amount too low).

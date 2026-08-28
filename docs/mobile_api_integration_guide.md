# Mobile Developer Integration Guide - Shop Features API

This guide provides technical specs and implementation guidance for the mobile developer to consume the updated REST APIs of the ECC Shop.

---

## 1. Product Variation Showcase on Listing (Swatches / Options)

When displaying products in a grid or list view, the mobile app should render the showcased variation group (e.g., color swatches or size buttons) under each card, matching the website behavior.

### API Endpoint
`GET /api/v1/shop/products`

### JSON Response Structure
In `ShopProductResource`, we have added a `showcase_variation_group` field:
```json
{
  "id": 1,
  "slug": "ecc-premium-shirt",
  "title": "ECC Premium Shirt",
  "has_variations": true,
  "showcase_variation_group": {
    "id": 4,
    "name": "Color",
    "presentation": "color", // can be "color", "image", or "text"
    "has_gallery_images": true,
    "values": [
      {
        "id": 12,
        "label": "Crimson Gold",
        "price": "1499.00",
        "stock": 10,
        "is_default": true,
        "presentation_image_url": null,
        "display": {
          "image_url": null,
          "color_hex": "#755a24"
        }
      }
    ]
  }
}
```

### Rendering Rules
1. If `showcase_variation_group` is `null`, do not render any swatches/options on the product card.
2. If `presentation` is `"color"` and `color_hex` is present: Render color circles using `color_hex`.
3. If `presentation` is `"image"` and `image_url` is present: Render small circular or square image thumbnails.
4. If `presentation` is `"text"`: Render small text badges/pills containing the option `label` (e.g. S, M, L).
5. User Interaction: Tapping a swatch should dynamically update the product card's main thumbnail image (if `has_gallery_images` is true and swatch has an image) or update the price helper.

---

## 2. Size Guide / Chart Integration

Products can be linked to size charts defining dimension details, measurements, and a "how to measure" guide.

### API Endpoint
`GET /api/v1/shop/products/{id}`

### JSON Response Structure
In `ShopProductDetailResource`, we added the `size_guide` dictionary:
```json
{
  "id": 1,
  "title": "ECC Premium Shirt",
  "size_guide": {
    "id": 2,
    "name": "Men's Polo T-Shirts",
    "description": "Standard sizing chart for polo shirts.",
    "how_to_measure_text": "Measure around the chest area...",
    "how_to_measure_image_url": "https://ecc-test.example.com/storage/size-guides/measure-polo.png",
    "cm_to_inch_multiplier": 0.393701,
    "table_data": {
      "headers": ["Size", "Chest (cm)", "Length (cm)"],
      "rows": [
        ["S", "96", "68"],
        ["M", "100", "70"],
        ["L", "104", "72"]
      ]
    }
  }
}
```

### Rendering Rules
1. Render a "Size Guide" or "View Chart" button on the Product Details screen **only** if the product returns a non-null `size_guide`.
2. Tapping the button should open a Modal/Sheet showing:
   - **Measurements Table**: Build a dynamic grid/table from `table_data.headers` and `table_data.rows`.
   - **Measurement Instructions**: Display `how_to_measure_text` and the `how_to_measure_image_url` (if present).
   - **Unit Toggle (cm / inches)**: Provide a switch to convert metric measurements in the table rows using the `cm_to_inch_multiplier`.

---

## 3. Country-Based Address Selection (Checkout)

Address forms during checkout are dynamically built based on the selected country's shipping capabilities.

### Flow
1. Fetch active delivery countries:
   `GET /api/v1/shop/delivery-countries`
   
   Response includes `fields` configuring the form inputs:
   ```json
   [
     {
       "id": 1,
       "name": "India",
       "code": "IN",
       "fields": [
         { "name": "full_name", "label": "Full Name", "is_required": true },
         { "name": "postal_code", "label": "Pincode", "is_required": true }
       ]
     }
   ]
   ```
2. When the user changes the Country selection, dynamically show/hide the address form fields in the UI based on the `fields` list.
3. Validate required inputs locally on the client using the `is_required` attribute, then post/put:
   - `POST /api/v1/shop/addresses`
   - `PUT /api/v1/shop/addresses/{id}`

---

## 4. User Registration, Recovery (Option B) & Login Prompt Flow

This section details how the mobile app should handle user registration, seamless recovery for unverified accounts, and automatic login prompts for already-verified users.

### API Endpoint
`POST /api/v1/register`

### Request Payload
```json
{
  "name": "John Doe",
  "email": "johndoe@example.com",
  "phone": "+919876543210",
  "password": "Password123!",
  "password_confirmation": "Password123!"
}
```

---

### Scenario A: Unverified Registration Recovery (Option B)
If a user previously started registration but closed the app before completing phone/WhatsApp OTP verification (`phone_verified_at` is `null`), re-submitting the registration form **will NOT return a duplicate email/phone error**.

Instead, the backend updates their account details, generates a fresh OTP, and returns an HTTP 200 success response containing `'is_resumed_registration': true`.

#### API Response (HTTP 200 OK)
```json
{
  "success": true,
  "message": "Registration resumed. A new verification OTP has been sent via WhatsApp.",
  "data": {
    "is_resumed_registration": true,
    "verified": false,
    "access_token": "eyJhbGciOiJIUzI1Ni...",
    "token_type": "bearer",
    "expires_in": 3600,
    "user": {
      "id": 42,
      "name": "John Doe",
      "email": "johndoe@example.com",
      "phone": "+919876543210",
      "phone_verified_at": null
    },
    "ttl_minutes": 5,
    "otp_method": "template",
    "whatsapp_number": "919876543210"
  }
}
```

#### Mobile Integration Logic for Scenario A:
1. Save the `access_token` into secure storage.
2. Check if `verified == false` or `user.phone_verified_at == null`.
3. Transition immediately to the **OTP Verification Screen** and render the countdown timer (`ttl_minutes`).
4. Prompt the user: *"We found an incomplete registration for this account. A new OTP has been sent to your WhatsApp number."*

---

### Scenario B: Verified Account Registration Attempt (Prompt User to Login)
If a user who is **already registered and verified** (`phone_verified_at` is NOT `null`) tries to submit the registration form using their existing email or phone number, the backend returns an HTTP 422 Unprocessable Entity with a structured `ACCOUNT_VERIFIED_PLEASE_LOGIN` code.

#### API Response (HTTP 422 Unprocessable Entity)
```json
{
  "success": false,
  "message": "An account with this email or phone number is already registered and verified. Please log in.",
  "code": "ACCOUNT_VERIFIED_PLEASE_LOGIN",
  "errors": {
    "email": ["An account with this email or phone number is already registered and verified. Please log in."],
    "phone": ["An account with this email or phone number is already registered and verified. Please log in."]
  },
  "data": {
    "should_login": true,
    "email": "johndoe@example.com",
    "phone": "+919876543210"
  }
}
```

#### Mobile Integration Logic for Scenario B:
1. Inspect the HTTP 422 response body for `code == "ACCOUNT_VERIFIED_PLEASE_LOGIN"` OR `data.should_login == true`.
2. Do NOT display a raw field validation error on the form.
3. Instead, display a modal dialog or alert prompt:
   > **Account Already Verified**  
   > An account with **johndoe@example.com** is already registered and verified. Please log in to continue.  
   > `[ Log In Now ]` `[ Cancel ]`
4. Clicking **[ Log In Now ]** pre-fills the email/phone field on the **Login Screen** and focuses the Password input field.

---

### Scenario C: Fresh New User Registration
When a new user registers with unused credentials:

#### API Response (HTTP 200 OK / 201 Created)
```json
{
  "success": true,
  "message": "Registration successful. Please verify OTP.",
  "data": {
    "is_resumed_registration": false,
    "verified": false,
    "access_token": "eyJhbGciOiJIUzI1Ni...",
    "user": {
      "id": 43,
      "name": "Jane Smith",
      "email": "janesmith@example.com",
      "phone": "+919876543211",
      "phone_verified_at": null
    },
    "ttl_minutes": 5
  }
}
```

#### Mobile Integration Logic for Scenario C:
1. Save `access_token`.
2. Direct user to **OTP Verification Screen**.


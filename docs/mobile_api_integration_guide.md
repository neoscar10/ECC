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

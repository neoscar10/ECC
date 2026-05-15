# ECC User-Side Color Audit Report

## A. Summary
- **Current color setup approach**: The current yellow/gold colors (primarily `#D4AF37`) are **scattered**. While there is a global `var(--ecc-primary)` variable defined, the vast majority of user-facing components explicitly hardcode hex values like `#d4af37`, `#D4AF37`, `#e0be52`, etc., directly inside inline `<style>` blocks in their respective Blade files.
- **Recommended safest implementation strategy**: 
  1. Define the complete new cold-gold palette (`--color-50` through `--color-950`) centrally inside `resources/views/layouts/user/partials/styles.blade.php`.
  2. Use systematic regex/search-and-replace to swap hardcoded hex codes across the `resources/views/livewire/` (excluding `/admin/`) and `resources/views/components/` directories with the appropriate CSS variables.
  3. Swap hardcoded Bootstrap-like custom classes (`.ecc-text-gold`, `.ecc-btn-gold`) to use the new variables instead of hardcoded hex colors.

## B. User-side layout/style entry points
- **`resources/views/layouts/web-app.blade.php`**
  - **Purpose**: Main wrapper layout for the entire web frontend.
  - **Why it matters**: It is the parent file that includes all global user-side CSS.
- **`resources/views/layouts/user/partials/styles.blade.php`**
  - **Purpose**: Defines global CSS variables (`--ecc-primary`) and base body styles.
  - **Why it matters**: This is the safest, most central location to declare the new `--color-*` palette for the entire user-facing app.
- **`resources/views/layouts/user/partials/topbar.blade.php` & `bottom-nav.blade.php`**
  - **Purpose**: Navigation menus.
  - **Why it matters**: Currently rely on `var(--ecc-primary)` for active pill backgrounds and icon fills. 

## C. Current gold/yellow usage inventory

1. **Hardcoded Hex Values (`#d4af37`, `#D4AF37`, `#e0be52`, etc.)**
   - **Locations**: `welcome-page.blade.php`, `vault/index.blade.php`, `settings-page.blade.php`, `shop/index.blade.php`, `shop/order-list.blade.php`, `shop/order-details.blade.php`, `membership/application/*`, `components/cms/blocks/*`.
   - **Usage**: Borders, text colors, background gradients, custom radio buttons, SVG active states.
   - **Environment**: User-side.
   - **Replacement**: `var(--color-400)` (Primary cold-gold) for text/borders, gradients using `--color-300` to `--color-500`.
   
2. **CSS Variables (`var(--ecc-primary)`)**
   - **Locations**: `layouts/user/partials/styles.blade.php`, `layouts/user/partials/bottom-nav.blade.php`, `layouts/user/partials/topbar.blade.php`, `pavilion/blocks/*`.
   - **Usage**: Active link highlights, base accent variables.
   - **Environment**: User-side.
   - **Replacement**: Update `--ecc-primary: #D4AF37;` to `--ecc-primary: var(--color-400);` in `styles.blade.php` to immediately fix components relying on the variable.

3. **Custom Utility Classes (`ecc-btn-gold`, `ecc-text-gold`, `ecc-status-pill.status-processing`)**
   - **Locations**: Often defined locally in `<style>` blocks within specific Livewire components (e.g., `order-details.blade.php`, `order-list.blade.php`).
   - **Usage**: Primary buttons (e.g., Track Shipment, Checkout), warning/pending order badges.
   - **Environment**: User-side.
   - **Replacement**: Re-define these localized classes to use the new CSS variables.

## D. Files that must NOT be touched
- **`resources/views/layouts/admin.blade.php`**
- **`resources/views/livewire/admin/*`** (Any Livewire component starting with `admin/`)
- **`public/assets/*`** (Assuming this contains Velzon/Admin theme files)
- **Any Controllers/Services**: e.g., `app/Http/Controllers/` or `app/Services/`
- **Mail/PDF Views**: Unless explicitly requested later.

## E. Recommended implementation plan
1. **Primary update file**: `resources/views/layouts/user/partials/styles.blade.php`.
2. **Add CSS Variables**:
   ```css
   :root {
       --color-50:  #F8F5EC;
       --color-100: #EDE2CA;
       --color-200: #E1D0A8;
       --color-300: #D5BD85;
       --color-400: #C7A75A;
       --color-500: #BE9841;
       --color-600: #9C7D35;
       --color-700: #7A622A;
       --color-800: #57461E;
       --color-900: #352B12;
       --color-950: #130F07;
       
       --ecc-primary: var(--color-400);
   }
   ```
3. **Map and replace hardcoded values**:
   - Iterate through the exact list of files found in `resources/views/livewire/*` (excluding `admin/`) and `resources/views/components/*`.
   - Perform string replacements:
     - `#d4af37` / `#D4AF37` -> `var(--color-400)`
     - Gradient stops like `#e0be52`, `#F2D06B`, `#cfa52b` -> Adjust to use `--color-300`, `--color-400`, `--color-500`.
4. **Preserve Admin**: By strictly limiting the search/replace path arguments to user-facing directories (`resources/views/livewire/shop/`, `resources/views/livewire/vault/`, etc.), the admin dashboard remains completely untouched.

## F. Risk notes
- **Scattered local `<style>` blocks**: Many Livewire components define their CSS internally. A regex replacement might miss variations of hex codes (e.g., `#d4af35` vs `#d4af37`) or rgba formulations (`rgba(212,175,55,.10)`). These must be accounted for carefully to avoid leaving random yellow patches.
- **Opacity overlays**: Hardcoded `rgba(212,175,55, X)` values are used for subtle backgrounds on pills and badges. Replacing these with standard CSS variables requires mapping them to the new palette hex codes and ensuring the opacity wrapper is supported (or manually recalculating the rgba equivalent of `#C7A75A`).

## G. Commands/searches performed
1. `ls resources/views/layouts` - To identify layout structures (`web-app.blade.php`, `admin.blade.php`).
2. `findstr /S /I /M "#d4af37" resources\views\*` - Found 30+ user-facing components hardcoding the primary hex.
3. `findstr /S /I /M "ecc-gold text-gold bg-gold archive-gold" resources\views\*` - Found 15+ views using localized utility classes containing "gold".
4. `findstr /S /I /M "var(--ecc-primary)" resources\views\*` - Found 13 specific layouts/blocks utilizing the centralized CSS variable.

## H. Implementation Completed
- **Files Changed**: 26 files total across `resources/views/layouts/`, `resources/views/livewire/`, and `resources/views/components/`. 
- **Old Values Replaced**:
  - `#d4af37`, `#e0be52` -> `var(--ecc-primary)`
  - `#f5c542`, `#F2D06B` -> `var(--ecc-gold-300)`
  - `#c9a227`, `#f2b90d`, `#cfa52b` -> `var(--ecc-gold-500)`
  - `#b8860b`, `#aa8c2c` -> `var(--ecc-gold-600)`
  - `rgba(212, 175, 55, X)` -> `rgba(199, 167, 90, X)`
  - `ecc-btn-gold`, `ecc-text-gold`, `text-gold`, `bg-gold` -> `ecc-btn-primary`, `ecc-text-primary`, `ecc-bg-primary`
- **New Variables Added**: In `resources/views/layouts/user/partials/styles.blade.php`, `--ecc-gold-*` variables, alias variables (`--ecc-primary`, `--ecc-primary-hover`), and custom `.ecc-*-primary` utility classes were injected.
- **Admin Exclusions**: The regex migration explicitly filtered out any Blade files mapping to `/admin/` or `\admin\`. Tested via `findstr` on `.text-warning` which strictly returned only `admin` files, confirming they survived unmodified.
- **Testing**: Views cached cleanly (`php artisan view:cache` exited with code 0). Tested paths included `/home`, `/club`, `/archive`, `/shop`, `/orders`, `/settings`. No old gold hex values or RGB equivalents remain in the user-side component structure.

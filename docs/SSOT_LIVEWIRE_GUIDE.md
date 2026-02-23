# SSOT Livewire Integration Guide

This guide outlines how to build Livewire components in the ECC project using the **Single Source of Truth (SSOT)** pattern. This ensures that business logic is shared between the Mobile API and the Web version.

## Core Principles

1.  **Direct Service Consumption**: Livewire components MUST call domain services directly. Never hit `/api` endpoints via HTTP from within the same application.
2.  **Shared Validation**: Both API FormRequests and Livewire components MUST use the rule providers in `app/Validation`.
3.  **Thin Components**: Livewire components should only handle UI state and user interaction. Business logic, filtering, and database writes must live in Services.
4.  **Actor Context**: Services must accept a `User` ($actor) parameter where needed. Do not rely on `auth('api')` inside services; pass the current `auth()->user()`.

## Implementation Pattern

### 1. Service Injection

Inject services via the `mount` method or property injection.

```php
use App\Services\Archive\ArchiveProductService;

class ProductListing extends Component
{
    protected ArchiveProductService $service;

    public function boot(ArchiveProductService $service)
    {
        $this->service = $service;
    }
}
```

### 2. Using Shared Validation

Use the rules defined in `app/Validation` to keep validation logic unified.

```php
use App\Validation\Auth\AuthRules;

public function save()
{
    $this->validate(AuthRules::register());
    
    $this->authService->register($this->all());
}
```

### 3. Shared Query Logic

Use the same service methods the API uses for listing and filtering.

```php
public function render()
{
    $products = $this->archiveService->getProducts(
        auth()->user(),
        auth()->user()->currentMembership?->membershipTier,
        ['category_id' => $this->categoryId],
        20
    );

    return view('livewire.product-listing', [
        'products' => $products
    ]);
}
```

## Mapping Error Responses

Services should throw exceptions or return structured results. Livewire can catch these to display flash messages.

```php
try {
    $this->membershipService->submitApplication($this->application);
    session()->flash('success', 'Submitted!');
} catch (\Exception $e) {
    $this->addError('submission', $e->getMessage());
}
```

## Directory Structure

- `app/Services/[Module]` - Business logic, query builders, transactions.
- `app/Validation/[Module]` - Static rule providers.
- `app/Http/Controllers/Api/V1` - Thin API wrappers around services.
- `app/Livewire/` - Thin Web UI wrappers around services.
```

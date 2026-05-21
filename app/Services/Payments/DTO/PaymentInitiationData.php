<?php

namespace App\Services\Payments\DTO;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PaymentInitiationData
{
    public Model $payable;
    public float|string $amount;
    public string $currency;
    public string $purpose;
    public ?User $user;
    public ?string $gateway;
    public array $context;

    public function __construct(
        Model $payable,
        float|string $amount,
        string $purpose,
        ?User $user = null,
        ?string $gateway = null,
        array $context = []
    ) {
        $this->payable = $payable;
        $this->amount = $amount;
        $this->purpose = $purpose;
        $this->user = $user;
        $this->gateway = $gateway;
        $this->context = $context;
        $this->currency = $context['currency'] ?? $data['currency'] ?? config('payments.default_currency', 'INR');
    }

    /**
     * Create a DTO instance from an array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            payable: $data['payable'],
            amount: $data['amount'],
            purpose: $data['purpose'],
            user: $data['user'] ?? null,
            gateway: $data['gateway'] ?? null,
            context: $data['context'] ?? []
        );
    }
}

<?php

namespace App\Exceptions;

use App\Models\Customer;
use Exception;

class CreditLimitExceededException extends Exception
{
    protected ?Customer $customer;
    protected float $amount;
    protected float $limit;

    public function __construct(
        string $message,
        ?Customer $customer = null,
        float $amount = 0,
        float $limit = 0
    ) {
        parent::__construct($message);
        $this->customer = $customer;
        $this->amount = $amount;
        $this->limit = $limit;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getLimit(): float
    {
        return $this->limit;
    }
}

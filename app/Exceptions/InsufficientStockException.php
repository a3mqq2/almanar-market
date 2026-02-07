<?php

namespace App\Exceptions;

use App\Models\Product;
use Exception;

class InsufficientStockException extends Exception
{
    protected ?Product $product;
    protected float $requested;
    protected float $available;

    public function __construct(
        string $message,
        ?Product $product = null,
        float $requested = 0,
        float $available = 0
    ) {
        parent::__construct($message);
        $this->product = $product;
        $this->requested = $requested;
        $this->available = $available;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function getRequested(): float
    {
        return $this->requested;
    }

    public function getAvailable(): float
    {
        return $this->available;
    }
}

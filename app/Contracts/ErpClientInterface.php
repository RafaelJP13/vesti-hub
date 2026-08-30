<?php

namespace App\Contracts;

interface ErpClientInterface
{
    public function getProducts(): array;

    public function getVariations(): array;
}

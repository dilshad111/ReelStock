<?php

namespace App\Support\Contracts;

interface DataTransferObject
{
    /**
     * Convert the DTO to an array.
     */
    public function toArray(): array;
}

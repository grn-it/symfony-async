<?php

declare(strict_types=1);

namespace App\Dto\Microservice\Statistics;

class SalesTotalDto
{
    public function __construct(
        public ?int $users = null,
        public ?int $buyers = null,
        public ?int $orders = null,
        public ?int $products = null,
        public ?int $income = null
    )
    {
    }
}

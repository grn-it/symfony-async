<?php

declare(strict_types=1);

namespace App\Dto\Service\Statistics;

class SalesDto
{
    public array $user;
    public array $order;
    public array $product;
    
    public function __construct(?array $data)
    {
        $this->user['id'] = $data['id'] ?? null;
        $this->user['firstname'] = $data['firstname'] ?? null;
        $this->user['lastname'] = $data['lastname'] ?? null;
        $this->user['email'] = $data['email'] ?? null;
        $this->order['count'] = $data['orderCount'] ?? null;
        $this->order['sum'] = $data['orderSum'] ?? null;
        $this->product['count'] = $data['productCount'] ?? null;
    }
}

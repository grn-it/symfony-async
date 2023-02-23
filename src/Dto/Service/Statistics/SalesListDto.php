<?php

declare(strict_types=1);

namespace App\Dto\Service\Statistics;

class SalesListDto
{
    /** @var SalesDto[] $sales  */
    private array $sales = [];

    public function __construct(?array $data)
    {
        foreach ($data as $value) {
            $this->sales[] = new SalesDto($value);
        }
    }
    
    public function getSales(): array
    {
        return $this->sales;
    }
}

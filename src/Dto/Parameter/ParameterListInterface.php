<?php

declare(strict_types=1);

namespace App\Dto\Parameter;

interface ParameterListInterface
{
    public const PARAMETER_LIST_FILTER = 'filter';
    public const PARAMETER_LISTS = [self::PARAMETER_LIST_FILTER];
    
    public function getItems(): array;
    public function hasName(array $names): bool;
}

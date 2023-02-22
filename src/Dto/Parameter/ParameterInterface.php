<?php

declare(strict_types=1);

namespace App\Dto\Parameter;

interface ParameterInterface
{
    public const PARAMETER_SEARCH = 'search';
    public const PARAMETER_ORDER = 'order';
    public const PARAMETERS = [self::PARAMETER_SEARCH, self::PARAMETER_ORDER];

    public function getName(): string;
    public function getValue(): string|array;
    public function hasName(array $names): bool;
}

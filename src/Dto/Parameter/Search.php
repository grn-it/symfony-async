<?php

declare(strict_types=1);

namespace App\Dto\Parameter;

class Search extends AbstractParameter
{
    public const PARAMETER_NAME = ParameterInterface::PARAMETER_SEARCH;

    public static function getParameterName(): string
    {
        return self::PARAMETER_NAME;
    }
}

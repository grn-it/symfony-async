<?php

declare(strict_types=1);

namespace App\Dto\Parameter;

class Filter extends AbstractParameter
{
    public const PARAMETER_NAME = ParameterListInterface::PARAMETER_LIST_FILTER;

    public static function getParameterName(): string
    {
        return self::PARAMETER_NAME;
    }
}

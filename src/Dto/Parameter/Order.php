<?php

declare(strict_types=1);

namespace App\Dto\Parameter;

class Order extends AbstractParameter
{
    public const PARAMETER_NAME = ParameterInterface::PARAMETER_ORDER;

    public static function getParameterName(): string
    {
        return self::PARAMETER_NAME;
    }
}

<?php

declare(strict_types=1);

namespace App\Dto\Parameter;

use DomainException;
use Symfony\Component\HttpFoundation\Request;

class ParameterFactory
{
    public function __construct(private readonly ParameterBuilder $parameterBuilder)
    {
    }
    
    public function create(string $queryParameterName, Request $request): ParameterInterface|null
    {
        return match($queryParameterName) {
            ParameterInterface::PARAMETER_SEARCH => $this->parameterBuilder
                ->setRequest($request)
                ->setQueryParameterName($queryParameterName)
                ->setParameterClassName(Search::class)
                ->build(),
            
            ParameterInterface::PARAMETER_ORDER  => $this->parameterBuilder
                ->setRequest($request)
                ->setQueryParameterName($queryParameterName)
                ->setParameterClassName(Order::class)
                ->build(),
            
            default => throw new DomainException(
                sprintf('Unknown parameter name "%s".', $queryParameterName)
            )
        };
    }
    

}

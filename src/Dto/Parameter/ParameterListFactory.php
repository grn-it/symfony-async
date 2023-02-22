<?php

declare(strict_types=1);

namespace App\Dto\Parameter;

use Symfony\Component\HttpFoundation\Request;

class ParameterListFactory
{
    public function __construct(private readonly ParameterListBuilder $parameterListBuilder)
    {
    }

    public function create(string $queryParameterName, Request $request): ParameterListInterface|null
    {
        return match($queryParameterName) {
            ParameterListInterface::PARAMETER_LIST_FILTER => $this->parameterListBuilder
                ->setRequest($request)
                ->setQueryParameterName($queryParameterName)
                ->setParameterListClassName(FilterList::class)
                ->setParameterClassName(Filter::class)
                ->build(),
            
            default => throw new DomainException(
                sprintf('Unknown parameter name "%s".', $queryParameterName)
            )
        };
    }


}

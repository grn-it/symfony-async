<?php

declare(strict_types=1);

namespace App\Dto\Parameter;

use Symfony\Component\HttpFoundation\Request;

class ParameterCreator
{
    public function __construct(
        private readonly ParameterFactory $parameterFactory,
        private readonly ParameterListFactory $parameterListFactory
    )
    {
    }
    
    public function createSearch(Request $request): ParameterInterface|null
    {
        return $this->parameterFactory->create(ParameterInterface::PARAMETER_SEARCH, $request);
    }

    public function createOrder(Request $request): ParameterInterface|null
    {
        return $this->parameterFactory->create(ParameterInterface::PARAMETER_ORDER, $request);
    }
    
    public function createFilterList(Request $request): ParameterListInterface|null
    {
        return $this->parameterListFactory->create(ParameterListInterface::PARAMETER_LIST_FILTER, $request);
    }
}

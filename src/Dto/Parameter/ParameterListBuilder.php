<?php

declare(strict_types=1);

namespace App\Dto\Parameter;

use LogicException;

class ParameterListBuilder extends AbstractParameterBuilder
{
    private string $parameterListClassName;

    public function setParameterListClassName(string $parameterListClassName): self
    {
        $this->parameterListClassName = $parameterListClassName;
        return $this;
    }
    
    public function validateParameterListClassName(): void
    {
        if (!isset($this->parameterListClassName)) {
            throw new LogicException('Property "parameterListClassName" must be defined.');
        }
    }
    
    public function build(): ParameterListInterface|null
    {
        $this->validateQueryParameterName();
        $queryParameter = $this->request->query->all($this->queryParameterName);
        if (empty($queryParameter)) {
            return null;
        }

        $this->validateParameterListClassName();
        $this->checkClassExists($this->parameterListClassName);

        $this->validateParameterClassName();
        $this->checkClassExists($this->parameterClassName);

        /** @var ParameterListInterface $parameterList */
        $parameterList = new $this->parameterListClassName();

        foreach ($queryParameter as $name => $value) {
            $parameter = $this->createParameterByClassName($this->parameterClassName, $name, $value);
            $parameterList->addItem($parameter);
        }

        return $parameterList;
    }
}

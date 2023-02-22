<?php

declare(strict_types=1);

namespace App\Dto\Parameter;

class ParameterBuilder extends AbstractParameterBuilder
{
    public function build(): ParameterInterface|null
    {
        $this->validateQueryParameterName();
        $queryParameter = $this->request->query->all($this->queryParameterName);
        if (empty($queryParameter)) {
            return null;
        }

        $name = key($queryParameter);
        $value = $queryParameter[key($queryParameter)];

        $this->validateParameterClassName();        
        $this->checkClassExists($this->parameterClassName);
        
        return $this->createParameterByClassName($this->parameterClassName, $name, $value);
    }
}

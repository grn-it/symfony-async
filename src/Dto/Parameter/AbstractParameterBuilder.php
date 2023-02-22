<?php

declare(strict_types=1);

namespace App\Dto\Parameter;

use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AbstractParameterBuilder
{
    protected Request $request;
    protected string $queryParameterName;
    protected string $parameterClassName;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getMainRequest();
    }

    public function setRequest($request): self
    {
        $this->request = $request;
        return $this;
    }
    
    public function setQueryParameterName(string $queryParameterName): self
    {
        $this->queryParameterName = $queryParameterName;
        return $this;
    }

    public function validateQueryParameterName(): void
    {
        if (!isset($this->queryParameterName)) {
            throw new LogicException('Property "queryParameterName" must be defined.');
        }
    }
    
    public function setParameterClassName(string $parameterClassName): self
    {
        $this->parameterClassName = $parameterClassName;
        return $this;
    }
    
    public function validateParameterClassName(): void
    {
        if (!isset($this->parameterClassName)) {
            throw new LogicException('Property "parameterClassName" must be defined.');
        }
    }
    
    protected function createParameterByClassName(string $parameterClassName, string|int $name, string|array $value): ParameterInterface
    {
        $this->checkClassExists($parameterClassName);

        if (empty($name)) {
            throw new BadRequestHttpException(
                sprintf('The key of the "%s" parameter cannot be not be empty.', $parameterClassName::getParameterName())
            );
        }

        if (!is_string($name)) {
            throw new BadRequestHttpException(
                sprintf('The key of the "%s" parameter cannot be be string.', $parameterClassName::getParameterName())
            );
        }

        if (empty($value)) {
            throw new BadRequestHttpException(
                sprintf(
                    'Parameter "%s" with key "%s" cannot be empty.',
                    $parameterClassName::getParameterName(),
                    $name
                )
            );
        }

        return new $parameterClassName($name, $value);
    }

    protected function checkClassExists(string $className): void
    {
        if (!class_exists($className)) {
            throw new RuntimeException(
                sprintf('The class "%s" does not exist.', $className)
            );
        }
    }
}

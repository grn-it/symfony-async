<?php

declare(strict_types=1);

namespace App\Dto\Parameter;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

abstract class AbstractParameter implements ParameterInterface
{
    public function __construct(
        private readonly string $name,
        private readonly string|array $value
    )
    {
    }

    abstract public static function getParameterName(): string;
    
    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string|array
    {
        return $this->value;
    }
    
    public function hasName(array $names): bool
    {
        if (!in_array($this->getName(), $names, true)) {
            throw new BadRequestHttpException(
                sprintf(
                    'Parameter "%s" has invalid key "%s".',
                    $this->getParameterName(),
                    $this->getName()
                )
            );
        }
        
        return true;
    }
    
    public function hasKey(array $keys): bool
    {
        foreach (array_keys($this->value) as $key) {
            if (!in_array($key, $keys, true)) {
                throw new BadRequestHttpException(
                    sprintf(
                        'Parameter "%s" has invalid key "%s".',
                        $this->getParameterName(),
                        $key
                    )
                );
            }
        }
        
        return true;
    }
    
    public function hasValue(array $values): bool
    {
        if (is_array($this->value)) {
            $valueList = $this->value;
        } else {
            $valueList[] = $this->value;
        }
        
        foreach ($valueList as $value) {
            if (!in_array($value, $values, true)) {
                throw new BadRequestHttpException(
                    sprintf(
                        'Parameter "%s" has invalid value "%s".',
                        $this->getParameterName(),
                        $this->getValue()
                    )
                );
            }
        }
        
        return true;
    }
    
    public function isValueString(): bool
    {
        return is_string($this->value);
    }
    
    public function isValueArray(): bool
    {
        return is_array($this->value);
    }
}

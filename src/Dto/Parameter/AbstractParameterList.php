<?php

declare(strict_types=1);

namespace App\Dto\Parameter;

class AbstractParameterList implements ParameterListInterface
{
    /** @var ParameterInterface[] $items */
    protected array $items;

    public function getItems(): array
    {
        return $this->items;
    }
    
    public function addItem(ParameterInterface $item): self
    {
        $this->items[] = $item;
        return $this;
    }
    
    public function hasName(array $names): bool
    {
        foreach ($this->items as $item) {
            $item->hasName($names);
        }
        
        return true;
    }
}

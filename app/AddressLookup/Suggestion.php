<?php

namespace App\AddressLookup;

readonly class Suggestion
{
    public function __construct(
        public string $id,
        public string $label,
    ) {}

    /**
     * @return array{id: string, label: string}
     */
    public function toArray(): array
    {
        return ['id' => $this->id, 'label' => $this->label];
    }
}

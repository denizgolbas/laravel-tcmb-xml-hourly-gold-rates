<?php

namespace DenizTezc\TcmbGold\Services;

use Illuminate\Support\Collection;

class GoldBuilder
{
    protected $service;

    public function __construct(GoldService $service)
    {
        $this->service = $service;
    }

    public function get(): Collection
    {
        return $this->service->all();
    }
    
    // Add more fluent methods here if needed, e.g. ->filterByCode('XAU')
}

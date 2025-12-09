<?php

namespace DenizTezc\TcmbGold\Facades;

use Illuminate\Support\Facades\Facade;

class TcmbGold extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'tcmb-gold';
    }
}

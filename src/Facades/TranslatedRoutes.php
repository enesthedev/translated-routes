<?php

namespace Enes\TranslatedRoutes\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Enes\TranslatedRoutes\TranslatedRoutes
 */
class TranslatedRoutes extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Enes\TranslatedRoutes\TranslatedRoutes::class;
    }
}

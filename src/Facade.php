<?php
namespace ZanySoft\LaravelAssets;

use Illuminate\Support\Facades\Facade as LFacade;

class Facade extends LFacade
{
    /**
     * Name of the binding in the IoC container
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'zanysoft.assets';
    }
}

<?php


namespace Renesis\ApiWrapper\ServiceProvider;


use Illuminate\Support\Facades\Facade;

class RenesisApiWrapper extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'renesis-api-wrapper';
    }
}

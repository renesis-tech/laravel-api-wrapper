<?php


namespace Renesis\ApiWrapper\ServiceProvider;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Renesis\ApiWrapper\Wrapper\ApiWrapper;

class ServiceProvider extends BaseServiceProvider
{

    public function boot()
    {
        $this->publishes([
            __DIR__.'../../config/renesis-api-wrapper.php' => config_path('renesis-api-wrapper.php'),
        ]);
    }

    public function register()
    {
        $this->app->singleton('renesis-api-wrapper',function ($app){
            return new ApiWrapper();
        });

        $loader = AliasLoader::getInstance();
        $loader->alias('RenesisApiWrapper', 'Renesis\ApiWrapper\ServiceProvider\RenesisApiWrapper');
    }
}

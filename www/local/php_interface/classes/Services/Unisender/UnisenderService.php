<?php
namespace Yolva\Services\Unisender;

use Unisender\ApiWrapper\UnisenderApi;
use Yolva\Services\Unisender\Decorator\UnisenderLoggingDecorator;

abstract class UnisenderService
{
    /**
     * @var API-ключ Unisender
     */
    protected UnisenderLoggingDecorator $uniApi;

    function __construct()
    {
        $this->uniApi = new UnisenderLoggingDecorator(new UnisenderApi(getenv('APIKEY')));
    }

    /**
     * Абстрактные методы.
     */

    abstract public function create($name);
    abstract public function get();

}
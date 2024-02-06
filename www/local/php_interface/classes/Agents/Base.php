<?php

namespace Yolva\Agents;

/**
 * Рекодмендованный класс к наследованию агентами
 */
abstract class Base
{
    /**
     * @param string $method
     * @param string $params
     * @return string
     */
    protected static function getFunctionString(string $method, string $params = ''): string
    {
        return "$method($params);";
    }
}
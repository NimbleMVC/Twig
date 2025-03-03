<?php

use Twig\Markup;

/**
 * @param string $controller
 * @param string $method
 * @param string ...$params
 * @return false|Markup
 * @throws Throwable
 */
function action(string $controller, string $method, string ...$params): false|Markup
{
    $route = new \NimblePHP\Framework\Route(new \NimblePHP\Framework\Request());
    $route->setController($controller);
    $route->setMethod($method);
    $route->setParams($params);
    $kernel = new \NimblePHP\Framework\Kernel($route);
    ob_start();
    $kernel->handle();

    return new Markup(ob_get_clean(), 'UTF-8');
}
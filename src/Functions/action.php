<?php

use Twig\Markup;

function action(string $controller, string $method, string ...$params): false|Markup
{
    $route = new \Nimblephp\framework\Route(new \Nimblephp\framework\Request());
    $route->setController($controller);
    $route->setMethod($method);
    $route->setParams($params);
    $kernel = new \Nimblephp\framework\Kernel($route);
    ob_start();
    $kernel->handle();

    return new Markup(ob_get_clean(), 'UTF-8');
}
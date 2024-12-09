<?php

function action(string $controller, string $method, string ...$params): false|string
{
    $route = new \Nimblephp\framework\Route(new \Nimblephp\framework\Request());
    $route->setController($controller);
    $route->setMethod($method);
    $route->setParams($params);
    $kernel = new \Nimblephp\framework\Kernel($route);
    ob_start();
    $kernel->handle();

    return ob_get_clean();
}
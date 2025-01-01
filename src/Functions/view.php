<?php

use Nimblephp\debugbar\Debugbar;
use Nimblephp\twig\Twig;
use Nimblephp\twig\View;

function view(string $controller, string $action, array $data): false|string
{
    ob_start();
    $view = new View(new Twig());
    Debugbar::addMessage(['name' => $controller, 'action' => $action, 'data' => $data, 'global_variables' => Twig::$globalVariables], 'Load view');
    $view->render($controller . '/' . $action, $data);
    return ob_get_clean();
}

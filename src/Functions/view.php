<?php

use NimblePHP\debugbar\Debugbar;
use NimblePHP\twig\Twig;
use NimblePHP\twig\View;
use Twig\Markup;

function view(string $controller, string $action, array $data = []): false|Markup
{
    ob_start();
    $view = new View(new Twig());

    if ($_ENV['DEBUG'] && \NimblePHP\framework\Kernel::$activeDebugbar) {
        Debugbar::addMessage(['name' => $controller, 'action' => $action, 'data' => $data, 'global_variables' => Twig::$globalVariables], 'Load view');
    }

    $view->render($controller . '/' . $action, $data);

    return new Markup(ob_get_clean(), 'UTF-8');
}

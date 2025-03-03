<?php

use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Exception\NotFoundException;
use NimblePHP\Twig\Twig;
use NimblePHP\Twig\View;
use Twig\Markup;

/**
 * @param string $controller
 * @param string $action
 * @param array $data
 * @return false|Markup
 * @throws NimbleException
 * @throws NotFoundException
 */
function view(string $controller, string $action, array $data = []): false|Markup
{
    ob_start();
    $view = new View(new Twig());

    $view->render($controller . '/' . $action, $data);

    return new Markup(ob_get_clean(), 'UTF-8');
}

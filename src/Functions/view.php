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
    $view = new View(new Twig());
    $content = $view->render($controller . '/' . $action, $data, true);

    return new Markup($content ?: '', 'UTF-8');
}

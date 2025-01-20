<?php

use Twig\Markup;

/**
 * Dump data
 * @param mixed ...$data
 * @return Markup
 */
function debug(mixed ...$data): Markup
{
    $output = '<pre>' . var_export($data, true) . '</pre>';

    return new Markup($output, 'UTF-8');
}
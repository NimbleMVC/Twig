<?php

use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Kernel;
use Random\RandomException;
use Twig\Markup;

/**
 * JS loader
 * @param array $data
 * @param string|null $jsPath
 * @return Markup
 * @throws JsonException
 * @throws NimbleException
 * @throws RandomException
 */
function js(array $data = [], ?string $jsPath = null): Markup
{
    $templateStack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $controller = null;
    $action = null;

    if (!is_null($jsPath)) {
        $jsPath = explode('/', $jsPath);
        $controller = trim(strtolower($jsPath[0]));
        $action = trim($jsPath[1]);
    } else {
        foreach ($templateStack as $trace) {
            if (isset($trace['class']) && str_contains($trace['class'], 'App\\Controller\\')) {
                $controller = str_replace('App\\Controller\\', '', $trace['class']);
                $action = $trace['function'];

                break;
            }
        }
    }

    if (is_null($controller) || is_null($action)) {
        throw new NimbleException('Failed load js file');
    }

    $jsPath = Kernel::$projectPath . '/App/View/' . $controller . '/' . $action . '.js';
    $jsPath = str_replace('\\', '/', $jsPath);

    if (!file_exists($jsPath)) {
        throw new NimbleException('View js file not found ' . $jsPath);
    }

    $jsonData = json_encode($data, JSON_THROW_ON_ERROR);
    $random = md5(base64_encode(random_bytes(18)));

    $output = '<script id="script_' . $random . '">'
        . '$(document).ready(function() {'
        . file_get_contents($jsPath) . '($("#script_' . $random . '").parent(), ' . $jsonData . ')'
        . '});'
        . '</script>';

    return new Markup($output, 'UTF-8');
}
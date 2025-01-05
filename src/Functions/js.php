<?php

use Nimblephp\framework\Exception\NimbleException;

/**
 * JS loader
 * @param array $data
 * @return string
 * @throws JsonException
 * @throws NimbleException
 */
function js(array $data = []): string
{
    $templateStack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $controller = null;
    $action = null;

    foreach ($templateStack as $trace) {
        if (isset($trace['class']) && str_contains($trace['class'], 'src\\Controller\\')) {
            $controller = str_replace('src\\Controller\\', '', $trace['class']);
            $action = $trace['function'];

            break;
        }
    }

    if (is_null($controller) || is_null($action)) {
        throw new NimbleException('Failed load js file');
    }

    $jsPath = \Nimblephp\framework\Kernel::$projectPath . '/src/View/' . $controller . '/' . $action . '.js';

    \Nimblephp\debugbar\Debugbar::addMessage(['controller' => $controller, 'action' => $action, 'path' => $jsPath], 'Load view js');

    if (!file_exists($jsPath)) {
        throw new NimbleException('View js file not found');
    }

    $jsonData = json_encode($data, JSON_THROW_ON_ERROR);

    return '<script>'
        . file_get_contents($jsPath) . '($(document.currentScript).parent(), ' . $jsonData . ')'
        . '</script>';
}
<?php

use Random\RandomException;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Markup;

/**
 * JS loader
 * @param Environment $environment
 * @param array $data
 * @param string|null $jsPath
 * @return Markup
 * @throws JsonException
 * @throws LoaderError
 * @throws RandomException
 */
function js(Environment $environment, array $data = [], ?string $jsPath = null): Markup
{
    try {
        $jsPath = $environment->getLoader()->getSourceContext($jsPath)->getPath();
        $jsPath = str_replace('.twig', '.js', $jsPath);
    } catch (\Throwable) {
        if (!is_null($jsPath) && !str_ends_with($jsPath, '.js')) {
            $jsPath .= '.js';
        } else {
            $templateStack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

            foreach ($templateStack as $trace) {
                if (isset($trace['class']) && str_contains($trace['class'], 'App\\Controller\\')) {
                    $jsPath = str_replace('App\\Controller\\', '', $trace['class']) . '/' . $trace['function'] . '.js';
                    $jsPath = str_replace('\\', '/', $jsPath);

                    break;
                }
            }
        }
    }

    $existsFile = file_exists($jsPath);

    if (!$existsFile) {
        foreach (array_unique($environment->getLoader()->getPaths()) as $path) {
            if (file_exists($path . '/' . $jsPath)) {
                $existsFile = true;
                $jsPath = $path . '/' . $jsPath;
            }
        }
    }

    if (!$existsFile) {
        throw new LoaderError('View js file not found ' . $jsPath);
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
<?php

namespace NimblePHP\Twig\Services;

use NimblePHP\Framework\Config;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Module\ModuleRegister;
use NimblePHP\Twig\Event\AfterTwigConstructEvent;
use NimblePHP\Twig\Event\AfterTwigEnvironmentConstructEvent;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

class EnvironmentService
{

    public Environment $twigEnvironment;

    public FilesystemLoader $filesystemLoader;

    private string $cachePath {
        get {
            return Kernel::$projectPath . '/storage/cache/twig';
        }
    }

    public function __construct()
    {
    }

    public function setFilesystemLoader(FilesystemLoader $filesystemLoader): void
    {
        $this->filesystemLoader = $filesystemLoader;
    }

    public function getFilesystemLoader(): FilesystemLoader
    {
        return $this->filesystemLoader;
    }

    public function getInstance(): Environment
    {
        if (!isset($this->twigEnvironment)) {
            $this->generateInstance();
        }

        return $this->twigEnvironment;
    }

    private function generateInstance()
    {
        $this->twigEnvironment = new Environment($this->filesystemLoader, [
            'cache' => $this->cachePath,
            'auto_reload' => true,
            'optimizations' => -1,
            'use_yield' => true
        ]);

        $this->loadFunctions(__DIR__ . '/../Functions');

        if (ModuleRegister::isset('nimblephp/form')) {
            $this->loadFunctions(__DIR__ . '/../Functions/forms');
        }

        $this->addCustomFilters();

        if (!Config::get('TWIG_CACHE', false)) {
            $this->twigEnvironment->setCache(false);
        }

        Kernel::dispatchEvent(new AfterTwigEnvironmentConstructEvent($this));
    }


    /**
     * Loading functions from directory
     * @param string $directory
     * @return void
     * @throws NimbleException
     */
    private function loadFunctions(string $directory): void
    {
        if (!is_dir($directory)) {
            throw new NimbleException("Directory $directory does not exist.");
        }

        foreach (glob($directory . '/*.php') as $filename) {
            require_once $filename;

            $functionName = basename($filename, '.php');

            if (function_exists($functionName)) {
                $options = [];
                $firstParam = (new \ReflectionFunction($functionName))->getParameters()[0] ?? null;

                if ($firstParam?->getType()?->getName() === Environment::class) {
                    $options['needs_environment'] = true;
                }

                $this->twigEnvironment->addFunction(new TwigFunction($functionName, $functionName, $options));
            }
        }
    }


    /**
     * Add custom Twig filters
     * @return void
     */
    private function addCustomFilters(): void
    {
        $this->twigEnvironment->addFilter(new TwigFilter('json_decode', function ($string, $assoc = true) {
            if (is_string($string)) {
                return json_decode($string, $assoc);
            }

            return $string;
        }));

        $this->twigEnvironment->addFilter(new TwigFilter('url_encode', function ($string) {
            return urlencode($string);
        }));

        $this->twigEnvironment->addFilter(new TwigFilter('number_format', function ($number, $decimals = 0, $decimal_separator = '.', $thousands_separator = ' ') {
            return number_format($number, $decimals, $decimal_separator, $thousands_separator);
        }));

        $this->twigEnvironment->addFilter(new TwigFilter('date', function ($date, $format = 'Y-m-d H:i:s') {
            if (is_string($date)) {
                $timestamp = strtotime($date);

                if ($timestamp !== false) {
                    return date($format, $timestamp);
                }
            } elseif ($date instanceof \DateTime) {
                return $date->format($format);
            } elseif (is_numeric($date)) {
                return date($format, $date);
            }

            return $date;
        }));

        $this->twigEnvironment->addFilter(new TwigFilter('split', function ($string, $delimiter = ' ', $limit = null) {
            if ($limit !== null) {
                return explode($delimiter, $string, $limit);
            }

            return explode($delimiter, $string);
        }));

        $this->twigEnvironment->addFilter(new TwigFilter('slice', function ($input, $start, $length = null) {
            if (is_array($input)) {
                return array_slice($input, $start, $length);
            } elseif (is_string($input)) {
                return substr($input, $start, $length);
            }

            return $input;
        }));

        $this->twigEnvironment->addFilter(new TwigFilter('filter', function ($array, $callback = null) {
            if (!is_array($array)) {
                return $array;
            }

            if ($callback === null) {
                return array_filter($array);
            }

            if (is_string($callback)) {
                return array_filter($array);
            }

            return array_filter($array, $callback);
        }));

        $this->twigEnvironment->addFilter(new TwigFilter('length', function ($input) {
            if (is_array($input) || $input instanceof \Countable) {
                return count($input);
            } elseif (is_string($input)) {
                return strlen($input);
            }

            return 0;
        }));

        $this->twigEnvironment->addFilter(new TwigFilter('capitalize', function ($string) {
            return ucfirst(strtolower($string));
        }));

        $this->twigEnvironment->addFilter(new TwigFilter('join', function ($array, $separator = '') {
            if (is_array($array)) {
                return implode($separator, $array);
            }

            return $array;
        }));
    }

}
<?php

namespace NimblePHP\Twig;

use Exception;
use Krzysztofzylka\File\File;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Kernel;
use Throwable;
use Twig\Environment;
use Twig\TwigFunction;
use Twig\TwigFilter;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;

/**
 * Twig helper instance
 */
class Twig
{

    /**
     * Global variables
     * @var array
     */
    public static array $globalVariables = [];

    /**
     * Global paths
     * @var array
     */
    public static array $globalPaths = [];

    /**
     * Global headers
     * @var array
     */
    public static array $headers = [];

    /**
     * Twig file system loader instance
     * @var FilesystemLoader
     */
    public FilesystemLoader $twigFileSystemLoader;

    /**
     * Twig environment instance
     * @var Environment
     */
    public Environment $twigEnvironment;

    /**
     * Constructor
     * @throws Exception
     */
    public function __construct()
    {
        $cachePath = Kernel::$projectPath . '/storage/cache/twig';

        self::$globalVariables['APP'] = [
            'here' => $_SERVER['REQUEST_URI'] ?? '',
            'headers' => implode("\n\r", self::$headers)
        ];

        $directoryPaths = [
            $cachePath,
            Kernel::$projectPath . '/templates'
        ];

        foreach ($directoryPaths as $directoryPath) {
            try {
                File::mkdir($directoryPath);
            } catch (Throwable $e) {
                throw new NimbleException('Failed to create ' . $directoryPath . ' directory: ' . $e->getMessage(), 500);
            }
        }

        File::mkdir(
            [
                $cachePath,
                Kernel::$projectPath . '/templates'
            ]
        );

        $this->twigFileSystemLoader = new FilesystemLoader();
        $this->addPath(Kernel::$projectPath . '/App/View');

        foreach (self::$globalPaths as $globalPath) {
            $this->addPath($globalPath);
        }

        $this->twigEnvironment = new Environment($this->twigFileSystemLoader, [
            'cache' => $cachePath,
            'auto_reload' => true,
            'optimizations' => -1,
            'use_yield' => true
        ]);

        $this->loadFunctions(__DIR__ . '/Functions');
        $this->addCustomFilters();

        if (!($_ENV['TWIG_CACHE'] ?? false)) {
            $this->twigEnvironment->setCache(false);
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

    /**
     * @param $path
     * @return void
     * @throws LoaderError
     */
    public function addPath($path): void
    {
        $this->twigFileSystemLoader->addPath($path);
    }

    /**
     * Render view
     * @param string $twigFilePath
     * @param array $variables
     * @return string
     * @throws NimbleException
     */
    public function render(string $twigFilePath, array $variables = []): string
    {
        try {
            $variables = array_merge($variables, self::$globalVariables);

            return $this->twigEnvironment->render($twigFilePath, $variables);
        } catch (Throwable $throwable) {
            throw new NimbleException($throwable->getMessage(), $throwable->getCode() ?? 500, $throwable);
        }
    }

    /**
     * Render error template
     * @param Throwable $throwable
     * @param array $variables
     * @return string
     * @throws LoaderError
     * @throws NimbleException
     */
    public function renderSimpleException(Throwable $throwable, array $variables = []): string
    {
        $this->addPath(__DIR__ . '/Templates');

        $errors = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Page Not Found',
            405 => 'Method Not Allowed',
            408 => 'Request Timeout',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout'
        ];
        $debug = $_ENV['DEBUG'] ?? false;
        $code = $throwable->getCode() > 0 ? $throwable->getCode() : 500;
        $message = $debug ? $throwable->getMessage() : ($errors[$code] ?? 'Internal Server Error');
        $simpleThrowable = '';
        $currentThrowable = $throwable;

        while (True) {
            $simpleThrowable .= $this->throwableToString($currentThrowable);

            if ($currentThrowable->getPrevious()) {
                $simpleThrowable .= PHP_EOL . PHP_EOL;
                $currentThrowable = $currentThrowable->getPrevious();
            } else {
                break;
            }
        }

        return $this->render(
            'error.twig',
            [
                'code' => $code,
                'message' => $message,
                'debug' => $debug,
                'simpleThrowable' => $simpleThrowable,
                'throwable' => var_export($throwable, true),
                'default_page' => '/' . $_ENV['DEFAULT_CONTROLLER'] . '/' . $_ENV['DEFAULT_METHOD'],
                ...$variables
            ]
        );
    }

    /**
     * Throwable to string method
     * @param Throwable $throwable
     * @return string
     */
    private function throwableToString(Throwable $throwable): string
    {
        $hiddenMessage = '';

        if (method_exists($throwable, 'getHiddenMessage')) {
            $hiddenMessage = '<b style="color: #AAF;">Hidden message: ' . $throwable->getHiddenMessage() . '</b>' . PHP_EOL;
        }

        return '<i style="color: #FAA">[Code: ' . $throwable->getCode() . '] ' . $throwable->getMessage() . '</i>' . PHP_EOL
            . $hiddenMessage
            . $throwable->getFile() . '(' . $throwable->getLine() . ')' . PHP_EOL
            . $throwable->getTraceAsString();
    }

    /**
     * Set global variable
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function addGlobal(string $name, mixed $value): void
    {
        self::$globalVariables[$name] = $value;
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
                $this->twigEnvironment->addFunction(new TwigFunction($functionName, $functionName));
            }
        }
    }

    /**
     * Add header
     * @param $header
     * @return void
     */
    public static function addHeader($header): void
    {
        self::$headers[] = $header;
    }

    /**
     * Add js header
     * @param string $url
     * @return void
     */
    public static function addJsHeader(string $url): void
    {
        self::addHeader('<script type="text/javascript" src="' . $url . '"></script>');
    }

    /**
     * Add css header
     * @param string $url
     * @return void
     */
    public static function addCssHeader(string $url): void
    {
        self::addHeader('<link rel="stylesheet" type="text/css" href="' . $url . '" />');
    }

}
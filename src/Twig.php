<?php

namespace Nimblephp\twig;

use DebugBar\Bridge\Twig\TraceableTwigEnvironment;
use Exception;
use Krzysztofzylka\File\File;
use Nimblephp\debugbar\Debugbar;
use Nimblephp\framework\Config;
use Nimblephp\framework\Exception\DatabaseException;
use Nimblephp\framework\Exception\HiddenException;
use Nimblephp\framework\Exception\NimbleException;
use Nimblephp\framework\Kernel;
use Throwable;
use Twig\Environment;
use Twig\TwigFunction;
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
            'here' => $_SERVER['REQUEST_URI']
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
        $this->addPath(Kernel::$projectPath . '/src/View');

        foreach (self::$globalPaths as $globalPath) {
            $this->addPath($globalPath);
        }

        $this->twigEnvironment = new Environment($this->twigFileSystemLoader, [
            'cache' => $cachePath,
            'auto_reload' => true,
            'optimizations' => -1
        ]);

        $this->loadFunctions(__DIR__ . '/Functions');

        if (!($_ENV['TWIG_CACHE'] ?? false)) {
            $this->twigEnvironment->setCache(false);
        }
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
        $debug = Config::get('DEBUG', false);
        $code = $throwable->getCode() > 0 ? $throwable->getCode() : 500;
        $message = $debug ? $throwable->getMessage() : $errors[$code];
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
                'default_page' => '/' . Config::get('DEFAULT_CONTROLLER') . '/' . Config::get('DEFAULT_METHOD'),
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

}
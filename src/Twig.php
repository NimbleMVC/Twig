<?php

namespace Nimblephp\twig;

use Exception;
use Krzysztofzylka\File\File;
use Nimblephp\framework\Config;
use Nimblephp\framework\Exception\NimbleException;
use Nimblephp\framework\Kernel;
use Throwable;
use Twig\Environment;
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

        File::mkdir(
            [
                $cachePath,
                Kernel::$projectPath . '/templates'
            ]
        );

        $this->twigFileSystemLoader = new FilesystemLoader();
        $this->addPath(Kernel::$projectPath . '/src/View');
        $this->twigEnvironment = new Environment($this->twigFileSystemLoader, [
            'cache' => $cachePath,
        ]);
        $this->twigEnvironment->setCache(Config::get('TWIG_CACHE', false));
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
     * @return string
     * @throws LoaderError
     * @throws NimbleException
     */
    public function renderSimpleException(Throwable $throwable): string
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

        return $this->render(
            'error.twig',
            [
                'code' => $code,
                'message' => $message,
                'debug' => $debug,
                'throwable' => var_export($throwable, true),
                'default_page' => '/' . Config::get('DEFAULT_CONTROLLER') . '/' . Config::get('DEFAULT_METHOD')
            ]
        );
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

}
<?php

namespace NimblePHP\Twig;

use Exception;
use Krzysztofzylka\File\File;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Config;
use NimblePHP\Framework\Module\ModuleRegister;
use NimblePHP\Twig\Event\AfterTwigConstructEvent;
use NimblePHP\Twig\Services\EnvironmentService;
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
     * Twig environment instance
     * @var Environment
     */
    public Environment $twigEnvironment;

    /**
     * Twig filesystem loader instance
     * @var FilesystemLoader
     */
    private FilesystemLoader $filesystemLoader;

    /**
     * Constructor
     * @throws Exception
     */
    public function __construct()
    {
        $this->refreshAppGlobalVariables();

        $directoryPaths = [];

        if (Config::get('TWIG_CREATE_TEMPLATE_DIRECTORY', false)) {
            $directoryPaths[] = Kernel::$projectPath . '/templates';
        }

        foreach ($directoryPaths as $directoryPath) {
            try {
                File::mkdir($directoryPath);
            } catch (Throwable $e) {
                throw new NimbleException('Failed to create ' . $directoryPath . ' directory: ' . $e->getMessage(), 500);
            }
        }

        /** @var FilesystemLoader $filesystemLoader */
        $filesystemLoader = Kernel::$serviceContainer->get('twig.filesystemloader');
        $this->filesystemLoader = $filesystemLoader;
        $this->addPath(Kernel::$projectPath . '/App/View');

        foreach (self::$globalPaths as $globalPath) {
            $this->addPath($globalPath);
        }

        /** @var EnvironmentService $environment */
        $environment = Kernel::$serviceContainer->get('twig.environment');
        $environment->setFilesystemLoader($filesystemLoader);
        $this->twigEnvironment = $environment->getInstance();
        Kernel::dispatchEvent(new AfterTwigConstructEvent($this));
    }

    /**
     * @param string $path
     * @param string $namespace
     * @return void
     * @throws LoaderError
     */
    public function addPath(string $path, string $namespace = FilesystemLoader::MAIN_NAMESPACE): void
    {
        if (in_array($path, $this->filesystemLoader->getPaths($namespace), true)) {
            return;
        }

        $this->filesystemLoader->addPath($path, $namespace);
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
            $this->refreshAppGlobalVariables();
            $variables = array_merge($variables, self::$globalVariables);

            return $this->twigEnvironment->render($twigFilePath, $variables);
        } catch (Throwable $throwable) {
            throw new NimbleException($throwable->getMessage(), $throwable->getCode() ?? 500, $throwable);
        }
    }

    private function refreshAppGlobalVariables(): void
    {
        self::$globalVariables['APP'] = [
            'here' => $_SERVER['REQUEST_URI'] ?? '',
            'headers' => implode("\n\r", self::$headers),
        ];
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
     * Add header
     * @param $header
     * @return void
     */
    public static function addHeader($header): void
    {
        if (in_array($header, self::$headers)) {
            return;
        }

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

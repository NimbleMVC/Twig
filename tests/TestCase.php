<?php

namespace NimblePHP\Twig\Tests;

use NimblePHP\Framework\Container\ServiceContainer;
use NimblePHP\Framework\Cookie;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Middleware\MiddlewareManager;
use NimblePHP\Framework\Module\ModuleRegister;
use NimblePHP\Framework\Request;
use NimblePHP\Framework\Routes\Route;
use NimblePHP\Framework\Translation\Translation;
use NimblePHP\Twig\Twig;
use NimblePHP\Twig\View;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use Twig\Loader\FilesystemLoader;

abstract class TestCase extends PHPUnitTestCase
{
    protected string $projectPath;

    private array $originalEnv;

    private array $originalServer;

    private array $originalCookie;

    private array $originalGet;

    private array $originalPost;

    private array $originalFiles;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalEnv = $_ENV;
        $this->originalServer = $_SERVER;
        $this->originalCookie = $_COOKIE;
        $this->originalGet = $_GET;
        $this->originalPost = $_POST;
        $this->originalFiles = $_FILES;

        $this->projectPath = sys_get_temp_dir() . '/nimblephp-twig-tests-' . bin2hex(random_bytes(8));
        mkdir($this->projectPath . '/App/View', 0777, true);
        mkdir($this->projectPath . '/App/Lang', 0777, true);
        mkdir($this->projectPath . '/App/Controller', 0777, true);
        mkdir($this->projectPath . '/public', 0777, true);
        mkdir($this->projectPath . '/storage/cache', 0777, true);
        mkdir($this->projectPath . '/templates', 0777, true);
        file_put_contents($this->projectPath . '/public/index.php', "<?php\n");

        $_ENV = [
            'DEFAULT_CONTROLLER' => 'home',
            'DEFAULT_METHOD' => 'index',
        ];
        $_SERVER = [
            'REQUEST_URI' => '/current-uri',
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_FILENAME' => $this->projectPath . '/public/index.php',
        ];
        $_COOKIE = [];
        $_GET = [];
        $_POST = [];
        $_FILES = [];

        $this->resetStaticState();
        $this->refreshKernelServices();
    }

    protected function tearDown(): void
    {
        $_ENV = $this->originalEnv;
        $_SERVER = $this->originalServer;
        $_COOKIE = $this->originalCookie;
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;
        $_FILES = $this->originalFiles;

        $this->resetStaticState();
        $this->removeDirectory($this->projectPath);

        parent::tearDown();
    }

    protected function writeFile(string $relativePath, string $contents): void
    {
        $fullPath = $this->projectPath . '/' . ltrim($relativePath, '/');
        $directory = dirname($fullPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($fullPath, $contents);
    }

    protected function refreshKernelServices(): void
    {
        Kernel::$projectPath = $this->projectPath;
        Kernel::$middlewareManager = new MiddlewareManager();
        Kernel::$eventDispatcher = null;
        Kernel::$serviceContainer = new ServiceContainer();
        Kernel::$serviceContainer->set('twig.filesystemloader', new FilesystemLoader());
        Kernel::$serviceContainer->set('kernel.request', new Request());
        Kernel::$serviceContainer->set('kernel.cookie', new Cookie());
    }

    private function resetStaticState(): void
    {
        Twig::$globalVariables = [];
        Twig::$globalPaths = [];
        Twig::$headers = [];
        View::$globalVariables = [];

        $this->resetStaticProperties(Translation::class, [
            'instance' => null,
            'translations' => [],
            'translationPaths' => [],
        ]);

        $this->resetStaticProperties(ModuleRegister::class, [
            'modules' => [],
        ]);

        $this->resetStaticProperties(Route::class, [
            'routes' => [],
            'dynamicRouteIndex' => null,
            'dynamicRouteIndexCount' => -1,
            'expectedParamCountCache' => [],
        ]);
    }

    private function resetStaticProperties(string $className, array $values): void
    {
        $reflection = new ReflectionClass($className);

        foreach ($values as $propertyName => $value) {
            $property = $reflection->getProperty($propertyName);
            $property->setValue(null, $value);
        }
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($path);
    }
}

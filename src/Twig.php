<?php

namespace Nimblephp\twig;

use Exception;
use Krzysztofzylka\File\File;
use Nimblephp\framework\Config;
use Nimblephp\framework\Exception\NimbleException;
use Nimblephp\framework\Kernel;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;

/**
 * Twig helper instance
 */
class Twig
{

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
        File::mkdir(Kernel::$projectPath . '/storage/cache/twig');

        $this->twigFileSystemLoader = new FilesystemLoader();
        $this->addPath(Kernel::$projectPath . '/src/View');
        $this->twigEnvironment = new Environment($this->twigFileSystemLoader, [
            'cache' => Kernel::$projectPath . '/storage/cache/twig',
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
            return $this->twigEnvironment->render($twigFilePath, $variables);
        } catch (\Throwable $throwable) {
            throw new NimbleException($throwable->getMessage(), $throwable->getCode() ?? 500, $throwable);
        }
    }

}
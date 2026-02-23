<?php

namespace NimblePHP\Twig;

use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Kernel;
use Twig\Error\LoaderError;

class Template
{

    /**
     * template name
     * @var string
     */
    public string $name;

    /**
     * Twig instance
     * @var Twig
     */
    public Twig $twig;

    /**
     * Constructor
     * @param $name
     * @throws LoaderError
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->twig = new Twig();

        $this->twig->addPath(Kernel::$projectPath . '/templates');
    }

    /**
     * Render template
     * @param array $variables
     * @return void
     * @throws NimbleException
     */
    public function render(array $variables = []): void
    {
        Kernel::$middlewareManager->runHookWithReference('processingViewData', $variables);

        $filePath = $this->name . '.twig';
        $view = new View($this->twig);

        echo $view->render(
            $filePath,
            $variables
        );
    }

}
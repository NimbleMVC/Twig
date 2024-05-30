<?php

namespace Nimblephp\twig;

use Nimblephp\framework\Exception\NimbleException;
use Nimblephp\framework\Kernel;
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
        echo $this->twig->render(
            $this->name . '.twig',
            $variables
        );
    }

}
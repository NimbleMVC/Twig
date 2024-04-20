<?php

namespace Nimblephp\twig;

use Nimblephp\framework\Exception\NimbleException;
use Nimblephp\framework\Exception\NotFoundException;
use Nimblephp\framework\Interfaces\ViewInterface;
use Nimblephp\framework\Kernel;
use Nimblephp\framework\Response;

/**
 * Twig view instance
 */
class View implements ViewInterface
{

    /**
     * View path
     * @var string
     */
    protected string $viewPath = '/src/View/';

    /**
     * View variable
     * @var array
     */
    protected array $variables = [];

    /**
     * Twig instance
     * @var Twig
     */
    protected Twig $twig;

    /**
     * Response code
     * @var int
     */
    protected int $responseCode = 200;

    /**
     * Constructor
     * @param Twig $twig
     */
    public function __construct(Twig $twig) {
        $this->twig = $twig;
    }

    /**
     * Set response code
     * @param int $responseCode
     * @return void
     */
    public function setResponseCode(int $responseCode): void
    {
        $this->responseCode = $responseCode;
    }

    /**
     * Render view
     * @param string $viewName
     * @param array $data
     * @return void
     * @throws NotFoundException
     * @throws NimbleException
     */
    public function render(string $viewName, array $data = []): void
    {
        $filePath = Kernel::$projectPath . $this->viewPath . $viewName . '.twig';

        if (!file_exists($filePath)) {
            throw new NotFoundException();
        }

        $response = new Response();
        $response->setContent($this->twig->render($viewName . '.twig', $data));
        $response->setStatusCode($this->responseCode);
        $response->send();
    }

}
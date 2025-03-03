<?php

namespace NimblePHP\twig;

use NimblePHP\framework\Exception\NimbleException;
use NimblePHP\framework\Exception\NotFoundException;
use NimblePHP\framework\Interfaces\ViewInterface;
use NimblePHP\framework\Kernel;
use NimblePHP\framework\Response;

/**
 * Twig view instance
 */
class View implements ViewInterface
{

    /**
     * View path
     * @var string
     */
    protected string $viewPath = '/App/View/';

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
            throw new NotFoundException('Not found view in path ' . $filePath);
        }

        $response = new Response();
        $response->setContent($this->twig->render($viewName . '.twig', $data));
        $response->setStatusCode($this->responseCode);
        $response->send();
    }

    /**
     * View rendered inside Twig
     * @return bool
     */
    public function inTwig(): bool
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        foreach ($backtrace as $trace) {
            if (isset($trace['class']) && str_starts_with($trace['class'], 'Twig')) {
                return true;
            }
        }

        return false;
    }

}
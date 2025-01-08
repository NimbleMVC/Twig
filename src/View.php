<?php

namespace Nimblephp\twig;

use Nimblephp\debugbar\Debugbar;
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
        if (Kernel::$activeDebugbar) {
            $debugbarUuid = Debugbar::uuid();
            Debugbar::startTime($debugbarUuid, 'Render twig view ' . $viewName);
        }

        $filePath = Kernel::$projectPath . $this->viewPath . $viewName . '.twig';

        if (!file_exists($filePath)) {
            throw new NotFoundException('Not found view in path ' . $filePath);
        }

        $response = new Response();
        $response->setContent($this->twig->render($viewName . '.twig', $data));
        $response->setStatusCode($this->responseCode);
        $response->send();

        if (Kernel::$activeDebugbar) {
            Debugbar::stopTime($debugbarUuid);
        }
    }

    /**
     * View rendered inside Twig
     * @return bool
     */
    public function inTwig(): bool
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        foreach ($backtrace as $trace) {
            if (isset($trace['class']) && strpos($trace['class'], 'Twig') === 0) {
                return true;
            }
        }

        return false;
    }

}
<?php

namespace NimblePHP\Twig;

use App\Controller;
use Krzysztofzylka\Generator\Generator;
use NimblePHP\Framework\Exception\HiddenException;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Exception\NotFoundException;
use NimblePHP\Framework\Interfaces\ViewInterface;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Response;
use NimblePHP\Framework\Request;
use Random\RandomException;

/**
 * Twig view instance
 */
class View
{

    /**
     * Global variables
     * @var array
     */
    public static array $globalVariables = [];

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
     * Request instance
     * @var Request|mixed
     */
    protected Request $request;

    /**
     * Set global variables
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public static function setGlobalVariable(string $name, mixed $value): void
    {
        self::$globalVariables[$name] = $value;
    }

    /**
     * Constructor
     * @param Twig $twig
     */
    public function __construct(Twig $twig) {
        $this->request = Kernel::$serviceContainer->get('kernel.request');
        $this->twig = $twig;
        $this->twig->addPath(Kernel::$projectPath . $this->viewPath);
        Kernel::$middlewareManager->runHookWithReference('moduleTwig_afterViewConstruct', $this);
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
     * @param string|null $viewPath
     * @param array $data
     * @param bool $return
     * @param Controller|null $controller
     * @return null|string
     * @throws HiddenException
     * @throws NimbleException
     */
    public function render(?string $viewPath = null, array $data = [], bool $return = false, ?Controller $controller = null): null|string
    {
        $hash = $this->getHash();
        list($viewName, $viewAction) = $this->extractViewNameAndAction($viewPath);
        $data['_VIEW'] = ['hash' => $hash, 'viewPath' => $viewPath, 'viewName' => $viewName, 'viewAction' => $viewAction, 'return' => $return, 'isAjax' => $this->request->isAjax()];
        $data['_GLOBAL'] = self::$globalVariables;
        Kernel::$middlewareManager->runHookWithReference('processingViewData', $data);
        $filePath = $viewName . '/' . $viewAction . '.twig';
        Kernel::$middlewareManager->runHook('beforeViewRender', [$data, $viewName, $filePath]);
        Twig::$headers = array_unique(Twig::$headers);
        ob_start();
        $response = new Response();
        $response->setContent($this->twig->render($filePath, $data));
        $response->setStatusCode($this->responseCode);
        $response->send();
        Kernel::$middlewareManager->runHook('afterViewRender', [$data, $viewName, $filePath]);

        if ($return) {
            return ob_get_clean();
        } else {
            echo ob_get_clean();
        }

        if ($this->request->isAjax() && !$this->inTwig()) {
            if (!is_null($controller)) {
                $controller->renderModalConfigHeader();
            }

            exit();
        }

        return null;
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

    /**
     * Generuje hash dla widoku
     * @return string
     * @throws HiddenException
     */
    private function getHash(): string
    {
        try {
            return Generator::uuid();
        } catch (RandomException $e) {
            throw new HiddenException($e->getMessage());
        }
    }

    /**
     * Wyodrębnia nazwę i akcję widoku z ścieżki
     * @param string $viewPath
     * @return string[]
     */
    private function extractViewNameAndAction(string $viewPath): array
    {
        $explode = explode('/', $viewPath, 2);
        $viewName = str_replace('\\', '/', $explode[0]);
        $viewAction = $explode[1];

        return [$viewName, $viewAction];
    }

    /**
     * @return Twig
     */
    private function getTwigInstance(): Twig
    {
        return $this->twig;
    }

}
<?php

namespace NimblePHP\Twig;

use NimblePHP\Framework\Config;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Module\Interfaces\ModuleInterface;
use Twig\Loader\FilesystemLoader;

class Module implements ModuleInterface
{

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Nimblephp Twig views';
    }

    /**
     * @return void
     * @throws NimbleException
     */
    public function register(): void
    {
        Kernel::$serviceContainer->set('twig.filesystemloader', new FilesystemLoader());

        if (Config::get('TWIG_ADD_SERVICE', true)) {
            $twig = new Twig();

            Kernel::$serviceContainer->set('view', new View($twig));
        }
    }

}
<?php

namespace NimblePHP\Twig;

use NimblePHP\Framework\Config;
use NimblePHP\Framework\Interfaces\CliCommandProviderInterface;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Module\Interfaces\ModuleInterface;
use NimblePHP\Migrations\Commands\MigrationCommand;

class Module implements ModuleInterface
{

    public function getName(): string
    {
        return 'Nimblephp Twig views';
    }

    public function register(): void
    {
        if (Config::get('TWIG_ADD_SERVICE', true)) {
            $twig = new Twig();

            Kernel::$serviceContainer->set('view', new View($twig));
        }
    }

}
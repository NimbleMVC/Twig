<?php

namespace NimblePHP\Twig\Event;

use NimblePHP\Framework\Event\AbstractEvent;
use NimblePHP\Twig\Services\EnvironmentService;
use NimblePHP\Twig\Twig;

class AfterTwigEnvironmentConstructEvent extends AbstractEvent
{

    public function __construct(public EnvironmentService $twig)
    {
    }

}

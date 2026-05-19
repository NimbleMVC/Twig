<?php

namespace NimblePHP\Twig\Event;

use NimblePHP\Framework\Event\AbstractEvent;
use NimblePHP\Twig\Twig;

class AfterTwigConstructEvent extends AbstractEvent
{

    public function __construct(public Twig $twig)
    {
    }

}

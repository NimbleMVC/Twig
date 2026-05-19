<?php

namespace NimblePHP\Twig\Event;

use NimblePHP\Framework\Event\AbstractEvent;
use NimblePHP\Twig\View;

class AfterViewConstructEvent extends AbstractEvent
{

    public function __construct(public View $view)
    {
    }

}

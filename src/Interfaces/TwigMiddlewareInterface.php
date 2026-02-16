<?php

namespace NimblePHP\Twig\Interfaces;

use NimblePHP\Twig\Twig;
use NimblePHP\Twig\View;

interface TwigMiddlewareInterface
{

    public function moduleTwig_afterViewConstruct(View &$view): void;

    public function moduleTwig_afterTwigConstruct(Twig &$twig): void;

}
<?php

namespace App\Controller;

use NimblePHP\Twig\Twig;

class index
{
    public static function index(Twig $twig): string
    {
        return $twig->render('index/index.twig');
    }
}

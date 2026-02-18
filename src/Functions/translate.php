<?php

use NimblePHP\Framework\Translation\Translation;

/**
 * @param string $key
 * @param array $params
 * @return string
 * @throws Throwable
 */
function translate(string $key, array $params = []): string
{
    return Translation::getInstance()->translate($key, $params);
}
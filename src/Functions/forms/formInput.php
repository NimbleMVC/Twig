<?php

use NimblePHP\Form\Enum\MethodEnum;
use Twig\Markup;

/**
 * @param string $controller
 * @param string $method
 * @param string ...$params
 * @return false|Markup
 * @throws Throwable
 */
function formInput(string $name, string $title = null, array $attributes = []): false|Markup
{
    $form = new class () {
        use \NimblePHP\Form\Traits\Field;
        public \NimblePHP\Form\Enum\MethodEnum $method = MethodEnum::POST;
        public \NimblePHP\Framework\Request $request;
        public array $validationErrors = [];
        
        public function __construct()
        {
            $this->validationErrors = \NimblePHP\Form\Form::$VALIDATIONS;
        }

        public function getData(): array
        {
            return $_POST;
        }

        public function __toString(): string
        {
            return $this->renderField($this->fields[0]);
        }
    };
    $form->request = new \NimblePHP\Framework\Request();
    $form->addInput($name, $title, $attributes);

    return new Markup($form, 'UTF-8');
}
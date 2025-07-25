<?php

use NimblePHP\Form\Enum\MethodEnum;
use Twig\Markup;

/**
 * @param string $name
 * @param string|null $title
 * @param array $attributes
 * @return false|Markup
 */
function formTextarea(string $name, ?string $title = null, array $attributes = []): false|Markup
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
    $form->addTextarea($name, $title, $attributes);

    return new Markup($form, 'UTF-8');
}
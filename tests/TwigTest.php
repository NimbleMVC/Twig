<?php

namespace NimblePHP\Twig\Tests;

use Exception;
use NimblePHP\Framework\Exception\HiddenException;
use NimblePHP\Framework\Routes\Route;
use NimblePHP\Twig\Twig;

class TwigTest extends TestCase
{
    public function testRenderMergesGlobalVariablesAndProvidesCustomFilters(): void
    {
        $this->writeFile(
            'App/View/example.twig',
            '{{ name }}|{{ APP.here }}|{{ custom }}|{{ "{\"ok\":true}"|json_decode.ok ? "yes" : "no" }}|{{ 1234.5|number_format(1, ",", " ") }}|{{ "foo bar baz"|split(" ", 2)|join("/") }}|{{ [1, 2, 3]|slice(1, 2)|join(",") }}|{{ [0, 1, "", 2]|filter|join(",") }}|{{ "HELLO WORLD"|capitalize }}'
        );

        $twig = new Twig();
        $twig->addGlobal('custom', 'from-global');

        $rendered = $twig->render('example.twig', [
            'name' => 'Twig',
        ]);

        $this->assertSame(
            'Twig|/current-uri|from-global|yes|1 234,5|foo/bar baz|2,3|1,2|Hello world',
            $rendered
        );
    }

    public function testHeaderHelpersAvoidDuplicates(): void
    {
        Twig::addHeader('<meta name="robots" content="noindex">');
        Twig::addHeader('<meta name="robots" content="noindex">');
        Twig::addJsHeader('/assets/app.js');
        Twig::addCssHeader('/assets/app.css');

        $this->assertSame([
            '<meta name="robots" content="noindex">',
            '<script type="text/javascript" src="/assets/app.js"></script>',
            '<link rel="stylesheet" type="text/css" href="/assets/app.css" />',
        ], Twig::$headers);
    }

    public function testRenderSimpleExceptionUsesFallbackMessageWhenDebugIsDisabled(): void
    {
        $_ENV['DEBUG'] = false;
        $twig = new Twig();

        $rendered = $twig->renderSimpleException(new Exception('Hidden details', 404));

        $this->assertStringContainsString('Opps!</span> Page Not Found.', $rendered);
        $this->assertStringNotContainsString('Hidden details', $rendered);
        $this->assertStringContainsString('href="/home/index"', $rendered);
    }

    public function testRenderSimpleExceptionShowsOriginalMessageWhenDebugIsEnabled(): void
    {
        $_ENV['DEBUG'] = true;
        $twig = new Twig();

        $rendered = $twig->renderSimpleException(new HiddenException('Visible in debug', 500));

        $this->assertStringContainsString('Visible in debug', $rendered);
        $this->assertStringContainsString('HiddenException', $rendered);
    }

    public function testJsFunctionSupportsImplicitAndExplicitTemplatePaths(): void
    {
        require_once __DIR__ . '/Fixtures/App/Controller/index.php';

        $this->writeFile(
            'App/View/index/index.twig',
            <<<TWIG
{{ js() }}
{{ js({'name': 'test', 'enabled': true}) }}
{{ js({}, 'index/index') }}
{{ js({}, _self) }}
TWIG
        );
        $this->writeFile(
            'App/View/index/index.js',
            'window.__twigJsCalls = window.__twigJsCalls || []; window.__twigJsCalls.push'
        );

        $rendered = \App\Controller\index::index(new Twig());

        $this->assertSame(4, substr_count($rendered, '<script id="script_'));
        $this->assertSame(4, substr_count($rendered, 'window.__twigJsCalls = window.__twigJsCalls || []; window.__twigJsCalls.push'));
        $this->assertSame(3, substr_count($rendered, '.parent(), [])'));
        $this->assertSame(1, substr_count($rendered, '.parent(), {"name":"test","enabled":true})'));
    }

    public function testActionFunctionRendersNestedViewWithJsVariants(): void
    {
        $this->writeFile(
            'App/Controller/nested.php',
            <<<'PHP'
<?php

namespace App\Controller;

use NimblePHP\Framework\Abstracts\AbstractController;
use NimblePHP\Twig\Twig;
use NimblePHP\Twig\View;

class nested extends AbstractController
{
    public function index(): void
    {
        (new View(new Twig()))->render('nested/index');
    }
}
PHP
        );
        $this->writeFile('App/View/outer/index.twig', "{{ action('nested', 'index') }}");
        $this->writeFile(
            'App/View/nested/index.twig',
            <<<TWIG
nested-start
{{ js() }}
{{ js({'source': 'action'}) }}
{{ js({}, 'nested/index') }}
{{ js({}, _self) }}
nested-end
TWIG
        );
        $this->writeFile(
            'App/View/nested/index.js',
            'window.__actionJsCalls = window.__actionJsCalls || []; window.__actionJsCalls.push'
        );
        Route::addRoute('/nested/index', 'nested', 'index');

        $rendered = (new Twig())->render('outer/index.twig');
        restore_error_handler();

        $this->assertStringContainsString('nested-start', $rendered);
        $this->assertStringContainsString('nested-end', $rendered);
        $this->assertSame(4, substr_count($rendered, '<script id="script_'));
        $this->assertSame(4, substr_count($rendered, 'window.__actionJsCalls = window.__actionJsCalls || []; window.__actionJsCalls.push'));
        $this->assertSame(3, substr_count($rendered, '.parent(), [])'));
        $this->assertSame(1, substr_count($rendered, '.parent(), {"source":"action"})'));
    }
}

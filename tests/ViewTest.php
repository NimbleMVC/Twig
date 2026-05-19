<?php

namespace NimblePHP\Twig\Tests;

use NimblePHP\Twig\Twig;
use NimblePHP\Twig\View;

class ViewTest extends TestCase
{
    public function testRenderBuildsViewMetadataAndReturnsRenderedResponse(): void
    {
        Twig::$globalVariables['appName'] = 'Twig package';
        View::$globalVariables['featureFlag'] = 'enabled';

        $twig = $this->getMockBuilder(Twig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addPath', 'render'])
            ->getMock();

        $twig->expects($this->once())
            ->method('addPath')
            ->with($this->projectPath . '/App/View/');

        $twig->expects($this->once())
            ->method('render')
            ->with(
                'Dashboard/index.twig',
                $this->callback(function (array $data): bool {
                    $this->assertSame('bar', $data['foo']);
                    $this->assertSame('Dashboard/index', $data['_VIEW']['viewPath']);
                    $this->assertSame('Dashboard', $data['_VIEW']['viewName']);
                    $this->assertSame('index', $data['_VIEW']['viewAction']);
                    $this->assertTrue($data['_VIEW']['return']);
                    $this->assertFalse($data['_VIEW']['isAjax']);
                    $this->assertSame('en', $data['_VIEW']['lang']);
                    $this->assertNotEmpty($data['_VIEW']['hash']);
                    $this->assertSame([
                        'appName' => 'Twig package',
                        'featureFlag' => 'enabled',
                    ], $data['_GLOBAL']);

                    return true;
                })
            )
            ->willReturn('rendered body');

        $view = new View($twig);
        $rendered = $view->render('Dashboard/index', ['foo' => 'bar'], true);

        $this->assertSame('rendered body', $rendered);
    }

    public function testRenderUsesExplicitTwigTemplatePathWithoutAppendingExtension(): void
    {
        $twig = $this->getMockBuilder(Twig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addPath', 'render'])
            ->getMock();

        $twig->expects($this->once())
            ->method('addPath')
            ->with($this->projectPath . '/App/View/');

        $twig->expects($this->once())
            ->method('render')
            ->with(
                'standalone.twig',
                $this->callback(function (array $data): bool {
                    $this->assertSame('standalone.twig', $data['_VIEW']['viewPath']);
                    $this->assertSame('standalone.twig', $data['_VIEW']['viewName']);
                    $this->assertNull($data['_VIEW']['viewAction']);

                    return true;
                })
            )
            ->willReturn('standalone body');

        $view = new View($twig);

        $this->assertSame('standalone body', $view->render('standalone.twig', [], true));
    }
}

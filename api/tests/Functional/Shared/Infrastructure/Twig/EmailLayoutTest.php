<?php

declare(strict_types=1);

namespace App\Tests\Functional\Shared\Infrastructure\Twig;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

/**
 * F02-T13: base email layout.
 * The HTML layout inlines its CSS and shows the Communion name (sender, from a parameter);
 * the text layout renders the same content as plain text.
 */
final class EmailLayoutTest extends KernelTestCase
{
    private Environment $twig;
    private string $communionName;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->twig = self::getContainer()->get(Environment::class);
        $this->communionName = self::getContainer()->getParameter('communion_name');
    }

    public function testHtmlLayoutInlinesCssAndRendersHeaderContentAndFooter(): void
    {
        $template = $this->twig->createTemplate(
            "{% extends 'emails/layout.html.twig' %}"
            . "{% block content %}<p>Bonjour,</p>{% endblock %}"
        );

        $html = $template->render();

        // The CSS is inlined on the elements (inline_css filter), not only in a <style> block
        $this->assertGreaterThanOrEqual(3, substr_count($html, 'style="'));
        // The Communion name (parameter) is the sender identity, in header and footer
        $this->assertGreaterThanOrEqual(2, substr_count($html, $this->communionName));
        // The content block is rendered
        $this->assertStringContainsString('<p>Bonjour,</p>', $html);
        // The footer is rendered
        $this->assertStringContainsString('ne pas y répondre', $html);
    }

    public function testTextLayoutRendersPlainText(): void
    {
        $template = $this->twig->createTemplate(
            "{% extends 'emails/layout.txt.twig' %}"
            . "{% block content %}Bonjour,{% endblock %}"
        );

        $text = $template->render();

        $this->assertStringContainsString('Bonjour,', $text);
        $this->assertStringContainsString($this->communionName, $text);
        $this->assertStringContainsString('ne pas y répondre', $text);
        $this->assertStringNotContainsString('<', $text);
    }
}

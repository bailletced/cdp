<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\Symfony\HtmlSanitizer;

use App\Chain\Application\Port\HtmlSanitizer;
use App\Chain\Infrastructure\Symfony\HtmlSanitizer\SymfonyHtmlSanitizer;
use PHPUnit\Framework\TestCase;

/**
 * F06-T01: the HtmlSanitizer adapter keeps only p, br, strong, em and
 * blockquote — without any attribute — and neutralizes XSS payloads (T27).
 */
final class SymfonyHtmlSanitizerTest extends TestCase
{
    public function testItKeepsParagraphsAndStripsAttributes(): void
    {
        $sanitizer = new SymfonyHtmlSanitizer();

        self::assertSame('<p>texte</p>', $sanitizer->sanitize('<p onclick="steal()">texte</p>'));
    }

    public function testItDropsScriptTagsWithTheirContent(): void
    {
        $sanitizer = new SymfonyHtmlSanitizer();

        self::assertSame('', $sanitizer->sanitize('<script>alert(1)</script>'));
    }

    public function testItKeepsBoldAndItalicMarkup(): void
    {
        $sanitizer = new SymfonyHtmlSanitizer();

        self::assertSame(
            '<p><strong>gras</strong> et <em>italique</em></p>',
            $sanitizer->sanitize('<p><strong>gras</strong> et <em>italique</em></p>'),
        );
    }

    public function testItKeepsBlockquoteAndLineBreak(): void
    {
        $sanitizer = new SymfonyHtmlSanitizer();

        self::assertSame(
            '<blockquote>citation</blockquote><p>suite<br />retour</p>',
            $sanitizer->sanitize('<blockquote>citation</blockquote><p>suite<br>retour</p>'),
        );
    }

    public function testItStripsStyleAttributes(): void
    {
        $sanitizer = new SymfonyHtmlSanitizer();

        self::assertSame('<p>texte</p>', $sanitizer->sanitize('<p style="color:red">texte</p>'));
    }

    public function testItDropsLinksWithTheirText(): void
    {
        $sanitizer = new SymfonyHtmlSanitizer();

        self::assertSame('', $sanitizer->sanitize('<a href="https://evil.example">lien</a>'));
    }

    public function testItDropsJavascriptUrls(): void
    {
        $sanitizer = new SymfonyHtmlSanitizer();

        self::assertSame('<p></p>', $sanitizer->sanitize('<p><a href="javascript:alert(1)">clic</a></p>'));
    }

    public function testItDropsImages(): void
    {
        $sanitizer = new SymfonyHtmlSanitizer();

        self::assertSame('', $sanitizer->sanitize('<img src=x onerror=alert(1)>'));
    }

    public function testItDropsIframes(): void
    {
        $sanitizer = new SymfonyHtmlSanitizer();

        self::assertSame('', $sanitizer->sanitize('<iframe src="https://evil.example"></iframe>'));
    }

    public function testItDropsSvgElements(): void
    {
        $sanitizer = new SymfonyHtmlSanitizer();

        self::assertSame('', $sanitizer->sanitize('<svg onload=alert(1)><circle r="1"/></svg>'));
    }

    public function testItDropsUnknownInlineElementsWithTheirText(): void
    {
        $sanitizer = new SymfonyHtmlSanitizer();

        self::assertSame('<p>Texte </p>', $sanitizer->sanitize('<p>Texte <span class="x">inconnu</span></p>'));
    }

    public function testItKeepsPlainTextAsIs(): void
    {
        $sanitizer = new SymfonyHtmlSanitizer();

        self::assertSame('Texte brut sans balise', $sanitizer->sanitize('Texte brut sans balise'));
    }

    public function testItDecodesEntitiesToUtf8(): void
    {
        $sanitizer = new SymfonyHtmlSanitizer();

        self::assertSame('<p>café &amp; thé</p>', $sanitizer->sanitize('<p>caf&eacute; &amp; th&eacute;</p>'));
    }

    public function testItReturnsAnEmptyStringForEmptyInput(): void
    {
        $sanitizer = new SymfonyHtmlSanitizer();

        self::assertSame('', $sanitizer->sanitize(''));
        self::assertSame('', $sanitizer->sanitize('   '));
    }

    public function testItIsIdempotent(): void
    {
        $sanitizer = new SymfonyHtmlSanitizer();

        $once = $sanitizer->sanitize('<p onclick="x">Texte <strong>gras</strong></p><script>alert(1)</script>');

        self::assertSame('<p>Texte <strong>gras</strong></p>', $once);
        self::assertSame($once, $sanitizer->sanitize($once));
    }

    public function testItImplementsThePort(): void
    {
        self::assertInstanceOf(HtmlSanitizer::class, new SymfonyHtmlSanitizer());
    }
}

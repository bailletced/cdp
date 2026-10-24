<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\Twig;

use App\Chain\Infrastructure\Twig\RichTextExtension;
use PHPUnit\Framework\TestCase;

final class RichTextExtensionTest extends TestCase
{
    public function testTheRichTextBecomesPlainParagraphsForTheTextEmail(): void
    {
        $html = '<p>Que rien ne te trouble,<br>que rien ne t’effraie.</p><blockquote><p>Dieu seul suffit.</p></blockquote><p>Foi &amp; <strong>espérance</strong></p>';

        self::assertSame(
            "Que rien ne te trouble,\nque rien ne t’effraie.\n\nDieu seul suffit.\n\nFoi & espérance",
            new RichTextExtension()->plainText($html),
        );
    }
}

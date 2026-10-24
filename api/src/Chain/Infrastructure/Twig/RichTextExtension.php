<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Twig;

use Twig\Attribute\AsTwigFilter;

/**
 * Twig filter `plain_text` (F11-T04): the sanitized rich text of a text
 * (p, br, strong, em, blockquote — T27) as plain paragraphs, for the text
 * version of the emails.
 */
final class RichTextExtension
{
    #[AsTwigFilter('plain_text')]
    public function plainText(string $html): string
    {
        $text = preg_replace(['#<br\s*/?>#i', '#</(p|blockquote)>#i'], ["\n", "\n\n"], $html) ?? $html;
        $text = html_entity_decode(strip_tags($text), \ENT_QUOTES | \ENT_HTML5, 'UTF-8');

        return trim(preg_replace("/\n{3,}/", "\n\n", $text) ?? $text);
    }
}

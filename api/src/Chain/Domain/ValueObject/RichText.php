<?php

declare(strict_types=1);

namespace App\Chain\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

/**
 * Rich text body of a prayer text (T27): sanitized HTML (see the
 * HtmlSanitizer port), non-empty once tags are stripped, bounded in length.
 */
#[ORM\Embeddable]
final readonly class RichText
{
    public const int MAX_LENGTH = 10_000;

    #[ORM\Column(name: 'body', type: 'text', length: self::MAX_LENGTH)]
    public string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        // Emptiness is judged on the visible text: tags are stripped first,
        // then entities are decoded, so an escaped "<p>" still counts as
        // content while a lone non-breaking space does not.
        $visibleText = trim(str_replace("\u{00A0}", ' ', html_entity_decode(strip_tags($value), \ENT_QUOTES | \ENT_HTML5, 'UTF-8')));

        if ('' === $visibleText) {
            throw new InvalidArgumentException('Le corps du texte est obligatoire.');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(\sprintf('Le corps du texte ne doit pas dépasser %d caractères.', self::MAX_LENGTH));
        }

        $this->value = $value;
    }
}

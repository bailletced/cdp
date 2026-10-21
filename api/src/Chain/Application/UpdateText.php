<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Domain\ValueObject\TextId;
use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use App\Shared\Application\Command\CommandInterface;

/**
 * Updates a text of the library (US-06.2, RG-TX-3: the correction applies
 * everywhere the text is attributed).
 *
 * The body is raw HTML (Tiptap output): the handler sanitizes it through the
 * HtmlSanitizer port before the RichText value object is built (T27).
 */
final readonly class UpdateText implements CommandInterface
{
    public function __construct(
        public TextId $textId,
        public TextTitle $title,
        public TextSource $source,
        public string $body,
        public ?string $hints,
    ) {
    }
}

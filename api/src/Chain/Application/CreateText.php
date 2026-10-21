<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use App\Shared\Application\Command\CommandInterface;

/**
 * Creates a text of the library (US-06.1).
 *
 * The body is raw HTML (Tiptap output): the handler sanitizes it through the
 * HtmlSanitizer port before the RichText value object is built (T27).
 */
final readonly class CreateText implements CommandInterface
{
    public function __construct(
        public TextTitle $title,
        public TextSource $source,
        public string $body,
        public ?string $hints = null,
    ) {
    }
}

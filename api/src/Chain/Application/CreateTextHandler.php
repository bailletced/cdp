<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Application\Port\HtmlSanitizer;
use App\Chain\Domain\Entity\Text;
use App\Chain\Domain\Repository\TextRepositoryInterface;
use App\Chain\Domain\ValueObject\RichText;
use App\Shared\Application\Command\AsCommandHandler;

/**
 * Creates a text of the library (US-06.1).
 *
 * The body arrives as raw HTML (Tiptap output): it is sanitized through the
 * HtmlSanitizer port BEFORE the RichText value object is built, so the
 * aggregate only ever holds whitelisted markup (T27). A body that is empty
 * once cleaned makes the RichText constructor throw an InvalidArgumentException.
 */
#[AsCommandHandler]
final readonly class CreateTextHandler
{
    public function __construct(
        private TextRepositoryInterface $textRepository,
        private HtmlSanitizer $htmlSanitizer,
    ) {
    }

    public function __invoke(CreateText $command): CreateTextResult
    {
        $body = new RichText($this->htmlSanitizer->sanitize($command->body));

        $text = Text::create($command->title, $command->source, $body, $command->hints);

        $this->textRepository->add($text);

        return new CreateTextResult($text->getId());
    }
}

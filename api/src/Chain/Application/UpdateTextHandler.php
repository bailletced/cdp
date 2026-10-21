<?php

declare(strict_types=1);

namespace App\Chain\Application;

use App\Chain\Application\Port\HtmlSanitizer;
use App\Chain\Domain\Exception\TextNotFoundException;
use App\Chain\Domain\Repository\TextRepositoryInterface;
use App\Chain\Domain\ValueObject\RichText;
use App\Shared\Application\Command\AsCommandHandler;

/**
 * Updates a text of the library (US-06.2).
 *
 * RG-TX-3: the correction applies everywhere the text is attributed — the
 * aggregate is the single source, slots reference it by id. The body is
 * sanitized through the HtmlSanitizer port before the RichText value object
 * is built (T27); an empty-after-clean body throws an InvalidArgumentException.
 */
#[AsCommandHandler]
final readonly class UpdateTextHandler
{
    public function __construct(
        private TextRepositoryInterface $textRepository,
        private HtmlSanitizer $htmlSanitizer,
    ) {
    }

    public function __invoke(UpdateText $command): void
    {
        $text = $this->textRepository->findById($command->textId);

        if (null === $text) {
            throw new TextNotFoundException();
        }

        $body = new RichText($this->htmlSanitizer->sanitize($command->body));

        $text->update($command->title, $command->source, $body, $command->hints);
    }
}

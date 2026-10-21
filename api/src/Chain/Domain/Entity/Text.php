<?php

declare(strict_types=1);

namespace App\Chain\Domain\Entity;

use App\Chain\Domain\ValueObject\RichText;
use App\Chain\Domain\ValueObject\TextId;
use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

/**
 * Text aggregate root (Chain BC).
 *
 * A text of the library, common to all editions: title, source, rich-text
 * body (sanitized HTML, see the HtmlSanitizer port) and optional meditation
 * hints (plain text). Attribution to slots belongs to F07; deletion safety
 * is RG-TX-3, enforced by the DeleteText command.
 *
 * @see specs-fonctionnelles.md §5.6, RG-TX-1 to RG-TX-3
 * @see specs-techniques.md §10.1 (T27)
 */
#[ORM\Entity]
#[ORM\Table(name: 'text')]
class Text
{
    public const int MAX_HINTS_LENGTH = 2000;

    #[ORM\Embedded(columnPrefix: false)]
    private TextId $id;

    #[ORM\Embedded(columnPrefix: false)]
    private TextTitle $title;

    #[ORM\Embedded(columnPrefix: false)]
    private TextSource $source;

    #[ORM\Embedded(columnPrefix: false)]
    private RichText $body;

    #[ORM\Column(name: 'hints', type: 'text', length: self::MAX_HINTS_LENGTH, nullable: true)]
    private ?string $hints;

    private function __construct(TextTitle $title, TextSource $source, RichText $body, ?string $hints)
    {
        $this->id = new TextId();
        $this->title = $title;
        $this->source = $source;
        $this->body = $body;
        $this->hints = self::normalizeHints($hints);
    }

    public static function create(TextTitle $title, TextSource $source, RichText $body, ?string $hints = null): self
    {
        return new self($title, $source, $body, $hints);
    }

    /**
     * RG-TX-3: an update applies everywhere the text is attributed.
     */
    public function update(TextTitle $title, TextSource $source, RichText $body, ?string $hints): void
    {
        $this->title = $title;
        $this->source = $source;
        $this->body = $body;
        $this->hints = self::normalizeHints($hints);
    }

    public function getId(): TextId
    {
        return $this->id;
    }

    public function getTitle(): TextTitle
    {
        return $this->title;
    }

    public function getSource(): TextSource
    {
        return $this->source;
    }

    public function getBody(): RichText
    {
        return $this->body;
    }

    public function getHints(): ?string
    {
        return $this->hints;
    }

    /**
     * Hints are optional plain text: trimmed, empty normalized to null,
     * bounded in length.
     */
    private static function normalizeHints(?string $hints): ?string
    {
        if (null === $hints) {
            return null;
        }

        $hints = trim($hints);

        if ('' === $hints) {
            return null;
        }

        if (mb_strlen($hints) > self::MAX_HINTS_LENGTH) {
            throw new InvalidArgumentException(\sprintf('Les pistes de méditation ne doivent pas dépasser %d caractères.', self::MAX_HINTS_LENGTH));
        }

        return $hints;
    }
}

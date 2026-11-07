<?php

declare(strict_types=1);

namespace App\Chain\Domain\Entity;

use App\Chain\Domain\ValueObject\RichText;
use App\Chain\Domain\ValueObject\TextId;
use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use Doctrine\ORM\Mapping as ORM;

/**
 * Text aggregate root (Chain BC).
 *
 * A text of the library, common to all editions: title, source and rich-text
 * body (sanitized HTML, see the HtmlSanitizer port). Attribution to slots
 * belongs to F07; deletion safety
 * is RG-TX-3, enforced by the DeleteText command.
 *
 * @see specs-fonctionnelles.md §5.6, RG-TX-1 to RG-TX-3
 * @see specs-techniques.md §10.1 (T27)
 */
#[ORM\Entity]
#[ORM\Table(name: 'text')]
class Text
{
    #[ORM\Embedded(columnPrefix: false)]
    private TextId $id;

    #[ORM\Embedded(columnPrefix: false)]
    private TextTitle $title;

    #[ORM\Embedded(columnPrefix: false)]
    private TextSource $source;

    #[ORM\Embedded(columnPrefix: false)]
    private RichText $body;

    private function __construct(TextTitle $title, TextSource $source, RichText $body)
    {
        $this->id = new TextId();
        $this->title = $title;
        $this->source = $source;
        $this->body = $body;
    }

    public static function create(TextTitle $title, TextSource $source, RichText $body): self
    {
        return new self($title, $source, $body);
    }

    /**
     * RG-TX-3: an update applies everywhere the text is attributed.
     */
    public function update(TextTitle $title, TextSource $source, RichText $body): void
    {
        $this->title = $title;
        $this->source = $source;
        $this->body = $body;
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
}

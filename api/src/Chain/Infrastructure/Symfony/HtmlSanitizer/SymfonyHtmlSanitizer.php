<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Symfony\HtmlSanitizer;

use App\Chain\Application\Port\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer as ComponentHtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerAction;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Symfony HtmlSanitizer adapter (F06-T01, T27): strict whitelist for prayer
 * texts — p, br, strong, em, blockquote, no attribute. Any other element is
 * dropped with its children (default action Drop, set explicitly rather than
 * relying on the component default).
 */
final readonly class SymfonyHtmlSanitizer implements HtmlSanitizer
{
    private ComponentHtmlSanitizer $sanitizer;

    public function __construct()
    {
        $config = new HtmlSanitizerConfig()
            ->defaultAction(HtmlSanitizerAction::Drop)
            ->allowElement('p')
            ->allowElement('br')
            ->allowElement('strong')
            ->allowElement('em')
            ->allowElement('blockquote');

        $this->sanitizer = new ComponentHtmlSanitizer($config);
    }

    #[\Override]
    public function sanitize(string $html): string
    {
        return $this->sanitizer->sanitize($html);
    }
}

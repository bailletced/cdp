<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Chain\Application\CreateText;
use App\Chain\Application\CreateTextResult;
use App\Chain\Application\DeleteText;
use App\Chain\Application\Port\HtmlSanitizer;
use App\Chain\Application\UpdateText;
use App\Chain\Domain\Entity\Text;
use App\Chain\Domain\ValueObject\RichText;
use App\Chain\Domain\ValueObject\TextId;
use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use App\Chain\Infrastructure\ApiPlatform\Resource\AdminTextResource;
use App\Shared\Application\Command\CommandBusInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Processes AdminTextResource resources for POST (create), PATCH (update) and
 * DELETE operations (F06-T09).
 *
 * Invalid payloads answer 422 with one violation per offending property:
 * value object rejections (title, source, body) and hints length are mapped
 * to their respective property paths. Domain exceptions (404, 409) propagate
 * to API Platform's error handling.
 *
 * @implements ProcessorInterface<AdminTextResource, AdminTextResource>
 */
final readonly class AdminTextProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private HtmlSanitizer $htmlSanitizer,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AdminTextResource
    {
        if ($operation instanceof Post) {
            return $this->handleCreate($data);
        }

        if ($operation instanceof Patch) {
            return $this->handleUpdate($data, $uriVariables);
        }

        if ($operation instanceof Delete) {
            $this->handleDelete($uriVariables);

            return new AdminTextResource();
        }

        throw new \LogicException(\sprintf('Unsupported operation %s.', $operation::class));
    }

    private function handleCreate(AdminTextResource $data): AdminTextResource
    {
        $title = $this->toTitle($data->title);
        $source = $this->toSource($data->source);
        $this->validateBody($data->body);
        $this->validateHints($data->hints);

        $result = $this->commandBus->dispatch(new CreateText(
            $title,
            $source,
            $data->body ?? '',
            $data->hints,
        ));

        if (!$result instanceof CreateTextResult) {
            throw new \LogicException(\sprintf('Expected the command bus to return an %s, got %s.', CreateTextResult::class, get_debug_type($result)));
        }

        return new AdminTextResource(
            id: (string) $result->textId,
            title: $data->title,
            source: $data->source,
            body: $data->body,
            hints: $data->hints,
        );
    }

    /**
     * @param array<string, mixed> $uriVariables
     */
    private function handleUpdate(AdminTextResource $data, array $uriVariables): AdminTextResource
    {
        $textId = $this->textIdFromUri($uriVariables);

        $title = $this->toTitle($data->title);
        $source = $this->toSource($data->source);
        $this->validateBody($data->body);
        $this->validateHints($data->hints);

        // TextNotFoundException propagates: the DomainException mechanism maps it to 404.
        $this->commandBus->dispatch(new UpdateText(
            $textId,
            $title,
            $source,
            $data->body ?? '',
            $data->hints,
        ));

        return new AdminTextResource(
            id: (string) $textId,
            title: $data->title,
            source: $data->source,
            body: $data->body,
            hints: $data->hints,
        );
    }

    /**
     * @param array<string, mixed> $uriVariables
     */
    private function handleDelete(array $uriVariables): void
    {
        $textId = $this->textIdFromUri($uriVariables);

        // TextNotFoundException and TextAttributedToScheduledEditionException
        // propagate: the DomainException mechanism maps them to 404 and 409.
        $this->commandBus->dispatch(new DeleteText($textId));
    }

    /**
     * @param array<string, mixed> $uriVariables
     */
    private function textIdFromUri(array $uriVariables): TextId
    {
        if (!isset($uriVariables['id']) || !\is_string($uriVariables['id'])) {
            throw new \LogicException('Missing text identifier in URI variables.');
        }

        return new TextId($uriVariables['id']);
    }

    private function toTitle(?string $title): TextTitle
    {
        try {
            return new TextTitle($title ?? '');
        } catch (\InvalidArgumentException $e) {
            throw self::violation('title', $e->getMessage());
        }
    }

    private function toSource(?string $source): TextSource
    {
        try {
            return new TextSource($source ?? '');
        } catch (\InvalidArgumentException $e) {
            throw self::violation('source', $e->getMessage());
        }
    }

    private function validateBody(?string $body): void
    {
        if (null === $body) {
            throw self::violation('body', 'Le corps du texte est obligatoire.');
        }

        try {
            new RichText($this->htmlSanitizer->sanitize($body));
        } catch (\InvalidArgumentException $e) {
            throw self::violation('body', $e->getMessage());
        }
    }

    private function validateHints(?string $hints): void
    {
        if (null === $hints) {
            return;
        }

        $hints = trim($hints);

        if ('' === $hints) {
            return;
        }

        if (mb_strlen($hints) > Text::MAX_HINTS_LENGTH) {
            throw self::violation('hints', \sprintf('Les pistes de méditation ne doivent pas dépasser %d caractères.', Text::MAX_HINTS_LENGTH));
        }
    }

    private static function violation(string $propertyPath, string $message): ValidationException
    {
        return new ValidationException(new ConstraintViolationList([
            new ConstraintViolation($message, null, [], null, $propertyPath, null),
        ]));
    }
}

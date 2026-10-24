<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Chain\Application\Dto\AdminEditionView;
use App\Chain\Application\Dto\AdminSlotView;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Infrastructure\ApiPlatform\State\Processor\AdminEditionDistributeTextsProcessor;
use App\Chain\Infrastructure\ApiPlatform\State\Processor\AdminEditionProcessor;
use App\Chain\Infrastructure\ApiPlatform\State\Processor\AdminEditionScheduleProcessor;
use App\Chain\Infrastructure\ApiPlatform\State\Provider\AdminEditionProvider;
use App\Shared\Infrastructure\Symfony\Validator\AssertValueObject;

/**
 * Admin resource for managing editions (F05-T14, F05-T15, F05-T16).
 *
 * The payload is validated on write: title and presentation carry the value
 * object bounds as AssertValueObject constraints, so API Platform answers 422
 * with one violation per offending property. The date rules (full/half hour,
 * opening before start) are enforced by the processor, which raises the same
 * kind of violation.
 */
#[ApiResource(
    shortName: 'AdminEdition',
    operations: [
        new GetCollection(
            uriTemplate: '/admin/editions',
            provider: AdminEditionProvider::class,
            security: 'is_granted("ROLE_ORGANIZER")',
        ),
        new Get(
            uriTemplate: '/admin/editions/{id}',
            provider: AdminEditionProvider::class,
            security: 'is_granted("ROLE_ORGANIZER")',
        ),
        new Post(
            uriTemplate: '/admin/editions',
            processor: AdminEditionProcessor::class,
            security: 'is_granted("ROLE_ORGANIZER")',
        ),
        new Patch(
            uriTemplate: '/admin/editions/{id}',
            processor: AdminEditionProcessor::class,
            read: false,
            security: 'is_granted("ROLE_ORGANIZER")',
        ),
        new Post(
            uriTemplate: '/admin/editions/{id}/schedule',
            processor: AdminEditionScheduleProcessor::class,
            security: 'is_granted("ROLE_ORGANIZER")',
        ),
        new Post(
            uriTemplate: '/admin/editions/{id}/unschedule',
            processor: AdminEditionScheduleProcessor::class,
            security: 'is_granted("ROLE_ORGANIZER")',
        ),
        new Post(
            uriTemplate: '/admin/editions/{id}/distribute-texts',
            processor: AdminEditionDistributeTextsProcessor::class,
            read: false,
            status: 200,
            security: 'is_granted("ROLE_ORGANIZER")',
        ),
    ],
)]
final class AdminEditionResource
{
    /**
     * @param array<array{id: string, rank: int, startsAt: string, endsAt: string, label: string, partOfDay: string, textId: string|null, textTitle: string|null, textSource: string|null, textAlreadyUsed: bool}>|null $slots
     * @param list<string>|null                                                                                                                                                                                         $textIds
     * @param list<string>|null                                                                                                                                                                                         $duplicatedTextIds texts carried by several slots (« Texte déjà utilisé », F07-T04)
     */
    public function __construct(
        #[ApiProperty(identifier: true)]
        public readonly ?string $id = null,
        #[AssertValueObject(EditionTitle::class)]
        public readonly ?string $title = null,
        #[AssertValueObject(Presentation::class)]
        public readonly ?string $presentation = null,
        public readonly ?string $startsAt = null,
        public readonly ?string $opensAt = null,
        public readonly ?string $resolvedStartsAtUtc = null,
        public readonly ?string $resolvedStartsAtLabel = null,
        public readonly ?string $lifecycle = null,
        public readonly ?string $status = null,
        public readonly ?string $scheduledAt = null,
        public readonly ?int $version = null,
        public readonly ?array $slots = null,
        public readonly ?array $textIds = null,
        public readonly ?int $fromRank = null,
        public readonly ?int $slotsWithoutText = null,
        public readonly ?array $duplicatedTextIds = null,
        public readonly ?string $warning = null,
    ) {
    }

    /**
     * Read model of an edition, with its slots and their texts (F07-T04).
     */
    public static function fromView(AdminEditionView $view, ?string $warning = null): self
    {
        return new self(
            id: $view->id,
            title: $view->title,
            presentation: $view->presentation,
            startsAt: $view->startsAtUtc,
            opensAt: $view->opensAtUtc,
            lifecycle: $view->lifecycle,
            status: $view->status,
            scheduledAt: $view->scheduledAtUtc,
            version: $view->version,
            slots: array_map(static fn (AdminSlotView $slot): array => [
                'id' => $slot->id,
                'rank' => $slot->rank,
                'startsAt' => $slot->startsAtUtc,
                'endsAt' => $slot->endsAtUtc,
                'label' => $slot->label,
                'partOfDay' => $slot->partOfDay,
                'textId' => $slot->textId,
                'textTitle' => $slot->textTitle,
                'textSource' => $slot->textSource,
                'textAlreadyUsed' => $slot->textAlreadyUsed,
            ], $view->slots),
            slotsWithoutText: $view->slotsWithoutText,
            duplicatedTextIds: $view->duplicatedTextIds,
            warning: $warning,
        );
    }
}

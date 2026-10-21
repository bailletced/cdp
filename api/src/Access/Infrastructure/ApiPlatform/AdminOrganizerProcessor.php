<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Access\Application\AddOrganizer;
use App\Access\Application\AddOrganizerResult;
use App\Access\Application\Query\OrganizerViewFinder;
use App\Access\Application\RemoveOrganizer;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Domain\ValueObject\Email;
use InvalidArgumentException;

/**
 * Processes write operations on AdminOrganizer (F04-T02).
 *
 * - POST   → dispatches AddOrganizer, returns the created organizer
 * - DELETE → dispatches RemoveOrganizer with the organizer's email
 *
 * @implements ProcessorInterface<AdminOrganizer, AdminOrganizer|null>
 */
final readonly class AdminOrganizerProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private OrganizerViewFinder $finder,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?AdminOrganizer
    {
        if ($operation instanceof Post) {
            return $this->handlePost($data);
        }

        // DELETE: the provider already loaded the item into $data
        $this->handleDelete($data);

        return null;
    }

    private function handlePost(AdminOrganizer $data): AdminOrganizer
    {
        if (null === $data->email || '' === trim($data->email)) {
            throw new InvalidArgumentException("L'adresse e-mail est requise.");
        }

        $outcome = $this->commandBus->dispatch(new AddOrganizer(new Email($data->email)));

        if (!$outcome instanceof AddOrganizerResult) {
            throw new \LogicException(\sprintf('Expected the command bus to return an %s, got %s.', AddOrganizerResult::class, get_debug_type($outcome)));
        }

        $view = $this->finder->findByEmail(new Email($data->email));

        if (null === $view) {
            throw new \LogicException(\sprintf('Organizer "%s" was created but could not be found afterwards.', $data->email));
        }

        return new AdminOrganizer(
            id: $view->id,
            email: $view->email,
            addedByEmail: $view->addedByEmail,
            addedAt: $view->addedAt,
        );
    }

    private function handleDelete(AdminOrganizer $data): void
    {
        if (null === $data->email) {
            throw new \LogicException('Cannot delete an organizer without an email.');
        }

        // LastOrganizerException propagates: the DomainException mechanism maps it to a 400.
        $this->commandBus->dispatch(new RemoveOrganizer(new Email($data->email)));
    }
}

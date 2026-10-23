<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Chain\Application\RegisterInscriptions;
use App\Chain\Application\RegisterInscriptionsResult;
use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Exception\AlreadyRegisteredException;
use App\Chain\Domain\Repository\PriantRepositoryInterface;
use App\Chain\Domain\ValueObject\DisplayName;
use App\Chain\Domain\ValueObject\PriantId;
use App\Chain\Domain\ValueObject\SlotId;
use App\Chain\Infrastructure\ApiPlatform\Payload\RegisterInscriptionsPayload;
use App\Chain\Infrastructure\ApiPlatform\Resource\InscriptionResource;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Port\CurrentViewer;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Processes POST /api/inscriptions (F09-T10).
 *
 * Anti-abuse first (§6.7): a filled honeypot (`website`) answers an
 * identical-looking 201 success and nothing is done. Then the payload is
 * checked field by field (422 with one violation per field), the consent rule
 * is enforced (RG-IN-5: mandatory off-session, or when the authenticated
 * priant never consented), and the command is dispatched. Domain exceptions
 * propagate to API Platform's error handling (404 unknown slot, 409 already
 * registered, edition closed or slot past).
 *
 * @implements ProcessorInterface<RegisterInscriptionsPayload, InscriptionResource>
 */
final readonly class RegisterInscriptionsProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private PriantRepositoryInterface $priantRepository,
        private CurrentViewer $currentViewer,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): InscriptionResource
    {
        // Honeypot (§6.7): filled → identical-looking success, nothing is done.
        // A random identifier keeps the JSON-LD @id generatable, exactly like
        // a real success.
        if (null !== $data->website && '' !== trim($data->website)) {
            return new InscriptionResource(id: Uuid::v4()->toRfc4122());
        }

        $slotIds = $this->toSlotIds($data->slotIds);
        $displayName = $this->toDisplayName($data->displayName);
        $email = $this->toEmail($data->email);

        if (!$this->hasConsent($data->consent)) {
            throw self::violation('consent', 'Le consentement est obligatoire pour s’inscrire.');
        }

        try {
            $result = $this->commandBus->dispatch(new RegisterInscriptions(
                $slotIds,
                $displayName,
                $email,
                $data->consent ?? false,
            ));
        } catch (UniqueConstraintViolationException $e) {
            // RG-IN-3: a concurrent request (double click) registered the same
            // (slot, priant) first — the unique index rejected our flush, which
            // happens inside dispatch() (the command bus runs in a transaction).
            // Other unique violations (e.g. the priant email race, RG-IN-2) bubble up.
            if (!str_contains($e->getMessage(), 'inscription_slot_priant_unique')) {
                throw $e;
            }

            throw new AlreadyRegisteredException();
        }

        if (!$result instanceof RegisterInscriptionsResult) {
            throw new \LogicException(\sprintf('Expected the command bus to return a %s, got %s.', RegisterInscriptionsResult::class, get_debug_type($result)));
        }

        return new InscriptionResource(
            id: $result->inscriptions[0]->getId()->value->toRfc4122(),
            priantId: $result->priantId->value->toRfc4122(),
            displayName: $result->displayName->value,
            inscriptions: array_map(
                static fn (Inscription $inscription): array => [
                    'id' => $inscription->getId()->value->toRfc4122(),
                    'slotId' => $inscription->getSlotId()->value->toRfc4122(),
                    'editionId' => $inscription->getEditionId()->value->toRfc4122(),
                ],
                $result->inscriptions,
            ),
        );
    }

    /**
     * The payload declares list<string>, but the JSON body is untrusted: the
     * items are re-checked here before being turned into value objects.
     *
     * @param ?list<mixed> $slotIds
     *
     * @return list<SlotId>
     */
    private function toSlotIds(?array $slotIds): array
    {
        if (null === $slotIds || [] === $slotIds) {
            throw self::violation('slotIds', 'Au moins un créneau est requis.');
        }

        $ids = [];
        foreach ($slotIds as $slotId) {
            if (!\is_string($slotId)) {
                throw self::violation('slotIds', 'Chaque créneau doit être un identifiant valide.');
            }

            try {
                $ids[] = new SlotId($slotId);
            } catch (\InvalidArgumentException) {
                throw self::violation('slotIds', 'Chaque créneau doit être un identifiant valide.');
            }
        }

        return $ids;
    }

    private function toDisplayName(?string $displayName): DisplayName
    {
        if (null === $displayName || '' === trim($displayName)) {
            throw self::violation('displayName', 'Le nom affiché est obligatoire.');
        }

        try {
            return new DisplayName($displayName);
        } catch (\InvalidArgumentException $e) {
            throw self::violation('displayName', $e->getMessage());
        }
    }

    private function toEmail(?string $email): Email
    {
        if (null === $email || '' === trim($email)) {
            throw self::violation('email', 'L’adresse e-mail est obligatoire.');
        }

        try {
            return new Email($email);
        } catch (\InvalidArgumentException $e) {
            throw self::violation('email', $e->getMessage());
        }
    }

    /**
     * RG-IN-5: the consent is mandatory off-session, or when the authenticated
     * priant never consented. In session, the checkbox is hidden once the
     * consent has been given, so an absent consent is accepted then.
     */
    private function hasConsent(?bool $consent): bool
    {
        if (true === $consent) {
            return true;
        }

        $priantId = $this->currentViewer->current()->priantId;

        if (null === $priantId) {
            return false;
        }

        try {
            $priant = $this->priantRepository->findById(new PriantId($priantId));
        } catch (\InvalidArgumentException) {
            return false;
        }

        return null !== $priant?->getConsentAt();
    }

    private static function violation(string $propertyPath, string $message): ValidationException
    {
        return new ValidationException(new ConstraintViolationList([
            new ConstraintViolation($message, null, [], null, $propertyPath, null),
        ]));
    }
}

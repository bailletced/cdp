<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Chain\Application\RegisterInscriptions;
use App\Chain\Application\RegisterInscriptionsResult;
use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Entity\Priant;
use App\Chain\Domain\ValueObject\DisplayName;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\PriantId;
use App\Chain\Domain\ValueObject\SlotId;
use App\Chain\Infrastructure\ApiPlatform\Payload\RegisterInscriptionsPayload;
use App\Chain\Infrastructure\ApiPlatform\Resource\InscriptionResource;
use App\Chain\Infrastructure\ApiPlatform\State\Processor\RegisterInscriptionsProcessor;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryPriantRepository;
use App\Shared\Domain\Security\Viewer;
use App\Shared\Domain\ValueObject\Email;
use App\Tests\Unit\Shared\Application\Command\Fixture\FakeCommandBus;
use App\Tests\Unit\Shared\Application\Port\Fixture\FakeCurrentViewer;
use PHPUnit\Framework\TestCase;

final class RegisterInscriptionsProcessorTest extends TestCase
{
    private InMemoryPriantRepository $priantRepository;

    protected function setUp(): void
    {
        $this->priantRepository = new InMemoryPriantRepository();
    }

    public function testItDispatchesRegisterInscriptionsAndReturnsTheCreatedInscriptions(): void
    {
        $result = self::successfulResult();
        $commandBus = new FakeCommandBus($result);
        $processor = $this->processor($commandBus, Viewer::visitor());

        $slot1 = new SlotId();
        $slot2 = new SlotId();

        $resource = $processor->process(new RegisterInscriptionsPayload(
            slotIds: [$slot1->value->toRfc4122(), $slot2->value->toRfc4122()],
            displayName: 'Famille Martin',
            email: 'famille.martin@bailletced.org',
            consent: true,
        ), new Post(), [], []);

        $command = $commandBus->dispatched;
        self::assertInstanceOf(RegisterInscriptions::class, $command);
        self::assertEquals([$slot1, $slot2], $command->slotIds);
        self::assertSame('Famille Martin', $command->displayName->value);
        self::assertSame('famille.martin@bailletced.org', $command->email->value);
        self::assertTrue($command->consent);

        self::assertInstanceOf(InscriptionResource::class, $resource);
        self::assertSame($result->inscriptions[0]->getId()->value->toRfc4122(), $resource->id);
        self::assertSame($result->priantId->value->toRfc4122(), $resource->priantId);
        self::assertSame('Famille Martin', $resource->displayName);
        self::assertCount(2, $resource->inscriptions);
        self::assertSame($result->inscriptions[0]->getSlotId()->value->toRfc4122(), $resource->inscriptions[0]['slotId']);
        self::assertSame($result->inscriptions[0]->getEditionId()->value->toRfc4122(), $resource->inscriptions[0]['editionId']);
        self::assertSame($result->inscriptions[0]->getId()->value->toRfc4122(), $resource->inscriptions[0]['id']);
    }

    public function testAFilledHoneypotReturnsAnEmptySuccessWithoutDispatching(): void
    {
        $commandBus = new FakeCommandBus(self::successfulResult());
        $processor = $this->processor($commandBus, Viewer::visitor());

        $resource = $processor->process(new RegisterInscriptionsPayload(
            slotIds: [new SlotId()->value->toRfc4122()],
            displayName: 'Famille Martin',
            email: 'famille.martin@bailletced.org',
            consent: true,
            website: 'http://spam.example',
        ), new Post(), [], []);

        // §6.7: identical-looking success, but nothing is done.
        self::assertNull($commandBus->dispatched);
        self::assertNotNull($resource->id);
        self::assertNull($resource->priantId);
        self::assertSame([], $resource->inscriptions);
    }

    public function testMissingSlotIdsThrowsViolationOnSlotIds(): void
    {
        $this->assertViolationOn('slotIds', new RegisterInscriptionsPayload(
            displayName: 'Famille Martin',
            email: 'famille.martin@bailletced.org',
            consent: true,
        ));
    }

    public function testAnInvalidSlotIdThrowsViolationOnSlotIds(): void
    {
        $this->assertViolationOn('slotIds', new RegisterInscriptionsPayload(
            slotIds: ['not-a-uuid'],
            displayName: 'Famille Martin',
            email: 'famille.martin@bailletced.org',
            consent: true,
        ));
    }

    public function testMissingDisplayNameThrowsViolationOnDisplayName(): void
    {
        $this->assertViolationOn('displayName', new RegisterInscriptionsPayload(
            slotIds: [new SlotId()->value->toRfc4122()],
            email: 'famille.martin@bailletced.org',
            consent: true,
        ));
    }

    public function testTooLongDisplayNameThrowsViolationOnDisplayName(): void
    {
        $this->assertViolationOn('displayName', new RegisterInscriptionsPayload(
            slotIds: [new SlotId()->value->toRfc4122()],
            displayName: str_repeat('a', 81),
            email: 'famille.martin@bailletced.org',
            consent: true,
        ));
    }

    public function testMissingEmailThrowsViolationOnEmail(): void
    {
        $this->assertViolationOn('email', new RegisterInscriptionsPayload(
            slotIds: [new SlotId()->value->toRfc4122()],
            displayName: 'Famille Martin',
            consent: true,
        ));
    }

    public function testAnInvalidEmailThrowsViolationOnEmail(): void
    {
        $this->assertViolationOn('email', new RegisterInscriptionsPayload(
            slotIds: [new SlotId()->value->toRfc4122()],
            displayName: 'Famille Martin',
            email: 'not-an-email',
            consent: true,
        ));
    }

    public function testAVisitorWithoutConsentThrowsViolationOnConsent(): void
    {
        $this->assertViolationOn('consent', new RegisterInscriptionsPayload(
            slotIds: [new SlotId()->value->toRfc4122()],
            displayName: 'Famille Martin',
            email: 'famille.martin@bailletced.org',
        ), Viewer::visitor());
    }

    public function testAVisitorWithConsentFalseThrowsViolationOnConsent(): void
    {
        $this->assertViolationOn('consent', new RegisterInscriptionsPayload(
            slotIds: [new SlotId()->value->toRfc4122()],
            displayName: 'Famille Martin',
            email: 'famille.martin@bailletced.org',
            consent: false,
        ), Viewer::visitor());
    }

    public function testAnAuthenticatedPriantWhoNeverConsentedThrowsViolationOnConsent(): void
    {
        $priant = new Priant(new Email('famille.martin@bailletced.org'), new DisplayName('Famille Martin'));
        $this->priantRepository->add($priant);
        $viewer = new Viewer($priant->getId()->value->toRfc4122(), true, [], false);

        $this->assertViolationOn('consent', new RegisterInscriptionsPayload(
            slotIds: [new SlotId()->value->toRfc4122()],
            displayName: 'Famille Martin',
            email: 'famille.martin@bailletced.org',
            consent: false,
        ), $viewer);
    }

    public function testAnAuthenticatedPriantWhoAlreadyConsentedDoesNotNeedToConsentAgain(): void
    {
        $priant = new Priant(new Email('famille.martin@bailletced.org'), new DisplayName('Famille Martin'));
        $priant->giveConsent(new \DateTimeImmutable('2026-10-15 08:00:00', new \DateTimeZone('UTC')));
        $this->priantRepository->add($priant);
        $viewer = new Viewer($priant->getId()->value->toRfc4122(), true, [], false);

        $commandBus = new FakeCommandBus(self::successfulResult());
        $processor = $this->processor($commandBus, $viewer);

        // RG-IN-5: in session, the checkbox is hidden once the consent is given.
        $processor->process(new RegisterInscriptionsPayload(
            slotIds: [new SlotId()->value->toRfc4122()],
            displayName: 'Famille Martin',
            email: 'famille.martin@bailletced.org',
        ), new Post(), [], []);

        $command = $commandBus->dispatched;
        self::assertInstanceOf(RegisterInscriptions::class, $command);
        self::assertFalse($command->consent);
    }

    private function processor(FakeCommandBus $commandBus, Viewer $viewer): RegisterInscriptionsProcessor
    {
        return new RegisterInscriptionsProcessor(
            $commandBus,
            $this->priantRepository,
            new FakeCurrentViewer($viewer),
        );
    }

    private function assertViolationOn(string $propertyPath, RegisterInscriptionsPayload $payload, ?Viewer $viewer = null): void
    {
        $processor = $this->processor(new FakeCommandBus(self::successfulResult()), $viewer ?? Viewer::visitor());

        try {
            $processor->process($payload, new Post(), [], []);
            self::fail('A ValidationException was expected.');
        } catch (ValidationException $e) {
            $violations = $e->getConstraintViolationList();
            self::assertCount(1, $violations);
            self::assertSame($propertyPath, $violations[0]->getPropertyPath());
        }
    }

    private static function successfulResult(): RegisterInscriptionsResult
    {
        $priantId = new PriantId();
        $editionId = new EditionId();
        $slot1 = new SlotId();
        $slot2 = new SlotId();

        return new RegisterInscriptionsResult(
            $priantId,
            new DisplayName('Famille Martin'),
            [
                new Inscription($editionId, $slot1, $priantId),
                new Inscription($editionId, $slot2, $priantId),
            ],
        );
    }
}

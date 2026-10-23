<?php

declare(strict_types=1);

namespace App\Tests\Behat\Chain;

use App\Access\Domain\Entity\Account;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Priant;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\Repository\PriantRepositoryInterface;
use App\Chain\Domain\ValueObject\DisplayName;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Domain\ValueObject\PriantId;
use App\Shared\Domain\Time\ParisCalendar;
use App\Shared\Domain\ValueObject\Email;
use App\Tests\Behat\BehatKernelTrait;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Uuid;

/**
 * Behat context for the public inscription flow (F09-T07 to F09-T13):
 * edition and priant fixtures, POST /api/inscriptions, the public planning
 * counters (RG-PL-1) and the admin RG-ED-3 start-time lock.
 */
final class InscriptionContext implements Context
{
    use BehatKernelTrait;

    #[Given('an open edition exists')]
    public function anOpenEditionExists(): void
    {
        $state = $this->state();

        /** @var EditionRepositoryInterface $editionRepository */
        $editionRepository = $this->testContainer()->get(EditionRepositoryInterface::class);

        // Same fixture as the acceptance tests: scheduled, inscriptions open,
        // every slot in the future.
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2099-01-01 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2020-01-01 00:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $edition->schedule(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        $editionRepository->add($edition);
        $state->entityManager?->flush();

        $state->capturedEditionId = $edition->getId()->value->toRfc4122();
        $state->capturedSlotIds = array_map(
            static fn (\App\Chain\Domain\Entity\Slot $slot): string => $slot->getId()->value->toRfc4122(),
            $edition->getSlots(),
        );
    }

    #[Given('a priant exists with email :email and display name :name')]
    public function aPriantExistsWithEmailAndDisplayName(string $email, string $name): void
    {
        $state = $this->state();

        /** @var PriantRepositoryInterface $priantRepository */
        $priantRepository = $this->testContainer()->get(PriantRepositoryInterface::class);

        $priant = new Priant(new Email($email), new DisplayName($name));
        $priantRepository->add($priant);
        $state->entityManager?->flush();

        $state->capturedPriantId = $priant->getId()->value->toRfc4122();
    }

    #[Given('the last created priant has given consent')]
    public function theLastCreatedPriantHasGivenConsent(): void
    {
        $state = $this->state();

        if (null === $state->capturedPriantId) {
            throw new \RuntimeException('No priant captured. Create a priant first.');
        }

        /** @var PriantRepositoryInterface $priantRepository */
        $priantRepository = $this->testContainer()->get(PriantRepositoryInterface::class);

        $priant = $priantRepository->findById(new PriantId($state->capturedPriantId));

        if (null === $priant) {
            throw new \RuntimeException('The captured priant no longer exists.');
        }

        $priant->giveConsent(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        $state->entityManager?->flush();
    }

    #[Given('this account is linked to the last created priant')]
    public function thisAccountIsLinkedToTheLastCreatedPriant(): void
    {
        $state = $this->state();

        if (null === $state->currentAccount || null === $state->capturedPriantId) {
            throw new \RuntimeException('No account or no priant available.');
        }

        // Re-fetch the account: the factory-built instance is not necessarily
        // managed by the entity manager handling the requests (same pattern as
        // "the account is deleted").
        $account = $state->entityManager?->getRepository(Account::class)
            ->find($state->currentAccount->getId()->value->toRfc4122());

        if (null === $account) {
            throw new \RuntimeException('The current account no longer exists.');
        }

        $account->updatePriantId($state->capturedPriantId);
        $state->entityManager?->flush();
    }

    #[When('I register on the first slot of the last created edition with body:')]
    public function iRegisterOnTheFirstSlotWithBody(PyStringNode $body): void
    {
        $this->registerWithSlots([0], $body);
    }

    #[When('I register on the first two slots of the last created edition with body:')]
    public function iRegisterOnTheFirstTwoSlotsWithBody(PyStringNode $body): void
    {
        $this->registerWithSlots([0, 1], $body);
    }

    #[When('I register on an unknown slot with body:')]
    public function iRegisterOnAnUnknownSlotWithBody(PyStringNode $body): void
    {
        $this->postRegistration([Uuid::v7()->toRfc4122()], $body);
    }

    #[When('I get the slots of the last created edition')]
    public function iGetTheSlotsOfTheLastCreatedEdition(): void
    {
        $state = $this->state();

        if (null === $state->capturedEditionId) {
            throw new \RuntimeException('No edition captured. Create an edition first.');
        }

        $this->handleRequest(Request::create('/api/editions/'.$state->capturedEditionId.'/slots', 'GET'));
    }

    #[When('I PATCH JSON-LD to the last created edition with body:')]
    public function iPatchJsonLdToTheLastCreatedEditionWithBody(PyStringNode $body): void
    {
        $state = $this->state();

        if (null === $state->capturedEditionId) {
            throw new \RuntimeException('No edition captured. Create an edition first.');
        }

        $request = Request::create('/api/admin/editions/'.$state->capturedEditionId, 'PATCH', [], [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ], $body->getRaw());
        $this->handleRequest($request);
    }

    #[Then('the response should contain :count inscriptions')]
    public function theResponseShouldContainInscriptions(int $count): void
    {
        $data = json_decode($this->state()->lastResponseContent ?? '', true);
        $inscriptions = \is_array($data) ? ($data['inscriptions'] ?? null) : null;

        if (!\is_array($inscriptions)) {
            throw new \RuntimeException('Response does not contain an inscriptions array');
        }

        if (\count($inscriptions) !== $count) {
            throw new \RuntimeException(\sprintf('Expected %d inscription(s), got %d', $count, \count($inscriptions)));
        }
    }

    #[Then('a priant should exist with email :email and display name :name')]
    public function aPriantShouldExistWithEmailAndDisplayName(string $email, string $name): void
    {
        $count = (int) $this->connection()->fetchOne(
            'SELECT COUNT(*) FROM priant WHERE email = :email AND display_name = :name',
            ['email' => $email, 'name' => $name],
        );

        if (0 === $count) {
            throw new \RuntimeException(\sprintf('Expected a priant "%s" with email "%s", got none', $name, $email));
        }
    }

    #[Then('the priant with email :email should still be named :name')]
    public function thePriantWithEmailShouldStillBeNamed(string $email, string $name): void
    {
        $actual = $this->connection()->fetchOne(
            'SELECT display_name FROM priant WHERE email = :email',
            ['email' => $email],
        );

        if ($actual !== $name) {
            throw new \RuntimeException(\sprintf('Expected the priant "%s" to still be named "%s", got "%s"', $email, $name, (string) $actual));
        }
    }

    #[Then('the account with email :email should be linked to the priant with email :priantEmail')]
    public function theAccountWithEmailShouldBeLinkedToThePriantWithEmail(string $email, string $priantEmail): void
    {
        $count = (int) $this->connection()->fetchOne(
            'SELECT COUNT(*) FROM account a JOIN priant p ON p.id = a.priant_id WHERE a.email = :email AND p.email = :priantEmail',
            ['email' => $email, 'priantEmail' => $priantEmail],
        );

        if (0 === $count) {
            throw new \RuntimeException(\sprintf('Expected the account "%s" to be linked to the priant "%s"', $email, $priantEmail));
        }
    }

    #[Then('exactly one account should exist')]
    public function exactlyOneAccountShouldExist(): void
    {
        $count = (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM account');

        if (1 !== $count) {
            throw new \RuntimeException(\sprintf('Expected exactly one account, got %d', $count));
        }
    }

    #[Then('the account table should be empty')]
    public function theAccountTableShouldBeEmpty(): void
    {
        $count = (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM account');

        if (0 !== $count) {
            throw new \RuntimeException(\sprintf('Expected no account, got %d', $count));
        }
    }

    #[Then('the priant table should be empty')]
    public function thePriantTableShouldBeEmpty(): void
    {
        $count = (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM priant');

        if (0 !== $count) {
            throw new \RuntimeException(\sprintf('Expected no priant, got %d', $count));
        }
    }

    #[Then('an inscription should exist on the first slot of the last created edition')]
    public function anInscriptionShouldExistOnTheFirstSlot(): void
    {
        $this->assertInscriptionCountOnFirstSlot(1);
    }

    #[Then('no inscription should exist on the first slot of the last created edition')]
    public function noInscriptionShouldExistOnTheFirstSlot(): void
    {
        $this->assertInscriptionCountOnFirstSlot(0);
    }

    #[Then('the response JSON key :key should be :expected')]
    public function theResponseJsonKeyShouldBe(string $key, string $expected): void
    {
        $data = json_decode($this->state()->lastResponseContent ?? '', true);

        if (!\is_array($data)) {
            throw new \RuntimeException('JSON response is not an object');
        }

        // The JSON-LD serializer skips null values: an absent key is a null value.
        if ('null' === $expected && !\array_key_exists($key, $data)) {
            return;
        }

        if (!\array_key_exists($key, $data)) {
            throw new \RuntimeException(\sprintf('JSON response does not contain key "%s"', $key));
        }

        $expectedValue = match ($expected) {
            'null' => null,
            'true' => true,
            'false' => false,
            default => $expected,
        };

        if ($data[$key] !== $expectedValue) {
            throw new \RuntimeException(\sprintf('Expected "%s" for key "%s", got %s', $expected, $key, var_export($data[$key], true)));
        }
    }

    /**
     * @param list<int> $slotIndexes
     */
    private function registerWithSlots(array $slotIndexes, PyStringNode $body): void
    {
        $state = $this->state();

        if ([] === $state->capturedSlotIds) {
            throw new \RuntimeException('No edition captured. Create an edition first.');
        }

        $slotIds = [];
        foreach ($slotIndexes as $index) {
            $slotIds[] = $state->capturedSlotIds[$index];
        }

        $this->postRegistration($slotIds, $body);
    }

    /**
     * @param list<string> $slotIds
     */
    private function postRegistration(array $slotIds, PyStringNode $body): void
    {
        $payload = json_decode($body->getRaw(), true);

        if (!\is_array($payload)) {
            throw new \RuntimeException('The registration body is not valid JSON.');
        }

        $payload['slotIds'] = $slotIds;

        $request = Request::create('/api/inscriptions', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ], (string) json_encode($payload, \JSON_THROW_ON_ERROR));

        $this->handleRequest($request);
    }

    private function assertInscriptionCountOnFirstSlot(int $expected): void
    {
        $state = $this->state();

        if ([] === $state->capturedSlotIds) {
            throw new \RuntimeException('No edition captured. Create an edition first.');
        }

        $count = (int) $this->connection()->fetchOne(
            'SELECT COUNT(*) FROM inscription WHERE slot_id = :slotId',
            ['slotId' => $state->capturedSlotIds[0]],
        );

        if ($count !== $expected) {
            throw new \RuntimeException(\sprintf('Expected %d inscription(s) on the first slot, got %d', $expected, $count));
        }
    }

    private function connection(): Connection
    {
        return $this->state()->entityManager?->getConnection()
            ?? throw new \RuntimeException('Kernel is not booted.');
    }

    private function testContainer(): \Symfony\Component\DependencyInjection\ContainerInterface
    {
        $container = $this->state()->kernel?->getContainer();

        if (null === $container) {
            throw new \RuntimeException('Kernel is not booted.');
        }

        return $container->get('test.service_container');
    }
}

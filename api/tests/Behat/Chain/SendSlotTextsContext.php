<?php

declare(strict_types=1);

namespace App\Tests\Behat\Chain;

use App\Chain\Application\CreateText;
use App\Chain\Application\CreateTextResult;
use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Entity\Priant;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\ValueObject\DisplayName;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\SlotId;
use App\Chain\Domain\ValueObject\TextId;
use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use App\Chain\Infrastructure\Symfony\Console\SendSlotTextsCommand;
use App\Shared\Application\Command\CommandBusInterface;
use App\Tests\Behat\BehatKernelTrait;
use App\Tests\Functional\Shared\Fixture\InMemoryMailTransport;
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\RawMessage;

/**
 * Behat context of the slot texts email command (F24)
 * (app:chain:send-slot-texts): one email per priant with an address, listing
 * the day and hour of each slot and its full text.
 */
final class SendSlotTextsContext implements Context
{
    use BehatKernelTrait;

    #[Given('the first slot of the last created edition carries the text :title')]
    public function theFirstSlotOfTheLastCreatedEditionCarriesTheText(string $title): void
    {
        $state = $this->state();

        if (null === $state->capturedEditionId || [] === $state->capturedSlotIds) {
            throw new \RuntimeException('No edition captured. Create an edition first.');
        }

        /** @var CommandBusInterface $commandBus */
        $commandBus = $this->testContainer()->get(CommandBusInterface::class);
        $result = $commandBus->dispatch(new CreateText(
            new TextTitle($title),
            new TextSource('Thérèse d’Avila'),
            \sprintf('<p>%s, que rien ne t’effraie.</p>', $title),
        ));

        if (!$result instanceof CreateTextResult) {
            throw new \RuntimeException(\sprintf('Expected the command bus to return a %s.', CreateTextResult::class));
        }

        /** @var EditionRepositoryInterface $editions */
        $editions = $this->testContainer()->get(EditionRepositoryInterface::class);
        $edition = $editions->findById(new EditionId($state->capturedEditionId));

        if (null === $edition) {
            throw new \RuntimeException('The captured edition no longer exists.');
        }

        $edition->assignText(new SlotId($state->capturedSlotIds[0]), new TextId($result->textId->value->toRfc4122()));
        $state->entityManager?->flush();
    }

    #[Given('a priant without e-mail has taken the first slot of the last created edition')]
    public function aPriantWithoutEmailHasTakenTheFirstSlot(): void
    {
        $state = $this->state();

        if (null === $state->capturedEditionId || [] === $state->capturedSlotIds) {
            throw new \RuntimeException('No edition captured. Create an edition first.');
        }

        $priant = new Priant(null, new DisplayName('Famille Sans E-mail'));
        $state->entityManager?->persist($priant);
        $state->entityManager?->persist(new Inscription(
            new EditionId($state->capturedEditionId),
            new SlotId($state->capturedSlotIds[0]),
            $priant->getId(),
        ));
        $state->entityManager?->flush();
    }

    #[When('I send the slot texts of the last created edition')]
    public function iSendTheSlotTextsOfTheLastCreatedEdition(): void
    {
        $state = $this->state();
        $this->runCommand(['--edition' => $state->capturedEditionId]);
    }

    #[When('I send the slot texts of the current edition')]
    public function iSendTheSlotTextsOfTheCurrentEdition(): void
    {
        $this->runCommand([]);
    }

    #[Then('a slot texts email is sent to :email')]
    public function aSlotTextsEmailIsSentTo(string $email): void
    {
        $this->slotTextsEmailSentTo($email);
    }

    #[Then('the slot texts email sent to :email mentions :content')]
    public function theSlotTextsEmailSentToMentions(string $email, string $content): void
    {
        $email = $this->slotTextsEmailSentTo($email);
        $body = $email->getTextBody() ?? '';

        if (!str_contains($body, $content)) {
            throw new \RuntimeException(\sprintf('The slot texts email does not mention "%s".', $content));
        }
    }

    #[Then('exactly :count slot texts email is sent to :email')]
    public function exactlyCountSlotTextsEmailIsSentTo(int $count, string $email): void
    {
        $transport = $this->mailTransport();
        $signature = static fn (RawMessage $message): bool => $message instanceof TemplatedEmail
            && \in_array($email, array_map(static fn (Address $address): string => $address->getAddress(), $message->getTo()), true)
            && str_starts_with((string) $message->getSubject(), 'Vos textes :');

        $matches = array_filter($transport->sent, $signature);

        if (\count($matches) !== $count) {
            throw new \RuntimeException(\sprintf('Expected %d slot texts email(s) sent to "%s", got %d.', $count, $email, \count($matches)));
        }
    }

    /**
     * @param array<string, string|null> $input
     */
    private function runCommand(array $input): void
    {
        $state = $this->state();
        $container = $state->kernel?->getContainer()->get('test.service_container');

        /** @var SendSlotTextsCommand $command */
        $command = $container?->get(SendSlotTextsCommand::class);

        $tester = new CommandTester($command);
        $state->lastCommandExitCode = $tester->execute($input);
        $state->lastCommandDisplay = $tester->getDisplay();
    }

    private function slotTextsEmailSentTo(string $recipient): TemplatedEmail
    {
        $transport = $this->mailTransport();

        $signature = static fn (RawMessage $message): bool => $message instanceof TemplatedEmail
            && \in_array($recipient, array_map(static fn (Address $address): string => $address->getAddress(), $message->getTo()), true)
            && str_starts_with((string) $message->getSubject(), 'Vos textes :');

        foreach ($transport->sent as $message) {
            if ($signature($message)) {
                return $message;
            }
        }

        throw new \RuntimeException(\sprintf('Expected a slot texts email ("Vos textes :…") sent to "%s", got %d email(s).', $recipient, \count($transport->sent)));
    }

    private function mailTransport(): InMemoryMailTransport
    {
        $transport = $this->testContainer()->get(InMemoryMailTransport::class);

        if (!$transport instanceof InMemoryMailTransport) {
            throw new \RuntimeException('The mail transport is not the in-memory test transport.');
        }

        return $transport;
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

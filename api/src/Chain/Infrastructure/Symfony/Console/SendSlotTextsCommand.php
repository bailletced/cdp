<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Symfony\Console;

use App\Chain\Application\Dto\SlotTextsEmail;
use App\Chain\Application\Port\Notifier;
use App\Chain\Application\Port\SlotTextsDeliveries;
use App\Chain\Application\Query\InscriptionDigestFinder;
use App\Chain\Application\Query\PublicEditionViewFinder;
use App\Chain\Application\Query\SlotTextsRecipientsFinder;
use App\Chain\Domain\ValueObject\EditionId;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sends by e-mail, to every priant of an edition who has an address, the
 * texts of all the slots they carry (F24): the day and hour of each slot,
 * with its full text. Default edition: the current open one (RG-ED-1).
 *
 * The command is idempotent: each delivered e-mail is recorded (SlotTexts
 * Deliveries), so running it again skips the priants already served.
 */
#[AsCommand(name: 'app:chain:send-slot-texts', description: 'Envoie à chaque priant de l’édition un e-mail avec les textes de tous ses créneaux (idempotent : un priant déjà servi ne reçoit rien)')]
final class SendSlotTextsCommand extends Command
{
    public function __construct(
        private SlotTextsRecipientsFinder $recipients,
        private InscriptionDigestFinder $digests,
        private Notifier $notifier,
        private PublicEditionViewFinder $currentEdition,
        private SlotTextsDeliveries $deliveries,
        private ClockInterface $clock,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addOption('edition', null, InputOption::VALUE_REQUIRED, 'Identifiant de l’édition visée (par défaut : l’édition ouverte)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $raw = $input->getOption('edition');

        if (null === $raw) {
            $current = $this->currentEdition->current();

            if (null === $current) {
                $output->writeln('<error>Aucune édition ouverte : précisez une édition avec --edition.</error>');

                return Command::FAILURE;
            }

            $editionId = new EditionId($current->id);
        } else {
            $identifier = \is_string($raw) ? $raw : '';

            try {
                $editionId = new EditionId($identifier);
            } catch (\InvalidArgumentException|\ValueError) {
                $output->writeln(\sprintf('<error>Identifiant d’édition invalide : %s</error>', $identifier));

                return Command::FAILURE;
            }
        }

        $recipients = $this->recipients->findByEdition($editionId);

        if ([] === $recipients) {
            $output->writeln('<comment>Aucun priant avec e-mail inscrit sur cette édition.</comment>');

            return Command::SUCCESS;
        }

        $sent = 0;
        $alreadySent = 0;
        foreach ($recipients as $recipient) {
            if ($this->deliveries->alreadySentTo($recipient->priantId, $editionId)) {
                ++$alreadySent;

                continue;
            }

            $digest = $this->digests->findByPriantAndEdition($recipient->priantId, $editionId);

            if (null === $digest) {
                $output->writeln(\sprintf('<comment>%s : aucun créneau, e-mail ignoré.</comment>', $recipient->displayName));

                continue;
            }

            $this->notifier->sendSlotTexts(new SlotTextsEmail(
                $recipient->email,
                $recipient->displayName,
                $digest,
            ));
            $this->deliveries->recordDelivery($recipient->priantId, $editionId, $this->clock->now());
            ++$sent;
        }

        if (0 < $alreadySent) {
            $already = 1 === $alreadySent ? '1 e-mail déjà envoyé' : \sprintf('%d e-mails déjà envoyés', $alreadySent);
            $output->writeln(\sprintf('<comment>%s.</comment>', $already));
        }

        $summary = match ($sent) {
            0 => '0 e-mail envoyé',
            1 => '1 e-mail envoyé',
            default => \sprintf('%d e-mails envoyés', $sent),
        };
        $output->writeln(\sprintf('<info>%s.</info>', $summary));

        return Command::SUCCESS;
    }
}

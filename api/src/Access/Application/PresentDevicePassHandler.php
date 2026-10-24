<?php

declare(strict_types=1);

namespace App\Access\Application;

use App\Access\Domain\Repository\DevicePassRepositoryInterface;
use App\Shared\Application\Command\AsCommandHandler;
use Psr\Clock\ClockInterface;

/**
 * Handles PresentDevicePass inside the command bus transaction (F10-T04).
 * An unknown or expired pass covers nothing.
 */
#[AsCommandHandler]
final readonly class PresentDevicePassHandler
{
    public function __construct(
        private DevicePassRepositoryInterface $devicePassRepository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return list<string> The inscriptions covered by the pass
     */
    public function __invoke(PresentDevicePass $command): array
    {
        $now = $this->clock->now();
        $pass = $this->devicePassRepository->findByToken($command->token);

        if (null === $pass || $pass->isExpiredAt($now)) {
            return [];
        }

        $pass->seenAt($now);

        return $pass->getInscriptionIds();
    }
}

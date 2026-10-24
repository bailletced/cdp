<?php

declare(strict_types=1);

namespace App\Access\Application;

use App\Access\Domain\Entity\DevicePass;
use App\Access\Domain\Repository\DevicePassRepositoryInterface;
use App\Shared\Application\Command\AsCommandHandler;
use Psr\Clock\ClockInterface;

/**
 * Handles GrantDevicePass inside the command bus transaction (F10-T02).
 *
 * Each inscription made from the device is added to its pass. A device
 * presenting an unknown or expired pass gets a new one: an expired pass is
 * never extended.
 */
#[AsCommandHandler]
final readonly class GrantDevicePassHandler
{
    public function __construct(
        private DevicePassRepositoryInterface $devicePassRepository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return string The token the device keeps
     */
    public function __invoke(GrantDevicePass $command): string
    {
        $now = $this->clock->now();
        $pass = null === $command->presentedToken ? null : $this->devicePassRepository->findByToken($command->presentedToken);

        if (null !== $command->presentedToken && null !== $pass && !$pass->isExpiredAt($now)) {
            $pass->cover($command->inscriptionIds, $command->expiresAt);

            return $command->presentedToken;
        }

        $this->devicePassRepository->add(new DevicePass($command->freshToken, $command->inscriptionIds, $command->expiresAt, $now));

        return $command->freshToken;
    }
}

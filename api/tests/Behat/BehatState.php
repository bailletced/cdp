<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Access\Domain\Account;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Shared mutable state across all Behat contexts within a scenario.
 * Stored as a static property in BehatKernelTrait.
 */
final class BehatState
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    public ?KernelInterface $kernel = null;
    public ?EntityManagerInterface $entityManager = null;
    public ?Account $currentAccount = null;
    public ?array $loginLinkParams = null;
    public array $responseCookies = [];
    public array $cookieJar = [];
    public ?string $lastResponseContent = null;
    public ?int $lastResponseStatus = null;
    public ?array $lastResponseHeaders = null;

    public function reset(): void
    {
        $this->kernel = null;
        $this->entityManager = null;
        $this->currentAccount = null;
        $this->loginLinkParams = null;
        $this->responseCookies = [];
        $this->cookieJar = [];
        $this->lastResponseContent = null;
        $this->lastResponseStatus = null;
        $this->lastResponseHeaders = null;
    }
}

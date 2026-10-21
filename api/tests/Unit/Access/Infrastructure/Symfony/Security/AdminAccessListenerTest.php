<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Infrastructure\Symfony\Security;

use App\Access\Infrastructure\Symfony\Security\AdminAccessListener;
use App\Access\Infrastructure\Symfony\Security\SecurityUser;
use App\Shared\Infrastructure\ApiPlatform\Exception\AccessDeniedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Uid\Uuid;

/**
 * F04-T02 : garde d'accès sur /api/admin/* — réservé aux organisateurs.
 * Le 401 visiteur est géré par la règle access_control de security.yaml
 * (^/api/admin, IS_AUTHENTICATED_REMEMBERED) et l'entry point du firewall ;
 * ce listener applique la règle métier : priant (non organisateur) → 403.
 */
final class AdminAccessListenerTest extends TestCase
{
    public function testVisitorIsIgnoredOnAdminPath(): void
    {
        // No exception: a visitor never reaches this listener, the
        // access_control rule answers 401 before (entry point of the firewall).
        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null);

        $listener = new AdminAccessListener($tokenStorage);

        $request = Request::create('/api/admin/organizers', 'GET');
        $event = new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $listener->onKernelRequest($event);

        $this->addToAssertionCount(1);
    }

    public function testPriantGets403OnAdminPath(): void
    {
        $user = new SecurityUser(Uuid::v7(), 'thomas@bailletced.org', '', false);

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $listener = new AdminAccessListener($tokenStorage);

        $request = Request::create('/api/admin/organizers', 'GET');
        $event = new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $this->expectException(AccessDeniedException::class);

        $listener->onKernelRequest($event);
    }

    public function testOrganizerIsAllowedOnAdminPath(): void
    {
        $user = new SecurityUser(Uuid::v7(), 'anne@bailletced.org', '', true);

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $listener = new AdminAccessListener($tokenStorage);

        $request = Request::create('/api/admin/organizers', 'GET');
        $event = new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $listener->onKernelRequest($event);

        $this->addToAssertionCount(1);
    }

    public function testNonAdminPathIsIgnored(): void
    {
        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null);

        $listener = new AdminAccessListener($tokenStorage);

        $request = Request::create('/api/me', 'GET');
        $event = new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $listener->onKernelRequest($event);

        $this->addToAssertionCount(1);
    }
}

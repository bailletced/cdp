<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Infrastructure\Security;

use App\Access\Infrastructure\Security\AdminAccessListener;
use App\Access\Infrastructure\Security\SecurityUser;
use App\Shared\Infrastructure\ApiPlatform\Exception\AccessDeniedException;
use App\Shared\Infrastructure\ApiPlatform\Exception\UnauthenticatedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Uid\Uuid;

/**
 * F04-T02 : garde d'accès sur /api/admin/* — réservé à ROLE_ORGANIZER.
 * Visiteur (pas de session) → 401, priant (non organisateur) → 403.
 */
final class AdminAccessListenerTest extends TestCase
{
    public function testVisitorGets401OnAdminPath(): void
    {
        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null);

        $listener = new AdminAccessListener($tokenStorage);

        $request = Request::create('/api/admin/organizers', 'GET');
        $event = new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $this->expectException(UnauthenticatedException::class);

        $listener->onKernelRequest($event);
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

<?php

declare(strict_types=1);

namespace App\Tests\Functional\Architecture;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use App\Access\Infrastructure\ApiPlatform\Me;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * Architecture test (F04-T03): no application resource outside /api/admin may
 * serialize an email address. Emails are back-office data (the organizers
 * list); exposing one outside the admin API is a privacy regression.
 *
 * The only allowed exception is Me (GET /api/me, F02-T16), which exposes the
 * currently authenticated account's own email back to that same account.
 *
 * Whether an operation is "admin" is decided from its exposed route path
 * (resolved through the router), using the same /api/admin prefix as
 * AdminAccessListener: API Platform generates format-suffix variants whose
 * uriTemplate no longer starts with /admin/ (e.g. /admin_organizers/{id}).
 */
final class NoEmailOutsideAdminResourceTest extends KernelTestCase
{
    /** Same prefix as AdminAccessListener: routes below it may expose emails. */
    private const ADMIN_PATH_PREFIX = '/api/admin';

    /** @var list<class-string> Resources allowed to serialize an email outside /api/admin. */
    private const EMAIL_ALLOWED_RESOURCES = [Me::class];

    public function testNoResourceOutsideAdminSerializesAnEmail(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        /** @var ResourceNameCollectionFactoryInterface $resourceNames */
        $resourceNames = $container->get(ResourceNameCollectionFactoryInterface::class);
        /** @var ResourceMetadataCollectionFactoryInterface $resourceMetadata */
        $resourceMetadata = $container->get(ResourceMetadataCollectionFactoryInterface::class);
        /** @var RouterInterface $router */
        $router = $container->get(RouterInterface::class);

        /** @var list<string> $violations */
        $violations = [];

        foreach ($resourceNames->create() as $resourceClass) {
            if (!str_starts_with($resourceClass, 'App\\')) {
                continue; // Audit application resources only.
            }

            foreach ($resourceMetadata->create($resourceClass) as $apiResource) {
                foreach ($apiResource->getOperations() ?? [] as $operation) {
                    if (!$operation instanceof HttpOperation) {
                        continue;
                    }

                    $path = $this->routePath($operation, $router);

                    if (null === $path || str_starts_with($path, self::ADMIN_PATH_PREFIX)) {
                        continue; // Not exposed, or admin: emails are allowed there.
                    }

                    $outputClass = $this->serializedClass($operation);

                    if (null === $outputClass || \in_array($outputClass, self::EMAIL_ALLOWED_RESOURCES, true)) {
                        continue;
                    }

                    foreach ($this->serializedPropertyNames($outputClass) as $propertyName) {
                        if (str_contains(strtolower($propertyName), 'email')) {
                            $violations[] = \sprintf(
                                '%s (%s %s) serializes an email field "%s". Emails must only be exposed under /api/admin.',
                                $resourceClass,
                                $operation->getMethod(),
                                $path,
                                $propertyName,
                            );
                        }
                    }
                }
            }
        }

        self::assertSame(
            [],
            $violations,
            "No resource outside /api/admin may serialize an email:\n - ".implode("\n - ", $violations),
        );
    }

    /**
     * Resolves the exposed route path of the operation through the router.
     *
     * @return string|null Null when the operation is not routed (e.g. NotExposed).
     */
    private function routePath(HttpOperation $operation, RouterInterface $router): ?string
    {
        $routeName = $operation->getName();

        if (null === $routeName) {
            return null;
        }

        return $router->getRouteCollection()->get($routeName)?->getPath();
    }

    /**
     * Resolves the class whose properties are serialized for this operation
     * (the output DTO when one is declared, the resource class otherwise).
     *
     * @return class-string|null null when the operation has no response body
     */
    private function serializedClass(Operation $operation): ?string
    {
        $output = $operation->getOutput();

        if (\is_string($output) && '' !== $output) {
            /* @var class-string $output */
            return $output;
        }

        if (\is_array($output) && isset($output['class']) && \is_string($output['class']) && '' !== $output['class']) {
            /** @var class-string $outputClass */
            $outputClass = $output['class'];

            return $outputClass;
        }

        if (false === $output) {
            return null;
        }

        $class = $operation->getClass();

        if (null === $class || !class_exists($class)) {
            return null;
        }

        return $class;
    }

    /**
     * Lists the property names a serializer can read on the given class:
     * public properties plus getter methods (getX, isX, hasX).
     *
     * @param class-string $class
     *
     * @return list<string>
     */
    private function serializedPropertyNames(string $class): array
    {
        /** @var list<string> $names */
        $names = [];
        $reflection = new ReflectionClass($class);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $names[] = $property->getName();
        }

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() || $method->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            $methodName = $method->getName();

            foreach (['get', 'is', 'has'] as $prefix) {
                if (str_starts_with($methodName, $prefix) && \strlen($methodName) > \strlen($prefix)) {
                    $names[] = lcfirst(substr($methodName, \strlen($prefix)));
                }
            }
        }

        return array_values(array_unique($names));
    }
}

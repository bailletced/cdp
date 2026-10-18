<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ApiPlatform\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Normalise les réponses d'erreur et ajoute les en-têtes Cache-Control.
 *
 * - 422 : violations[] avec propertyPath (déjà géré par API Platform)
 * - 409 : erreurs d'unicité et verrou optimiste (via ConflictException)
 * - 403 : erreurs d'autorisation (via AccessDeniedException)
 * - Cache-Control: private, no-store par défaut
 */
final class ExceptionSubscriber implements EventSubscriberInterface
{
    private const string DEFAULT_CACHE_CONTROL = 'private, no-store';

    private const string SYMFONY_DEFAULT_CACHE_CONTROL = 'no-cache, private';

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['addCacheControlHeader', 255],
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    /**
     * Ajoute l'en-tête Cache-Control par défaut sur toutes les réponses.
     * Ne pas écraser si déjà défini explicitement (autre valeur que celle par défaut de Symfony).
     */
    public function addCacheControlHeader(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        $currentCacheControl = $response->headers->get('Cache-Control');

        // Si pas de Cache-Control, ou si c'est la valeur par défaut de Symfony, on la remplace
        if (null === $currentCacheControl
            || self::SYMFONY_DEFAULT_CACHE_CONTROL === $currentCacheControl) {
            $response->headers->set('Cache-Control', self::DEFAULT_CACHE_CONTROL);
        }
    }

    /**
     * Normalise les réponses d'erreur.
     *
     * Les erreurs de validation (422) sont déjà gérées par API Platform avec le bon format.
     * On s'assure juste que les exceptions personnalisées retournent le bon code HTTP.
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        // API Platform gère déjà les ValidationException avec le format violations[]
        // On ne fait rien ici pour l'instant, car le format est déjà correct
        // Si besoin de personnalisation, on pourrait normaliser la réponse ici
    }
}

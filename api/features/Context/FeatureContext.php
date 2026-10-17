<?php

declare(strict_types=1);

namespace App\Features\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Gherkin\Node\ScenarioInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Context principal pour les tests Behat.
 * Contient des步骤 utilitaires et gère l'initialisation.
 */
class FeatureContext implements Context
{
    private ?KernelInterface $kernel = null;
    private ?EntityManagerInterface $entityManager = null;
    private array $generatedEntities = [];

    /**
     * @BeforeScenario
     */
    public function beforeScenario(BeforeScenarioScope $scope): void
    {
        // Initialiser le kernel Symfony
        $this->kernel = new \App\Kernel($_ENV['APP_ENV'] ?? 'test', (bool)$_ENV['APP_DEBUG'] ?? false);
        $this->kernel->boot();
        
        $this->entityManager = $this->kernel->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * @AfterScenario
     */
    public function afterScenario(AfterScenarioScope $scope): void
    {
        // Nettoyer les entités créées pendant le scénario
        foreach ($this->generatedEntities as $entity) {
            $this->entityManager?->remove($entity);
        }
        $this->entityManager?->flush();
        
        // Fermer le kernel
        $this->kernel?->shutdown();
        $this->generatedEntities = [];
    }

    /**
     * Retourne le container Symfony.
     */
    public function getContainer(): \Symfony\Component\DependencyInjection\ContainerInterface
    {
        return $this->kernel->getContainer();
    }

    /**
     * Retourne l'EntityManager.
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * Enregistre une entité pour nettoyage après le scénario.
     */
    public function registerEntityForCleanup(object $entity): void
    {
        $this->generatedEntities[] = $entity;
    }

    /**
     * @Given le système est initialisé
     */
    public function systemIsInitialized(): void
    {
        // Le kernel est déjà initialisé dans BeforeScenario
        // Ce step est juste pour la lisibilité des features
    }
}

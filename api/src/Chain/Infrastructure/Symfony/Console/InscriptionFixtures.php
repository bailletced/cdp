<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Symfony\Console;

/**
 * F09-T20: demonstration priants of the open edition and the slots (ranks)
 * they take — a family on several slots, priants registered by the
 * organizers without email, shared slots, and slots still waiting for
 * someone. The addresses use the reserved example.org domain.
 */
final class InscriptionFixtures
{
    /**
     * @return list<array{displayName: string, email: ?string, ranks: list<int>}>
     */
    public static function all(): array
    {
        return [
            ['displayName' => 'Marie', 'email' => 'marie@example.org', 'ranks' => [1, 2]],
            ['displayName' => 'Famille Martin', 'email' => 'famille.martin@example.org', 'ranks' => [1, 14, 15, 16]],
            ['displayName' => 'Thomas', 'email' => 'thomas@example.org', 'ranks' => [3]],
            ['displayName' => 'Paroisse Saint-Joseph', 'email' => 'saint-joseph@example.org', 'ranks' => [4, 5, 6, 7]],
            ['displayName' => 'Sœur Anne-Marie', 'email' => null, 'ranks' => [8, 9]],
            ['displayName' => 'Famille Bernard', 'email' => null, 'ranks' => [24]],
            ['displayName' => 'Jean-Baptiste', 'email' => 'jean-baptiste@example.org', 'ranks' => [24, 25, 26]],
            ['displayName' => 'Claire', 'email' => 'claire@example.org', 'ranks' => [33, 34]],
            ['displayName' => 'Famille Rousseau', 'email' => 'famille.rousseau@example.org', 'ranks' => [40, 41]],
            ['displayName' => 'Odile', 'email' => 'odile@example.org', 'ranks' => [47, 48]],
            ['displayName' => 'Pierre', 'email' => null, 'ranks' => [48]],
        ];
    }
}

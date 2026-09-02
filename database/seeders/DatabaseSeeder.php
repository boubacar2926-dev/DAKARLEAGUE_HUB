<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * CompetitionDemoSeeder crée un compte Super Administrateur avec un mot de passe par
     * défaut (celui de UserFactory) et un e-mail prévisible : il ne doit jamais être exécuté
     * en dehors d'un environnement local/test, sous peine de laisser un compte à privilèges
     * avec des identifiants faibles/connus sur un environnement accessible publiquement.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Seeding de démonstration ignoré (environnement non local) : CompetitionDemoSeeder crée des comptes avec des mots de passe par défaut.');

            return;
        }

        $this->call([
            CompetitionDemoSeeder::class,
        ]);
    }
}

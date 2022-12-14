<?php

namespace App\DataFixtures;

use App\Entity\Candidate;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker;

class CandidateFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    // Création de 150 faux candidats et leurs utilisateurs
    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');

        for ($i = 0; $i < 100; $i++) {
            $candidate = new Candidate();
            $candidate->setFirstname($faker->firstName());
            $candidate->setLastname($faker->lastName());

            // Ajout des utilisateurs
            $user = new User();
            $user->setEmail($faker->unique()->companyEmail());
            $user->setActive(true);
            $user->setRoles(['ROLE_CANDIDATE']);
            $password = $this->hasher->hashPassword($user, '123456789');
            $user->setPassword($password);

            $candidate->setUser($user);

            $manager->persist($candidate);
        }

        $manager->flush();
    }

}

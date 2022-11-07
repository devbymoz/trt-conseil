<?php

namespace App\DataFixtures;

use App\Entity\Company;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker;


class CompanyFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    // Création de 93 fausses entreprise et leurs utilisateurs
    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');

        for ($i = 0; $i < 93; $i++) {
            $company = new Company();
            $company->setName($faker->unique()->company());
            $company->setAddress($faker->address());

            // Ajout des utilisateurs
            $user = new User();
            $user->setEmail($faker->unique()->companyEmail());
            $user->setActive(true);
            $user->setRoles(['ROLE_COMPANY']);
            $password = $this->hasher->hashPassword($user, '123456789');
            $user->setPassword($password);

            $company->setUser($user);

            $manager->persist($company);
            $this->addReference('company'. $i, $company);
        }
        $manager->flush();
    }


    
}

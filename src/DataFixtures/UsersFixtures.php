<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UsersFixtures extends Fixture
{

    private User $user;
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    // Création de faux utilisateurs
    public function load(ObjectManager $manager): void
    {
        // Création d'ADMIN
        $userAdmin = $this->createUser();
        $userAdmin->setEmail('admin@trt.fr');
        $userAdmin->setRoles(['ROLE_ADMIN']);
        $manager->persist($userAdmin);

        // Création d'un CONSULTANT
        $userConsultant = $this->createUser();
        $userConsultant->setEmail('consultant@trt.fr');
        $userConsultant->setRoles(['ROLE_CONSULTANT']);
        $manager->persist($userConsultant);
        
        $manager->flush();
    }

    public function createUser(): User
    {
        $faker = Faker\Factory::create('fr_FR');

        // Création de nouvel utilisateur
        $this->user = new User();
        $this->user->setEmail($faker->unique()->companyEmail());
        $this->user->setActive(true);
        $password = $this->hasher->hashPassword($this->user, '123456789');
        $this->user->setPassword($password);

        return $this->user;
    }

}

<?php

namespace App\DataFixtures;

use App\Entity\Ad;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Faker;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class AdFixtures extends Fixture implements DependentFixtureInterface
{
    // Création de 106 offres d'emploi
    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');

        for ($i = 0; $i < 106; $i++) {
            $ad = new Ad();
            $ad->setName($faker->jobTitle());
            $ad->setDescription($faker->text(100));
            $ad->setAddress($faker->address());
            $ad->setSalary($faker->randomNumber(4, true));

            $hourly = 'De ' . rand(6, 9) . 'h à ' . rand(17, 20) . 'h';
            $ad->setHourly($hourly);
            $ad->setActive(rand(0,1));

            // On attribue une entreprise aux annonces
            $rand = rand(0, 92);
            $ad->setCompany($this->getReference('company' . $rand));

            $manager->persist($ad); 
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            CompanyFixtures::class
        ];
    }
}

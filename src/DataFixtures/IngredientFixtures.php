<?php

namespace App\DataFixtures;

use App\Entity\Ingredient;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class IngredientFixtures extends Fixture implements FixtureGroupInterface
{
    public const REF_PREFIX = 'ingredient_';
    private const COUNT = 30;

    public static function getGroups(): array
    {
        return ['demo'];
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $units = ['g', 'ml', 'pièce', 'c.à.s', 'c.à.c'];

        for ($i = 0; $i < self::COUNT; $i++) {
            $ing = new Ingredient();
            $ing->setName($faker->unique()->words(mt_rand(1, 2), true)); // ex: "farine", "sucre roux"
            $ing->setUnit($faker->randomElement($units));
            $ing->setCreatedAt(new \DateTimeImmutable());

            $manager->persist($ing);
            $this->addReference(self::REF_PREFIX.$i, $ing);
        }

        $manager->flush();
    }
}

<?php

namespace App\DataFixtures;

use App\Entity\Recipe;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class RecipeFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    private const COUNT = 20;

    public static function getGroups(): array
    {
        return ['demo'];
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
            IngredientFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // compter les refs existantes
        $categoryCount = 6;   // on en a créé 6 dans CategoryFixtures
        $ingredientCount = 30; // idem IngredientFixtures

        for ($i = 0; $i < self::COUNT; $i++) {
            $r = new Recipe();
            $r->setTitle($faker->sentence(3));
            $r->setDescription($faker->optional()->paragraph());
            $r->setInstructions($faker->paragraphs(mt_rand(2, 5), true));
            $r->setPrepTime($faker->optional()->numberBetween(10, 120));
            $r->setDifficulty($faker->optional()->numberBetween(1, 5));
            $r->setCreatedAt(new \DateTimeImmutable());

            // Catégorie obligatoire (ManyToOne, nullable=false)
            $catIndex = random_int(0, $categoryCount - 1);
            /** @var \App\Entity\Category $category */
            $category = $this->getReference(CategoryFixtures::REF_PREFIX.$catIndex, \App\Entity\Category::class);
            $r->setCategory($category);

            // Ingrédients (2 à 6, sans doublon)
            $wanted = random_int(2, 6);
            $used = [];
            while (count($used) < $wanted) {
                $ingIndex = random_int(0, $ingredientCount - 1);
                if (!in_array($ingIndex, $used, true)) {
                    $used[] = $ingIndex;
                    /** @var \App\Entity\Ingredient $ing */
                    $ing = $this->getReference(IngredientFixtures::REF_PREFIX.$ingIndex, \App\Entity\Ingredient::class);
                    $r->addIngredient($ing);
                }
            }

            $manager->persist($r);
        }

        $manager->flush();
    }
}

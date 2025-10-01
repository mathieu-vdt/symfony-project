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
            UserFixtures::class, // ✅ add this
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $categoryCount = 6;
        $ingredientCount = 30;
        $userCount = 10;

        for ($i = 0; $i < self::COUNT; $i++) {
            $r = new Recipe();
            $r->setTitle($faker->sentence(3));
            $r->setDescription($faker->optional()->paragraph());
            $r->setInstructions($faker->paragraphs(mt_rand(2, 5), true));
            $r->setPrepTime($faker->optional()->numberBetween(10, 120));
            $r->setDifficulty($faker->optional()->numberBetween(1, 5));
            $r->setCreatedAt(new \DateTimeImmutable());

            // Category
            $catIndex = random_int(0, $categoryCount - 1);
            $category = $this->getReference(CategoryFixtures::REF_PREFIX.$catIndex, \App\Entity\Category::class);
            $r->setCategory($category);

            // Ingredients
            $wanted = random_int(2, 6);
            $used = [];
            while (count($used) < $wanted) {
                $ingIndex = random_int(0, $ingredientCount - 1);
                if (!in_array($ingIndex, $used, true)) {
                    $used[] = $ingIndex;
                    $ing = $this->getReference(IngredientFixtures::REF_PREFIX.$ingIndex, \App\Entity\Ingredient::class);
                    $r->addIngredient($ing);
                }
            }

            // Author
            $userIndex = random_int(0, $userCount - 1);
            $author = $this->getReference(UserFixtures::REF_PREFIX.$userIndex, \App\Entity\User::class);
            $r->setAuthor($author);

            $manager->persist($r);
        }

        $manager->flush();
    }

}

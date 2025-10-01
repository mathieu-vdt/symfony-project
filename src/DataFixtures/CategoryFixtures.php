<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\AsciiSlugger;

class CategoryFixtures extends Fixture implements FixtureGroupInterface
{
    public const REF_PREFIX = 'category_';

    public static function getGroups(): array
    {
        return ['demo'];
    }

    public function load(ObjectManager $manager): void
    {
        $labels = ['Starters', 'Main courses', 'Desserts', 'Drinks', 'Vegan', 'Gluten-free'];
        $slugger = new AsciiSlugger();

        foreach ($labels as $i => $label) {
            $c = new Category();
            $c->setName($label);
            $c->setSlug(strtolower($slugger->slug($label)));
            $c->setCreatedAt(new \DateTimeImmutable());

            $manager->persist($c);
            // reference for RecipeFixtures
            $this->addReference(self::REF_PREFIX.$i, $c);
        }

        $manager->flush();
    }
}

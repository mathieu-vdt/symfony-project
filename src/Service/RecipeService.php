<?php

namespace App\Service;

use App\Entity\Recipe;
use Doctrine\ORM\EntityManagerInterface;

class RecipeService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    /** Crée une recette (set createdAt si manquant) */
    public function create(Recipe $recipe): void
    {
        if (null === $recipe->getCreatedAt()) {
            $recipe->setCreatedAt(new \DateTimeImmutable());
        }

        $this->em->persist($recipe);
        $this->em->flush();
    }

    /** Met à jour une recette déjà managée */
    public function update(Recipe $recipe): void
    {
        // ici tu peux ajouter une logique (ex: updatedAt si tu l’ajoutes)
        $this->em->flush();
    }

    /** Supprime une recette */
    public function delete(Recipe $recipe): void
    {
        $this->em->remove($recipe);
        $this->em->flush();
    }
}

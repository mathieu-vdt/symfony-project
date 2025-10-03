<?php

namespace App\Controller\Api;

use App\Entity\Recipe;
use App\Repository\RecipeRepository;
use App\Security\Voter\RecipeVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/recipes', name: 'api_recipe_')]
class RecipeApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(RecipeRepository $recipeRepository): JsonResponse
    {
        $recipes = $recipeRepository->findAll();
        
        return $this->json($recipes, Response::HTTP_OK, [], [
            'groups' => ['recipe:read']
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[IsGranted(RecipeVoter::VIEW, 'recipe')]
    public function show(Recipe $recipe): JsonResponse
    {
        return $this->json($recipe, Response::HTTP_OK, [], [
            'groups' => ['recipe:read', 'recipe:details']
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted(RecipeVoter::CREATE)]
    public function create(Request $request): JsonResponse
    {
        try {
            $recipe = $this->serializer->deserialize(
                $request->getContent(),
                Recipe::class,
                'json',
                ['groups' => ['recipe:write']]
            );

            $recipe->setAuthor($this->getUser());
            $recipe->setCreatedAt(new \DateTimeImmutable());

            $errors = $this->validator->validate($recipe);
            if (count($errors) > 0) {
                return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($recipe);
            $this->entityManager->flush();

            return $this->json($recipe, Response::HTTP_CREATED, [], [
                'groups' => ['recipe:read']
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Invalid JSON data',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[IsGranted(RecipeVoter::EDIT, 'recipe')]
    public function update(Request $request, Recipe $recipe): JsonResponse
    {
        try {
            $this->serializer->deserialize(
                $request->getContent(),
                Recipe::class,
                'json',
                [
                    'object_to_populate' => $recipe,
                    'groups' => ['recipe:write']
                ]
            );

            $recipe->setUpdatedAt(new \DateTimeImmutable());

            $errors = $this->validator->validate($recipe);
            if (count($errors) > 0) {
                return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->flush();

            return $this->json($recipe, Response::HTTP_OK, [], [
                'groups' => ['recipe:read']
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Invalid JSON data',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted(RecipeVoter::DELETE, 'recipe')]
    public function delete(Recipe $recipe): JsonResponse
    {
        $this->entityManager->remove($recipe);
        $this->entityManager->flush();

        return $this->json(['message' => 'Recipe deleted successfully'], Response::HTTP_OK);
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request, RecipeRepository $recipeRepository): JsonResponse
    {
        $query = $request->query->get('q', '');
        $category = $request->query->get('category');
        $difficulty = $request->query->get('difficulty');
        $time = $request->query->get('time');

        $criteria = [];
        if ($query) {
            $criteria['query'] = $query;
        }
        if ($category) {
            $criteria['category'] = $category;
        }
        if ($difficulty !== null) {
            $criteria['difficulty'] = (int) $difficulty;
        }
        if ($time) {
            $criteria['maxPrepTime'] = (int) $time;
        }

        $recipes = $recipeRepository->searchAdvanced($criteria);

        return $this->json($recipes, Response::HTTP_OK, [], [
            'groups' => ['recipe:read']
        ]);
    }

    #[Route('/my', name: 'my_recipes', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function myRecipes(RecipeRepository $recipeRepository): JsonResponse
    {
        $recipes = $recipeRepository->findBy(['author' => $this->getUser()]);

        return $this->json($recipes, Response::HTTP_OK, [], [
            'groups' => ['recipe:read']
        ]);
    }
}
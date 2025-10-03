<?php

namespace App\GraphQL\Resolver;

use App\Entity\User;
use App\Repository\UserRepository;

class UserResolver
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function findAll(): array
    {
        return $this->userRepository->findAll();
    }

    public function findById(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    public function getRecipeCount(User $user): int
    {
        return $user->getRecipes()->count();
    }
}
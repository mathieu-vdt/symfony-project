<?php

namespace App\GraphQL\Resolver;

use App\Entity\Category;
use App\Repository\CategoryRepository;

class CategoryResolver
{
    public function __construct(
        private CategoryRepository $categoryRepository
    ) {}

    public function findAll(): array
    {
        return $this->categoryRepository->findAll();
    }

    public function findById(int $id): ?Category
    {
        return $this->categoryRepository->find($id);
    }
}
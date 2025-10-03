<?php

namespace App\Tests\Controller;

use App\Entity\Recipe;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class RecipeControllerSecurityTest extends DatabaseTestCase
{
    private const RECIPES_NEW_PATH = '/recipes/new';
    private const FORM_RECIPE_SELECTOR = 'form[name="recipe"]';
    private const OWNER_EMAIL = 'owner@example.com';
    
    private $client;

    protected function setUp(): void
    {
        parent::setUp(); // This calls the DatabaseTestCase setUp
        $this->client = static::createClient();
    }

    public function testAnonymousUserCannotAccessCreatePage(): void
    {
        $this->client->request('GET', self::RECIPES_NEW_PATH);
        
        // Should redirect to login
        $this->assertResponseRedirects('/login');
    }

    public function testAnonymousUserCannotAccessEditPage(): void
    {
        $recipe = $this->createTestRecipe();
        
        $this->client->request('GET', "/recipes/{$recipe->getId()}/edit");
        
        // Should redirect to login
        $this->assertResponseRedirects('/login');
    }

    public function testStudentCanAccessCreatePage(): void
    {
        $user = $this->createUserWithRole('ROLE_STUDENT');
        $this->client->loginUser($user);
        
        $this->client->request('GET', self::RECIPES_NEW_PATH);
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists(self::FORM_RECIPE_SELECTOR);
    }

    public function testUserCanEditOwnRecipe(): void
    {
        $user = $this->createUserWithRole('ROLE_STUDENT');
        $recipe = $this->createTestRecipe($user);
        
        $this->client->loginUser($user);
        $this->client->request('GET', "/recipes/{$recipe->getId()}/edit");
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists(self::FORM_RECIPE_SELECTOR);
    }

    public function testUserCannotEditOthersRecipe(): void
    {
        $owner = $this->createUserWithRole('ROLE_STUDENT', self::OWNER_EMAIL);
        $otherUser = $this->createUserWithRole('ROLE_STUDENT', 'other@example.com');
        $recipe = $this->createTestRecipe($owner);
        
        $this->client->loginUser($otherUser);
        $this->client->request('GET', "/recipes/{$recipe->getId()}/edit");
        
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testModeratorCanEditAnyRecipe(): void
    {
        $owner = $this->createUserWithRole('ROLE_STUDENT', self::OWNER_EMAIL);
        $moderator = $this->createUserWithRole('ROLE_MODERATOR', 'moderator@example.com');
        $recipe = $this->createTestRecipe($owner);
        
        $this->client->loginUser($moderator);
        $this->client->request('GET', "/recipes/{$recipe->getId()}/edit");
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists(self::FORM_RECIPE_SELECTOR);
    }

    public function testAdminCanEditAnyRecipe(): void
    {
        $owner = $this->createUserWithRole('ROLE_STUDENT', self::OWNER_EMAIL);
        $admin = $this->createUserWithRole('ROLE_ADMIN', 'admin@example.com');
        $recipe = $this->createTestRecipe($owner);
        
        $this->client->loginUser($admin);
        $this->client->request('GET', "/recipes/{$recipe->getId()}/edit");
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists(self::FORM_RECIPE_SELECTOR);
    }

    public function testUserCanDeleteOwnRecipe(): void
    {
        $user = $this->createUserWithRole('ROLE_STUDENT');
        $recipe = $this->createTestRecipe($user);
        
        $this->client->loginUser($user);
        $this->client->request('DELETE', "/recipes/{$recipe->getId()}");
        
        // Should redirect after successful deletion
        $this->assertResponseRedirects();
    }

    public function testUserCannotDeleteOthersRecipe(): void
    {
        $owner = $this->createUserWithRole('ROLE_STUDENT', self::OWNER_EMAIL);
        $otherUser = $this->createUserWithRole('ROLE_STUDENT', 'other@example.com');
        $recipe = $this->createTestRecipe($owner);
        
        $this->client->loginUser($otherUser);
        $this->client->request('DELETE', "/recipes/{$recipe->getId()}");
        
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testModeratorCanOnlyDeleteOwnRecipe(): void
    {
        $owner = $this->createUserWithRole('ROLE_STUDENT', self::OWNER_EMAIL);
        $moderator = $this->createUserWithRole('ROLE_MODERATOR', 'moderator@example.com');
        $recipe = $this->createTestRecipe($owner);
        
        $this->client->loginUser($moderator);
        $this->client->request('DELETE', "/recipes/{$recipe->getId()}");
        
        // Moderators cannot delete others' recipes (based on voter logic)
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAdminCanDeleteAnyRecipe(): void
    {
        $owner = $this->createUserWithRole('ROLE_STUDENT', self::OWNER_EMAIL);
        $admin = $this->createUserWithRole('ROLE_ADMIN', 'admin@example.com');
        $recipe = $this->createTestRecipe($owner);
        
        $this->client->loginUser($admin);
        $this->client->request('DELETE', "/recipes/{$recipe->getId()}");
        
        // Should redirect after successful deletion
        $this->assertResponseRedirects();
    }

    public function testAnyLoggedUserCanViewRecipes(): void
    {
        $owner = $this->createUserWithRole('ROLE_STUDENT', self::OWNER_EMAIL);
        $viewer = $this->createUserWithRole('ROLE_USER', 'viewer@example.com');
        $recipe = $this->createTestRecipe($owner);
        
        $this->client->loginUser($viewer);
        $this->client->request('GET', "/recipes/{$recipe->getId()}");
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', $recipe->getTitle());
    }

    public function testRegularUserCannotCreateRecipes(): void
    {
        $user = $this->createUserWithRole('ROLE_USER'); // Only ROLE_USER, no ROLE_STUDENT
        
        $this->client->loginUser($user);
        $this->client->request('GET', self::RECIPES_NEW_PATH);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    private function createUserWithRole(string $role, string $email = 'test@example.com'): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles([$role]);
        $user->setPassword('password'); // In real app this would be hashed
        $user->setIsVerified(true);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }

    private function createTestRecipe(?User $author = null): Recipe
    {
        if (!$author) {
            $author = $this->createUserWithRole('ROLE_STUDENT');
        }
        
        $recipe = new Recipe();
        $recipe->setTitle('Test Recipe');
        $recipe->setDescription('Test Description');
        $recipe->setInstructions('Test Instructions');
        $recipe->setPrepTime(30);
        $recipe->setDifficulty(3);
        $recipe->setAuthor($author);
        $recipe->setCreatedAt(new \DateTimeImmutable());
        $recipe->setUpdatedAt(new \DateTimeImmutable());
        
        $this->entityManager->persist($recipe);
        $this->entityManager->flush();
        
        return $recipe;
    }

}
<?php

namespace App\Tests\Security\Voter;

use App\Entity\Recipe;
use App\Entity\User;
use App\Security\Voter\RecipeVoter;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class RecipeVoterTest extends TestCase
{
    private RecipeVoter $voter;
    private Security|MockObject $security;
    private TokenInterface|MockObject $token;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->token = $this->createMock(TokenInterface::class);
        $this->voter = new RecipeVoter($this->security);
    }

    public function testDenyAccessWithoutUser(): void
    {
        $this->token->method('getUser')->willReturn(null);
        $recipe = $this->createMock(Recipe::class);
        
        $result = $this->voter->vote($this->token, $recipe, [RecipeVoter::VIEW]);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAdminHasFullAccess(): void
    {
        $user = $this->createUser(1, 'admin');
        $recipe = $this->createRecipeWithAuthor(2, 'other_user');
        
        $this->token->method('getUser')->willReturn($user);
        $this->security->method('isGranted')->with('ROLE_ADMIN')->willReturn(true);

        $result = $this->voter->vote($this->token, $recipe, [RecipeVoter::EDIT]);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testUserCanViewAnyRecipe(): void
    {
        $user = $this->createUser(1, 'user');
        $recipe = $this->createRecipeWithAuthor(2, 'other_user');
        
        $this->token->method('getUser')->willReturn($user);
        $this->security->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);

        $result = $this->voter->vote($this->token, $recipe, [RecipeVoter::VIEW]);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testStudentCanCreateRecipe(): void
    {
        $user = $this->createUser(1, 'student');
        
        $this->token->method('getUser')->willReturn($user);
        $this->security->method('isGranted')->willReturnMap([
            ['ROLE_ADMIN', null, false],
            ['ROLE_STUDENT', null, true],
        ]);

        $result = $this->voter->vote($this->token, null, [RecipeVoter::CREATE]);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testRegularUserCannotCreateRecipe(): void
    {
        $user = $this->createUser(1, 'user');
        
        $this->token->method('getUser')->willReturn($user);
        $this->security->method('isGranted')->willReturnMap([
            ['ROLE_ADMIN', null, false],
            ['ROLE_STUDENT', null, false],
            ['ROLE_MODERATOR', null, false],
        ]);

        $result = $this->voter->vote($this->token, null, [RecipeVoter::CREATE]);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testUserCanEditOwnRecipe(): void
    {
        $userId = 1;
        $user = $this->createUser($userId, 'student');
        $recipe = $this->createRecipeWithAuthor($userId, 'student');
        
        $this->token->method('getUser')->willReturn($user);
        $this->security->method('isGranted')->willReturnMap([
            ['ROLE_ADMIN', null, false],
            ['ROLE_MODERATOR', null, false],
        ]);

        $result = $this->voter->vote($this->token, $recipe, [RecipeVoter::EDIT]);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testUserCannotEditOthersRecipe(): void
    {
        $user = $this->createUser(1, 'student');
        $recipe = $this->createRecipeWithAuthor(2, 'other_user');
        
        $this->token->method('getUser')->willReturn($user);
        $this->security->method('isGranted')->willReturnMap([
            ['ROLE_ADMIN', null, false],
            ['ROLE_MODERATOR', null, false],
        ]);

        $result = $this->voter->vote($this->token, $recipe, [RecipeVoter::EDIT]);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testModeratorCanEditAllRecipes(): void
    {
        $user = $this->createUser(1, 'moderator');
        $recipe = $this->createRecipeWithAuthor(2, 'other_user');
        
        $this->token->method('getUser')->willReturn($user);
        $this->security->method('isGranted')->willReturnMap([
            ['ROLE_ADMIN', null, false],
            ['ROLE_MODERATOR', null, true],
        ]);

        $result = $this->voter->vote($this->token, $recipe, [RecipeVoter::EDIT]);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    private function createUser(int $id, string $username): User|MockObject
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);
        $user->method('getUsername')->willReturn($username);
        return $user;
    }

    private function createRecipeWithAuthor(int $authorId, string $authorUsername): Recipe|MockObject
    {
        $author = $this->createUser($authorId, $authorUsername);
        
        $recipe = $this->createMock(Recipe::class);
        $recipe->method('getAuthor')->willReturn($author);
        
        return $recipe;
    }
}
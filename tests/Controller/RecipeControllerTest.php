<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RecipeControllerTest extends WebTestCase
{
    public function testRecipeRoutesExist(): void
    {
        $client = static::createClient();
        
        // Test that the recipe new page redirects to login (because it requires authentication)
        $client->request('GET', '/recipes/new');
        $this->assertResponseRedirects('/login');
        
        // This is a simple test that doesn't require database access
        $this->assertTrue(true);
    }
}

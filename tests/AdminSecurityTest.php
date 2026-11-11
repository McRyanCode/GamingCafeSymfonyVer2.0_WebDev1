<?php

namespace App\Tests;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminSecurityTest extends WebTestCase
{
    public function testAnonymousAccessToAdminRedirectsToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        $this->assertResponseRedirects('/login');
    }

    public function testRegularUserAccessToAdminIsForbidden(): void
    {
        $client = static::createClient();
        $userRepo = static::getContainer()->get('doctrine')->getRepository(User::class);
        $user = $userRepo->findOneBy(['username' => 'mc']);

        $client->loginUser($user);
        $client->request('GET', '/admin');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testStaffUserAccessToAdminIsForbidden(): void
    {
        $client = static::createClient();
        $userRepo = static::getContainer()->get('doctrine')->getRepository(User::class);
        $staff = $userRepo->findOneBy(['username' => 'staff1']);

        $client->loginUser($staff);
        $client->request('GET', '/admin');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testStaffUserAccessToAdminCustomersIsForbidden(): void
    {
        $client = static::createClient();
        $userRepo = static::getContainer()->get('doctrine')->getRepository(User::class);
        $staff = $userRepo->findOneBy(['username' => 'staff1']);

        $client->loginUser($staff);
        $client->request('GET', '/admin/customers');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminUserCanAccessDashboard(): void
    {
        $client = static::createClient();
        $userRepo = static::getContainer()->get('doctrine')->getRepository(User::class);
        $admin = $userRepo->findOneBy(['username' => 'yan']);

        $client->loginUser($admin);
        $client->request('GET', '/admin');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1.admin-title', 'System Overview & Analytics');
    }

    public function testAdminUserCanAccessCustomers(): void
    {
        $client = static::createClient();
        $userRepo = static::getContainer()->get('doctrine')->getRepository(User::class);
        $admin = $userRepo->findOneBy(['username' => 'yan']);

        $client->loginUser($admin);
        $client->request('GET', '/admin/customers');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1.admin-title', 'User & Staff Management');
    }
}

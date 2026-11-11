<?php

namespace App\Tests;

use App\Entity\Booking;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StaffSecurityTest extends WebTestCase
{
    public function testRegularUserCannotAccessStaffPortal(): void
    {
        $client = static::createClient();
        $userRepo = static::getContainer()->get('doctrine')->getRepository(User::class);
        $user = $userRepo->findOneBy(['username' => 'mc']);

        $client->loginUser($user);
        $client->request('GET', '/staff');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testStaffUserCanAccessStaffPortal(): void
    {
        $client = static::createClient();
        $userRepo = static::getContainer()->get('doctrine')->getRepository(User::class);
        $staff = $userRepo->findOneBy(['username' => 'staff1']);

        $client->loginUser($staff);
        $client->request('GET', '/staff');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1.admin-title', 'Staff Portal & Records');
    }

    public function testAdminUserCanAccessStaffPortalViaRoleHierarchy(): void
    {
        $client = static::createClient();
        $userRepo = static::getContainer()->get('doctrine')->getRepository(User::class);
        $admin = $userRepo->findOneBy(['username' => 'yan']);

        $client->loginUser($admin);
        $client->request('GET', '/staff');

        $this->assertResponseIsSuccessful();
    }

    public function testStaffCannotEditAnotherUsersRecord(): void
    {
        $client = static::createClient();
        $userRepo = static::getContainer()->get('doctrine')->getRepository(User::class);
        $bookingRepo = static::getContainer()->get('doctrine')->getRepository(Booking::class);

        $staff = $userRepo->findOneBy(['username' => 'staff1']);
        // Booking 2 is owned by user 1 (mc), not staff1
        $otherBooking = $bookingRepo->find(2);

        if ($otherBooking) {
            $client->loginUser($staff);
            $client->request('GET', '/staff/record/' . $otherBooking->getId() . '/edit');

            $this->assertResponseStatusCodeSame(403);
        }
    }
}

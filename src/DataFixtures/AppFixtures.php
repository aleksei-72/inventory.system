<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Department;
use App\Entity\Item;
use App\Entity\Profile;
use App\Entity\Room;
use App\Entity\User;
use App\UserRoleList;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {

        $user = new User();
        $user->setUserName("admin");
        $user->setName("admin");
        $user->setPassword("P@ssw0rd");
        $user->setEmail("admin@example.com");
        $user->setRole(UserRoleList::U_ADMIN);
        $user->setCreatedAt(new \DateTime());

        $manager->persist($user);
        $manager->flush();
    }

}

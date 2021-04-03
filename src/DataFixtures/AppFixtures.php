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
        // $product = new Product();
        // $manager->persist($product);

        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setUserName("user_$i");
            $user->setName("Иванов Иван Иванович");
            $user->setPassword(password_hash("P@ssw0rd_$i", PASSWORD_BCRYPT));
            $user->setEmail("user_$i@example.com");
            $user->setRole(UserRoleList::U_USER);
            $user->setCreatedAt(new \DateTime());



            $reader = new User();
            $reader->setUserName("reader_$i");
            $reader->setName("Иванов Иван Иванович");
            $reader->setPassword(password_hash("P@ssw0rd_$i", PASSWORD_BCRYPT));
            $reader->setEmail("reader_$i@example.com");
            $reader->setRole(UserRoleList::U_READONLY);
            $reader->setCreatedAt(new \DateTime());

            $manager->persist($user);
            $manager->persist($reader);
        }

        $user = new User();
        $user->setUserName("admin");
        $user->setName("admin");
        $user->setPassword(password_hash("P@ssw0rd", PASSWORD_BCRYPT));
        $user->setEmail("admin@example.com");
        $user->setRole(UserRoleList::U_ADMIN);
        $user->setCreatedAt(new \DateTime());

        $manager->persist($user);


        $manager->flush();
    }

}

<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use App\Entity\UserToken;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // $product = new Product();
        // $manager->persist($product);

        for($i = 0; $i < 25; $i++) {
            $user = new User();

            $user->setName("user_$i");
            $user->setRole('user');
            $user->setPassword(password_hash('P@ssw0rd', PASSWORD_DEFAULT));
            $manager->persist($user);
        }

        $admin = new User();

        $admin->setName('root');
        $admin->setRole('admin');
        $admin->setPassword(password_hash('P@ssw0rd', PASSWORD_DEFAULT));
        $manager->persist($admin);

        $manager->flush();
    }
}

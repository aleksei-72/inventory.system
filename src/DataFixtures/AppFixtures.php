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

        $categories = array();
        $categoryTitles = array("Принтер", "Компьютер", "Мышь", "Клавиатура");

        $itemFirms = array("sumsung", "oclick", "Logitech", "Canon", "honor");

        $comments = array("Работает", "Частично неисправен", "Сломан");


        $profiles = array();
        $profileTitles = array("Коптев Н.С.", "Басташвилли А.В.");


        for ($i = 0; $i < count($profileTitles); $i ++) {
            $a = new Profile();
            $a->setName($profileTitles[$i]);
            array_push($profiles, $a);

            $manager->persist($a);
        }



        for ($i = 0; $i < count($categoryTitles); $i ++) {
            $a = new Category();
            $a->setTitle($categoryTitles[$i]);
            array_push($categories, $a);

            $manager->persist($a);
        }

        $itemNumber = 1700501;

        $itemCreatedAt = time();

        for($i = 0; $i < 3; $i ++)
        {
            $department = new Department();
            $department->setTitle("Корпус № $i");
            $department->setAddress("г.ххх, ул.ххх, д. $i");
            $manager->persist($department);

            $roomCount = rand(2, 5);
            for ($j = 0; $j < $roomCount ; $j ++) {
                $room = new Room();
                $room->setNumber("$j");
                $room->setDepartment($department);

                $manager->persist($room);

                $itemInRoomCount = rand(1, 3);
                for ($l = 0; $l <= $itemInRoomCount; $l ++) {
                    $random = rand(0, count($categoryTitles));

                    $item = new Item();

                    $item->setNumber($itemNumber);
                    $item->addRoom($room);
                    $item->setCount(rand(1, 25));
                    $item->setComment($comments[array_rand($comments)]);

                    $item->setCreatedAt( $itemCreatedAt);
                    $item->setUpdatedAt($itemCreatedAt);

                    $itemCreatedAt++;

                    $itemNumber ++;

                    if(rand(0, 3) !== 2) {
                        $item->setProfile($profiles[array_rand($profiles)]);
                    }

                    switch ($random - 1) {
                        case 0:
                            $item->setCategory($categories[0]);
                            $item->setTitle("Принтер ". $itemFirms[array_rand($itemFirms)]);
                            break;

                        case 1:
                            $item->setCategory($categories[1]);
                            $item->setTitle("Компьютер ". $itemFirms[array_rand($itemFirms)]);
                            break;

                        case 2:
                            $item->setCategory($categories[2]);
                            $item->setTitle("Мышь ". $itemFirms[array_rand($itemFirms)]);
                            break;

                        default:
                            $item->setCategory($categories[3]);
                            $item->setTitle("Клавиатура ". $itemFirms[array_rand($itemFirms)]);
                            break;

                    }

                    $manager->persist($item);
                }
            }
        }




        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setUserName("user_$i");
            $user->setName("Иванов Иван Иванович");
            $user->setPassword(password_hash("P@ssw0rd_$i", PASSWORD_BCRYPT));
            $user->setEmail("user_$i@example.com");
            $user->setRole(UserRoleList::U_USER);
            $user->setCreatedAt(time());



            $reader = new User();
            $reader->setUserName("reader_$i");
            $reader->setName("Иванов Иван Иванович");
            $reader->setPassword(password_hash("P@ssw0rd_$i", PASSWORD_BCRYPT));
            $reader->setEmail("reader_$i@example.com");
            $reader->setRole(UserRoleList::U_READONLY);
            $reader->setCreatedAt(time());

            $manager->persist($user);
            $manager->persist($reader);
        }

        $user = new User();
        $user->setUserName("admin");
        $user->setName("admin");
        $user->setPassword(password_hash("P@ssw0rd", PASSWORD_BCRYPT));
        $user->setEmail("admin@example.com");
        $user->setRole(UserRoleList::U_ADMIN);
        $user->setCreatedAt(time());

        $manager->persist($user);


        $manager->flush();
    }

}

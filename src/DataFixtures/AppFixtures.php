<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Department;
use App\Entity\Item;
use App\Entity\Room;
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


        for ($i = 0; $i < count($categoryTitles); $i ++) {
            $a = new Category();
            $a->setTitle($categoryTitles[$i]);
            array_push($categories, $a);

            $manager->persist($a);
        }

        $itemNumber = 1700501;

        for($i = 0; $i < 3; $i ++)
        {
            $department = new Department();
            $department->setTitle("Корпус № $i");
            $department->setAddress("г.ххх, ул.ххх, д. $i");
            $manager->persist($department);

            $roomCount = rand(10, 40);
            for ($j = 0; $j < $roomCount ; $j ++) {
                $room = new Room();
                $room->setNumber("$j");
                $room->setDepartment($department);

                $manager->persist($room);

                $itemInRoomCount = rand(5, 20);
                for ($l = 0; $l <= $itemInRoomCount; $l ++) {
                    $random = rand(0, count($categoryTitles));

                    $item = new Item();

                    $item->setNumber($itemNumber);
                    $item->addRoom($room);
                    $item->setCount(rand(1, 25));
                    $item->setComment($comments[array_rand($comments)]);
                    $itemNumber ++;

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


        $manager->flush();
    }


    public function load2(ObjectManager $manager)
    {
        // $product = new Product();
        // $manager->persist($product);


        $department = new Department();
        $department->setTitle("keeek");
        $department->setAddress("dfhfh");
        //echo '$department->getId() = '. $department->getId();
        $manager->persist($department);

        //echo '$department->getId() = '. $department->getId();


        $manager->flush();
    }
}

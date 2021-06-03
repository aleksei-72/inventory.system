<?php


namespace App\Service;


use App\Entity\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;


class RawColumnsRequester
{


    private $manager;


    public static $filterNames = ['room_id' => 'r.id', 'profile_id' => 'p.id',
        'category_id' => 'c.id', 'department_id' => 'd.id', 'count' => 'i.count', 'number' => 'i.number',
        'price' => 'i.price', 'created_at' => 'i.createdAt', 'updatedAt' => 'i.updatedAt'];

    public static $columnNames = ['room' => 'r.number', 'profile' => 'p.name',
        'category' => 'c.title', 'department' => 'd.title', 'count' => 'i.count', 'number' => 'i.number',
        'title' => 'i.title', 'price' => 'i.price', 'comment' => 'i.comment',
        'created_at' => 'i.createdAt', 'updated_at' => 'i.updatedAt'];


    public static $operators = ['gt' => '>', 'ls' => '<', 'eq' => '=', 'neq' => '!='];


    public function __construct(ManagerRegistry $doctrine)
    {
        $this->manager = $doctrine->getManager();
    }

    public function getSomeColumnsByCriterias(array $filters, array $columns, array $order):array {

        $sort = array_keys($order)[0];

        $sort = str_replace('%order%', $order[$sort], $sort);

        $queryParams = array();

        $where = '';
        $selected = '';
        $joins = '';

        $listOfJoins = [
            'category' => ['enable' => false, 'sql' => 'LEFT JOIN i.category c'],
            'room' => ['enable' => false, 'sql' => 'LEFT JOIN i.room r'],
            'department' => ['enable' => false, 'sql' => 'LEFT JOIN r.department d'],
            'profile' => ['enable' => false, 'sql' => 'LEFT JOIN i.profile p']];


        //Названия колонок для выбора
        foreach ($columns as $column) {
            try {
                $selected .= ' ' . self::$columnNames[$column] . ' AS ' . $column . ',';
            } catch (\Exception $e) {
                continue;
            }


            switch (self::$columnNames[$column][0]) {

                case 'c':
                    $listOfJoins['category']['enable'] = true;
                    break;

                case 'r':
                    $listOfJoins['room']['enable'] = true;
                    break;

                case 'p':
                    $listOfJoins['profile']['enable'] = true;
                    break;

                case 'd':
                    $listOfJoins['room']['enable'] = true;
                    $listOfJoins['department']['enable'] = true;
                    break;
            }
        }
        if (mb_strlen($selected) === 0) {
            throw new \Exception("Не выбрано ни одной колонки");
        }

        $selected = substr($selected, 0, strlen($selected) - 1);



        $firstExp = true;
        $i = 1;

        //Условия отбора
        foreach ($filters as $filterName => $filter) {

            try {
                $filterField = self::$filterNames[$filterName];
                $filterOperator = self::$operators[$filter['operator']];

                $exp = $filterField . ' ' . $filterOperator .
                    ' :param_' . $i;
            } catch (\Exception $e) {
                continue;
            }


            if ($firstExp) {
                $where.= " ($exp) ";
                $firstExp = false;
            } else {
                $where.= " AND ($exp) ";
            }


            switch ($filterField[0]) {

                case 'c':
                    $listOfJoins['category']['enable'] = true;
                    break;

                case 'r':
                    $listOfJoins['room']['enable'] = true;
                    break;

                case 'p':
                    $listOfJoins['profile']['enable'] = true;
                    break;

                case 'd':
                    $listOfJoins['room']['enable'] = true;
                    $listOfJoins['department']['enable'] = true;
                    break;
            }



            $queryParams["param_$i"] = $filter['value'];
            $i ++;
        }

        if (mb_strlen($where) === 0) {
            $where = "1=1";
        }


        foreach ($listOfJoins as $join) {
            if ($join ['enable']) {
                $joins .= ' ' . $join['sql'] . ' ';
            }
        }


        $dql = "SELECT $selected FROM App\Entity\Item i $joins " .
            "WHERE $where ORDER BY $sort";


        $query = $this->manager->createQuery($dql)->setParameters($queryParams);

        return $query->getResult();
    }
}
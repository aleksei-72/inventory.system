<?php

namespace App\Repository;

use App\Entity\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Item|null find($id, $lockMode = null, $lockVersion = null)
 * @method Item|null findOneBy(array $criteria, array $orderBy = null)
 * @method Item[]    findAll()
 * @method Item[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ItemRepository extends ServiceEntityRepository
{

    public static $filterNames = ['room_id' => 'r.id', 'profile_id' => 'p.id',
        'category_id' => 'c.id', 'department_id' => 'd.id', 'count' => 'i.count', 'number' => 'i.number',
        'price' => 'i.price', 'created_at' => 'i.createdAt', 'updatedAt' => 'i.updatedAt'];

    public static $columnNames = ['room' => 'r.number', 'profile' => 'p.name',
        'category' => 'c.title', 'department' => 'd.title', 'count' => 'i.count', 'number' => 'i.number',
        'title' => 'i.title', 'price' => 'i.price', 'comment' => 'i.comment',
        'created_at' => 'i.createdAt', 'updated_at' => 'i.updatedAt'];

    public static $operators = ['gt' => '>', 'ls' => '<', 'eq' => '=', 'neq' => '!='];


    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Item::class);
    }

    public function findByCategory(int $category, array $order, int $limit, int $offset): array {
        $sort = array_keys($order)[0];

        $sortOrder = $order[$sort];

        $sort = 'i.' . $sort;

        if ($sort === 'i.count') {
            $sort = 'cast(substring(i.count, \'\d+\') AS Integer)';
        }

        $where = '(c.id = :category)';

        $dql = "SELECT i FROM App\Entity\Item i JOIN i.category c " .
            "WHERE $where ORDER BY $sort $sortOrder";

        $query = $this->getEntityManager()->createQuery($dql)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->setParameter('category', $category);


        $dqlForTotalCount = "SELECT count(i.id) FROM App\Entity\Item i JOIN i.category c " .
            "WHERE $where";

        $queryTotalCount = $this->getEntityManager()->createQuery($dqlForTotalCount)
            ->setParameter('category', $category);

        return ['items' => $query->getResult(), 'total_count' => $queryTotalCount->getResult()[0][1]];
    }


    public function findByKeyWord(string $match, array $order, int $limit, int $offset): array {
        $sort = array_keys($order)[0];
        $match = strtolower($match);

        $queryWords = explode(' ', $match);
        $queryParams = array();


        $sortOrder = $order[$sort];

        $sort = 'i.' . $sort;

        if ($sort === 'i.count') {
            $sort = 'cast(substring(i.count, \'\d+\') AS Integer)';
        }

        $where = '';


        $firstExp = true;

        for ($i = 0; $i < count($queryWords); $i ++) {
            $word = $queryWords[$i];

            if (strlen($word) === 0) {
                continue;
            }

            $newExp = "(lower(i.title) LIKE :match_$i OR lower(i.comment) LIKE :match_$i OR " .
                "lower(i.number) LIKE :match_$i OR lower(c.title) LIKE :match_$i)";

            if ($firstExp) {
                $firstExp = false;
                $where .= $newExp;
            } else {
                $where .= ' AND ' . $newExp;
            }

            $queryParams["match_$i"] = '%' . mb_strtolower($word) . '%';
        }

        if (mb_strlen($where) === 0) {
            $where = "1=1";  
        }


        $dql = "SELECT i FROM App\Entity\Item i LEFT JOIN i.category c " .
            "WHERE $where ORDER BY $sort $sortOrder";

        $query = $this->getEntityManager()->createQuery($dql)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->setParameters($queryParams);


        $sqlForTotalCount = "SELECT count(i.id) FROM App\Entity\Item i LEFT JOIN i.category c " .
            "WHERE $where";

        $queryTotalCount = $this->getEntityManager()->createQuery($sqlForTotalCount)
            ->setParameters($queryParams);

        return ['items' => $query->getResult(), 'total_count' => $queryTotalCount->getResult()[0][1]];
    }


    public function getSomeColumnsByCriterias(array $filters, array $columns, array $order):array {

        $sort = array_keys($order)[0];

        $sortOrder = $order[$sort];
        
        $sort = 'i.' . $sort;

        if ($sort === 'i.count') {
            $sort = 'cast(substring(i.count, \'\d+\') AS Integer)';
        }


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
            "WHERE $where ORDER BY $sort $sortOrder";


        $query = $this->getEntityManager()->createQuery($dql)->setParameters($queryParams);

        return $query->getResult();
    }



    // /**
    //  * @return Item[] Returns an array of Item objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Item
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
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

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Item::class);
    }

    public function findByCategory(int $category, array $order, int $limit, int $offset): array {
        $sort = array_keys($order)[0];


        $where = '(c.id = :category)';

        $dql = "SELECT i FROM App\Entity\Item i JOIN i.category c " .
            "WHERE $where ORDER BY i.$sort ${order[$sort]}";

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
        $dql = "SELECT i FROM App\Entity\Item i JOIN i.category c " .
            "WHERE $where ORDER BY i.$sort ${order[$sort]}";

        $query = $this->getEntityManager()->createQuery($dql)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->setParameters($queryParams);


        $sqlForTotalCount = "SELECT count(i.id) FROM App\Entity\Item i JOIN i.category c " .
            "WHERE $where";

        $queryTotalCount = $this->getEntityManager()->createQuery($sqlForTotalCount)
            ->setParameters($queryParams);

        return ['items' => $query->getResult(), 'total_count' => $queryTotalCount->getResult()[0][1]];
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
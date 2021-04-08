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


        $sql = "SELECT i FROM App\Entity\Item i JOIN i.category c " .
            "WHERE (c.id = :category OR c.parent = :category) ORDER BY i.$sort ${order[$sort]}";

        $query = $this->getEntityManager()->createQuery($sql)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->setParameter('category', $category);


        $sqlForTotalCount = 'SELECT count(i.id) FROM App\Entity\Item i JOIN i.category c  '.
            'WHERE (c.id = :category OR c.parent = :category)';

        $queryTotalCount = $this->getEntityManager()->createQuery($sqlForTotalCount)
            ->setParameter('category', $category);

        return ['items' => $query->getResult(), 'total_count' => $queryTotalCount->getResult()[0][1]];
    }

    public function findByKeyWord(string $match, array $order, int $limit, int $offset): array {
        $sort = array_keys($order)[0];
        $match = strtolower($match);

        $where = '(tsplainquery(i.title, :match) = true OR tsplainquery(i.comment, :match) = true' .
            ' OR tsplainquery(i.number, :match) = true OR tsplainquery(c.title, :match) = true)';

        $sql = "SELECT i FROM App\Entity\Item i JOIN i.category c " .
            "WHERE $where ORDER BY i.$sort ${order[$sort]}";

        $query = $this->getEntityManager()->createQuery($sql)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->setParameter('match', $match);


        $sqlForTotalCount = "SELECT count(i.id) FROM App\Entity\Item i JOIN i.category c " .
            "WHERE $where";

        $queryTotalCount = $this->getEntityManager()->createQuery($sqlForTotalCount)
            ->setParameter('match', $match);

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
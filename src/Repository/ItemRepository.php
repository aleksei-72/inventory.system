<?php

namespace App\Repository;

use App\Entity\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ExpressionBuilder;
use Doctrine\ORM\QueryBuilder;
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

    public function searchByMatch(array $criteria, array $match, array $order, int $limit, int $offset) {
        $orderField = array_keys($order)[0] ?? 'id';

        $query = $this->createSearchByMatchQuery($criteria, $match)
            ->orderBy("i.$orderField", $order[$orderField] ?? 'asc')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $query->getQuery()->getResult();
    }

    public function countByMatch(array $criteria, array $match) {

        $query = $this->createSearchByMatchQuery($criteria, $match)
            ->select('count(i.id)');

        return $query->getQuery()->getResult()[0][1];
    }



    private function createSearchByMatchQuery(array $equal, array $match): QueryBuilder {

        $query = $this->createQueryBuilder('i');

        //(field1 = eq1 AND field2 = eq2 AND field3 = eq3) AND (field1 like %like1% OR field2 like %like2%)

        $equalExpressions = array();
        foreach ($equal as $key => $value) {
            array_push($equalExpressions, $query->expr()->eq("i.$key", $value));
        }

        $matchExpressions = array();
        foreach ($match as $key => $value) {
            array_push($matchExpressions, $query->expr()->like("i.$key", '\'%' . $value . '%\''));
        }

        $equalExp = call_user_func_array([$query->expr(), 'andX'], $equalExpressions);
        $matchExp = call_user_func_array([$query->expr(), 'orX'], $matchExpressions);

        if ((count($equal) + count($match)) !== 0) {
            $query->where($query->expr()->andX($equalExp, $matchExp));
        }

        return $query;
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

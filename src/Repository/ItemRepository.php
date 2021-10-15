<?php

namespace App\Repository;

use App\ArgumentResolver\ElasticClientResolver;
use App\ColumnList;
use App\Entity\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Elasticsearch\Client;

/**
 * @method Item|null find($id, $lockMode = null, $lockVersion = null)
 * @method Item|null findOneBy(array $criteria, array $orderBy = null)
 * @method Item[]    findAll()
 * @method Item[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ItemRepository extends ServiceEntityRepository
{

    private Client $client;

    public function __construct(ManagerRegistry $registry)
    {
        $this->client = ((new ElasticClientResolver())->resolve(null, null))->current();
        parent::__construct($registry, Item::class);
    }

    public function findByCategory(?int $category, array $order, int $limit, int $offset): array {
        $sort = array_keys($order)[0];

        $sort = str_replace('%order%', $order[$sort], ColumnList::itemSortingBy[$sort]);

        $where = '(c.id = :category)';

        if (!$category) {
            $where= '1=1';
        }

        $dql = "SELECT i FROM App\Entity\Item i JOIN i.category c " .
            "WHERE $where ORDER BY $sort";

        $dqlForTotalCount = "SELECT count(i.id) FROM App\Entity\Item i JOIN i.category c " .
            "WHERE $where";


        $query = $this->getEntityManager()->createQuery($dql)
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $queryTotalCount = $this->getEntityManager()->createQuery($dqlForTotalCount);

        if ($category) {
            $query->setParameter('category', $category);
            $queryTotalCount->setParameter('category', $category);
        }


        return ['items' => $query->getResult(), 'total_count' => $queryTotalCount->getResult()[0][1]];
    }


    public function searchByKeyWord(string $match, array $order, int $limit, int $offset): array {
        $sort = array_keys($order)[0];
        $match = strtolower($match);

        $queryWords = explode(' ', $match);
        $queryParams = array();

        $sort = str_replace('%order%', $order[$sort], ColumnList::itemSortingBy[$sort]);


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
            "WHERE $where ORDER BY $sort";

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

    public function searchElasticByKeyWord(string $match, array $order, int $limit, int $offset): array {

        $sort = array_keys($order)[0];
        $order = $order[$sort];

        $match = strtolower($match);

        $data = $this->client->search([
            'index' => 'item',
            'body' => [
                'from' => $offset,
                'size' => $limit,
                /*'sort' => [
                    $sort => $order,
                    'id' => 'desc'
                ],*/

                'query' => [
                    'multi_match' => [
                        'query' => $match,
                        'fields' => [
                            'title',
                            'comment',
                            'number',
                            'category.title'
                        ]
                    ]
                ]
            ]
        ]);

        $results = array_map(function($item) {
            return $item['_source'];
        }, $data['hits']['hits']);

        return ['items' => $results, 'total_count' => $data['hits']['total']['value']];
    }
}
<?php

namespace App\Repository;

use App\Entity\Content;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Content>
 */
class ContentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Content::class);
    }
    
    public function findByCategory($category_id): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.type = :val')
            ->setParameter('val', $category_id)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(8)
            ->getQuery()
            ->getResult()
        ;
    }

    public function search(string $query): array
    {
        $q = '%' . mb_strtolower($query) . '%';

        return $this->createQueryBuilder('c')
            ->join('c.fk_user', 'u')
            ->where('LOWER(c.title) LIKE :q')
            ->orWhere('LOWER(c.description) LIKE :q')
            ->orWhere('LOWER(u.username) LIKE :q')
            ->setParameter('q', $q)
            ->orderBy('c.created_at', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function searchFiltered(string $query, array $filters): array
    {
        $qb = $this->createQueryBuilder('c')
            ->join('c.fk_user', 'u')
            ->andWhere('c.isSuspended = false');

        if ($query !== '') {
            $q = '%' . mb_strtolower($query) . '%';
            $qb->andWhere('LOWER(c.title) LIKE :q OR LOWER(c.description) LIKE :q OR LOWER(u.username) LIKE :q')
               ->setParameter('q', $q);
        }

        if (!empty($filters['categories'])) {
            $qb->join('c.type', 'cat')
               ->andWhere('cat.name IN (:categories)')
               ->setParameter('categories', $filters['categories']);
        }

        if (!empty($filters['tags'])) {
            $tagLower = array_map('mb_strtolower', $filters['tags']);
            $sub = $this->getEntityManager()->createQueryBuilder()
                ->select('1')
                ->from('App\Entity\ContentTag', 'ct')
                ->join('ct.fk_tag', 'tag')
                ->where('ct.fk_content = c')
                ->andWhere('LOWER(tag.name) IN (:tagNames)')
                ->getDQL();
            $qb->andWhere($qb->expr()->exists($sub))
               ->setParameter('tagNames', $tagLower);
        }

        if (!empty($filters['date_range']) && $filters['date_range'] !== 'all') {
            $dateMap = [
                'week'  => new \DateTime('-7 days'),
                'month' => new \DateTime('-1 month'),
                'year'  => new \DateTime('-1 year'),
            ];
            if (isset($dateMap[$filters['date_range']])) {
                $qb->andWhere('c.created_at >= :dateFrom')
                   ->setParameter('dateFrom', $dateMap[$filters['date_range']]);
            }
        }

        $minRating = isset($filters['min_rating']) && $filters['min_rating'] !== ''
            ? (int) $filters['min_rating'] : 0;

        if ($minRating > 0) {
            $qb->andWhere(
                '(SELECT AVG(r.value) FROM App\Entity\Rating r WHERE r.fk_content = c) >= :minRating'
            )->setParameter('minRating', $minRating);
        }

        $sort = $filters['sort'] ?? 'newest';

        if ($sort === 'rating') {
            $contents = $qb->orderBy('c.id', 'ASC')->getQuery()->getResult();
            if (empty($contents)) {
                return [];
            }

            $ids = array_map(static fn($c) => $c->getId(), $contents);

            $avgRows = $this->getEntityManager()->createQueryBuilder()
                ->select('IDENTITY(r.fk_content) AS cid, AVG(r.value) AS avg')
                ->from('App\Entity\Rating', 'r')
                ->where('r.fk_content IN (:ids)')
                ->setParameter('ids', $ids)
                ->groupBy('r.fk_content')
                ->getQuery()
                ->getArrayResult();

            $avgMap = [];
            foreach ($avgRows as $row) {
                $avgMap[(int) $row['cid']] = (float) $row['avg'];
            }

            usort($contents, static function ($a, $b) use ($avgMap) {
                $ra = $avgMap[$a->getId()] ?? 0.0;
                $rb = $avgMap[$b->getId()] ?? 0.0;
                return $rb <=> $ra;
            });

            return $contents;
        }

        switch ($sort) {
            case 'downloads':
                $qb->orderBy('c.downloadCount', 'DESC');
                break;
            case 'favorites':
                $qb->addSelect(
                    '(SELECT COUNT(IDENTITY(f3.fk_content)) FROM App\Entity\Favorite f3 WHERE f3.fk_content = c) AS HIDDEN favCount'
                )->orderBy('favCount', 'DESC');
                break;
            default:
                $qb->orderBy('c.created_at', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }
//    /**
//     * @return Content[] Returns an array of Content objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Content
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

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

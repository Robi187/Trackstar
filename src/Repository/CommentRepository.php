<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /** @return Comment[] Top-level comments (no parent), ordered by like count desc, then newest first. */
    public function findTopLevelByContent(\App\Entity\Content $content): array
    {
        return $this->createQueryBuilder('c')
            ->select('c', 'COUNT(cl.fk_user) AS HIDDEN likeCount')
            ->leftJoin('App\Entity\CommentLike', 'cl', 'WITH', 'cl.fk_comment = c')
            ->andWhere('c.fk_content = :content')
            ->andWhere('c.fk_parent_comment IS NULL')
            ->setParameter('content', $content)
            ->groupBy('c.id')
            ->orderBy('likeCount', 'DESC')
            ->addOrderBy('c.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

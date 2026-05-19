<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\CommentLike;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommentLike>
 */
class CommentLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommentLike::class);
    }

    /**
     * Returns [commentId => likeCount] for the given comment IDs.
     *
     * @param int[] $commentIds
     * @return array<int, int>
     */
    public function countByComments(array $commentIds): array
    {
        if (empty($commentIds)) {
            return [];
        }

        $rows = $this->createQueryBuilder('cl')
            ->select('IDENTITY(cl.fk_comment) AS commentId, COUNT(cl.fk_user) AS cnt')
            ->andWhere('cl.fk_comment IN (:ids)')
            ->setParameter('ids', $commentIds)
            ->groupBy('cl.fk_comment')
            ->getQuery()
            ->getArrayResult();

        $result = array_fill_keys($commentIds, 0);
        foreach ($rows as $row) {
            $result[(int) $row['commentId']] = (int) $row['cnt'];
        }

        return $result;
    }

    /**
     * Returns a set of comment IDs that the given user has liked.
     *
     * @param int[] $commentIds
     * @return array<int, true>
     */
    public function likedByUser(User $user, array $commentIds): array
    {
        if (empty($commentIds)) {
            return [];
        }

        $rows = $this->createQueryBuilder('cl')
            ->select('IDENTITY(cl.fk_comment) AS commentId')
            ->andWhere('cl.fk_user = :user')
            ->andWhere('cl.fk_comment IN (:ids)')
            ->setParameter('user', $user)
            ->setParameter('ids', $commentIds)
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['commentId']] = true;
        }

        return $result;
    }
}

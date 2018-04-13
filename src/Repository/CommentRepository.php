<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function findLatest(): array
    {
        $query = $this->getEntityManager()
            ->createQuery('
                SELECT c, a
                FROM App:Comment c
                JOIN c.author a
                WHERE c.publishedAt <= :now
                ORDER BY c.publishedAt DESC
            ')
            ->setParameter('now', new \DateTime())
            ->getResult()
        ;

        return $query;
    }

}

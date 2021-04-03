<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method Course|null find($id, $lockMode = null, $lockVersion = null)
 * @method Course|null findOneBy(array $criteria, array $orderBy = null)
 * @method Course[]    findAll()
 * @method Course[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Course::class);
    }

    /**
     * @return Transaction[] Returns an array of Transaction objects
     */
    public function findTransactionsByFilter(
        User $user,
        Request $request,
        CourseRepository $courseRepository
    ): array {
        $type = $request->query->get('type');
        $courseCode = $request->query->get('course_code');
        $skipExpired = $request->query->get('skip_expired');

        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.user = :userId')
            ->setParameter('userId', $user->getId());

        if ($type) {
            $numberType = (new Transaction())->getNumberTypeOperation($type);
            $qb->andWhere('t.typeOperation = :type')
                ->setParameter('type', $numberType);
        }
        if ($courseCode) {
            $course = $courseRepository->findOneBy(['code' => $courseCode]);
            $value = $course ? $course->getId() : null;
            $qb->andWhere('t.course = :courseId')
                ->setParameter('courseId', $value);
        }
        if ($skipExpired) {
            $qb->andWhere('t.expiresAt is null or t.expiresAt >= :today')
                ->setParameter('today', new DateTime());
        }

        return $qb->getQuery()->getResult();
    }
}

<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Course|null find($id, $lockMode = null, $lockVersion = null)
 * @method Course|null findOneBy(array $criteria, array $orderBy = null)
 * @method Course[]    findAll()
 * @method Course[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CourseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Course::class);
    }

    /**
     * @return Course[] Returns an array of Transaction objects
     */
    public function findExpiringCourses(User $user): array
    {
        $entityManager = $this->getEntityManager();

        $query = $entityManager->createQuery(
            "select distinct c.title, t.expiresAt
                    from App\Entity\Transaction t
                    inner join App\Entity\User u with t.user = :id
                    inner join App\Entity\Course c with t.course = c.id
                    where t.typeOperation = 1
                     and t.expiresAt < DATE_ADD(current_timestamp(),1,'day')"
        )->setParameter('id', $user->getId());

        return $query->getResult();
    }

    public function findMonthlyPaymentReport(string $date): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
                  select c.title,
                        case
                            when c.type = 1 then 'Аренда'
                            when c.type = 3 then 'Покупка'
                            else 'Неизвестно'
                        end as type,
                        count(t.id),
                        sum(t.amount * count(t.id)) over (partition by c.title) as summa
                  from course c
                  inner join transaction t on c.id = t.course_id
                  where
                        t.created_at >= :date and t.created_at <= :date + interval '1 month'
                        and c.type = 1 or c.type = 3
                  group by c.title, c.type, t.amount, c.price
                  order by c.title
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['date' => $date]);

        return $stmt->fetchAll();
    }
}

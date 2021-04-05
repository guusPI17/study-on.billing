<?php

namespace App\Controller;

use App\DTO\Transaction as TransactionDto;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Repository\TransactionRepository;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/v1/transactions")
 */
class TransactionController extends ApiController
{
    /**
     * @Route("/filter", name="api_transactions_filter", methods={"GET"})
     * @OA\Get(
     *     path="/api/v1/transactions/filter",
     *     summary="История начислений и списаний текущего пользователя",
     *     security={
     *         { "Bearer":{} },
     *     },
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Тип транзакции payment | deposit",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="course_code",
     *         in="query",
     *         description="Символьный код курса",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="skip_expired",
     *         in="query",
     *         description="Отбросить записи с датой expires_at в прошлом (т.е. оплаты аренд, которые уже истекли)",
     *         @OA\Schema(type="bool")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список транзакций",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(
     *                         property="id",
     *                         type="int"
     *                     ),
     *                     @OA\Property(
     *                         property="created_at",
     *                         type="string"
     *                     ),
     *                     @OA\Property(
     *                         property="type",
     *                         type="string"
     *                     ),
     *                     @OA\Property(
     *                         property="course_code",
     *                         type="string"
     *                     ),
     *                     @OA\Property(
     *                         property="amount",
     *                         type="number"
     *                     ),
     *                 )
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid JWT Token",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="string",
     *                 ),
     *                 example={"code": "401", "message": "Invalid JWT Token"}
     *             ),
     *        )
     *     )
     * )
     * @OA\Tag(name="Transaction")
     */
    public function filter(
        TransactionRepository $transactionRepository,
        CourseRepository $courseRepository,
        Request $request
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $transactions = $transactionRepository->findTransactionsByFilter(
            $user,
            $request,
            $courseRepository
        );

        $transactionsDto = [];
        foreach ($transactions as $transaction) {
            $course = $transaction->getCourse();
            $transactionsDto[] = new TransactionDto(
                $transaction->getId(),
                $transaction->getCreatedAt()->format('Y-m-d T H:i:s'),
                $transaction->getStringType(),
                $transaction->getAmount(),
                $course ? $course->getCode() : null
            );
        }

        return $this->sendResponseSuccessful($transactionsDto, Response::HTTP_OK);
    }
}

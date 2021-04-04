<?php

namespace App\Controller;

use App\DTO\Course as CourseDto;
use App\DTO\Pay as PayDto;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Service\PaymentService;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/v1/courses")
 */
class CourseController extends ApiController
{
    /**
     * @Route("", name="api_course_index", methods={"GET"})
     * @OA\Get(
     *     path="/api/v1/courses",
     *     summary="Получение списка курсов",
     *     @OA\Response(
     *         response=200,
     *         description="Список курсов",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(
     *                         property="code",
     *                         type="string"
     *                     ),
     *                     @OA\Property(
     *                         property="type",
     *                         type="string"
     *                     ),
     *                     @OA\Property(
     *                         property="price",
     *                         type="number"
     *                     ),
     *                 )
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Expired JWT Token",
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
     *                 example={"code": "401", "message": "Expired JWT Token"}
     *             ),
     *        )
     *     )
     * )
     * @OA\Tag(name="Course")
     */
    public function index(CourseRepository $courseRepository): Response
    {
        $courses = $courseRepository->findBy([], ['code' => 'ASC']);
        $coursesDto = [];
        foreach ($courses as $course) {
            $coursesDto[] = new CourseDto($course->getCode(), $course->getStringType(), $course->getPrice());
        }

        return $this->sendResponseSuccessful($coursesDto, Response::HTTP_OK);
    }

    /**
     * @Route("/{code}", name="api_course_show", methods={"GET"})
     * @OA\Get(
     *     path="/api/v1/courses/{code}",
     *     summary="Получение курса по коду",
     *     security={
     *         { "Bearer":{} },
     *     },
     *     @OA\Response(
     *         response=200,
     *         description="Курс получен",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="type",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="price",
     *                     type="number",
     *                 ),
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Данный курс не найден",
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
     *                 example={"code": "404", "message": "Данный курс не найден"}
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Expired JWT Token",
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
     *                 example={"code": "401", "message": "Expired JWT Token"}
     *             ),
     *        )
     *     )
     * )
     * @OA\Tag(name="Course")
     */
    public function show(string $code, CourseRepository $courseRepository): Response
    {
        $course = $courseRepository->findOneBy(['code' => $code]);
        if (!$course) {
            return $this->sendResponseBad(404, 'Данный курс не найден');
        }
        $courseDto = new CourseDto($course->getCode(), $course->getStringType(), $course->getPrice());

        return $this->sendResponseSuccessful($courseDto, Response::HTTP_OK);
    }

    /**
     * @Route("/{code}/pay", name="api_course_pay", methods={"POST"})
     * @OA\Post(
     *     path="/api/v1/courses/{code}/pay",
     *     summary="Оплата курса",
     *     security={
     *         { "Bearer":{} },
     *     },
     *     @OA\Response(
     *         response=200,
     *         description="Expired JWT Token",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="success",
     *                     type="boolean",
     *                 ),
     *                 @OA\Property(
     *                     property="course_type",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="expires_at",
     *                     type="string",
     *                 ),
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Данный курс не найден",
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
     *                 example={"code": "404", "message": "Данный курс не найден"}
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=406,
     *         description="У вас недостаточно средств",
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
     *                 example={"code": "406", "message": "У вас недостаточно средств"}
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Expired JWT Token",
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
     *                 example={"code": "401", "message": "Expired JWT Token"}
     *             ),
     *        )
     *     )
     * )
     * @OA\Tag(name="Course")
     */
    public function pay(string $code, CourseRepository $courseRepository, PaymentService $paymentService): Response
    {
        $course = $courseRepository->findOneBy(['code' => $code]);
        if (!$course) {
            return $this->sendResponseBad(404, 'Данный курс не найден');
        }
        /* @var User $user */
        $user = $this->getUser();
        try {
            $transaction = $paymentService->paymentCourses($user, $course);
            $expiresAt = $transaction->getExpiresAt();
            $payDto = new PayDto(
                true,
                $course->getStringType(),
                $expiresAt ? $expiresAt->format('Y-m-d T H:i:s') : null
            );

            return $this->sendResponseSuccessful($payDto, Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->sendResponseBad($e->getCode(), $e->getMessage());
        }
    }
}

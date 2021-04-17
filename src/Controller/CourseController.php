<?php

namespace App\Controller;

use App\DTO\Course as CourseDto;
use App\DTO\Pay as PayDto;
use App\Entity\Course;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Service\PaymentService;
use JMS\Serializer\SerializerBuilder;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Request;
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
     *         description="Курс куплен",
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
     *                 example={"code": "406", "message": "На вашем счету недостаточно средств"}
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

    /**
     * @Route("/new", name="api_course_new", methods={"POST"})
     * @OA\Post(
     *     path="/api/v1/courses/new",
     *     summary="Создание курса",
     *     security={
     *         { "Bearer":{} },
     *     },
     *     @OA\RequestBody(
     *         description="JSON",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="type",
     *                     type="string"
     *                 ),
     *                  @OA\Property(
     *                     property="title",
     *                     type="string"
     *                 ),
     *                  @OA\Property(
     *                     property="code",
     *                     type="string"
     *                 ),
     *                  @OA\Property(
     *                     property="price",
     *                     type="number"
     *                 ),
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Курс успешно создан",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="success",
     *                     type="bool",
     *                 ),
     *                 example={"success": true}
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Данный код курса уже существует",
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
     *                 example={"code": "500", "message": "Данный код курса уже существует"}
     *             ),
     *        )
     *     ),
     * )
     * @OA\Tag(name="Course")
     */
    public function new(Request $request, CourseRepository $courseRepository): Response
    {
        $serializer = SerializerBuilder::create()->build();
        $courseDto = $serializer->deserialize($request->getContent(), CourseDto::class, 'json');

        $course = $courseRepository->findOneBy(['code' => $courseDto->getCode()]);
        if ($course) {
            return $this->sendResponseBad(500, 'Данный код курса уже существует');
        }

        // создание пользователя
        $course = Course::fromDtoNew($courseDto);

        // добавление course в БД
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($course);
        $entityManager->flush();

        return $this->sendResponseSuccessful(['success' => true], 201);
    }

    /**
     * @Route("/{code}/edit", name="api_course_edit", methods={"POST"})
     * @OA\Post(
     *     path="/api/v1/courses/{code}/edit",
     *     summary="Редактирование курса",
     *     security={
     *         { "Bearer":{} },
     *     },
     *     @OA\RequestBody(
     *         description="JSON",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="type",
     *                     type="string"
     *                 ),
     *                  @OA\Property(
     *                     property="title",
     *                     type="string"
     *                 ),
     *                  @OA\Property(
     *                     property="code",
     *                     type="string"
     *                 ),
     *                  @OA\Property(
     *                     property="price",
     *                     type="number"
     *                 ),
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Курс успешно изменен",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="success",
     *                     type="bool",
     *                 ),
     *                 example={"success": true}
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Данный код курса уже существует",
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
     *                 example={"code": "500", "message": "Данный код курса уже существует"}
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Курс для изменения не найден",
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
     *                 example={"code": "404", "message": "Курс для изменения не найден"}
     *             ),
     *        )
     *     )
     * )
     * @OA\Tag(name="Course")
     */
    public function edit(string $code, Request $request, CourseRepository $courseRepository): Response
    {
        $serializer = SerializerBuilder::create()->build();
        $courseDto = $serializer->deserialize($request->getContent(), CourseDto::class, 'json');

        $course = $courseRepository->findOneBy(['code' => $courseDto->getCode()]);
        if ($course && $code !== $course->getCode()) {
            return $this->sendResponseBad(500, 'Данный код курса уже существует');
        }

        $course = $courseRepository->findOneBy(['code' => $code]);
        if (!$course) {
            return $this->sendResponseBad(404, 'Курс для изменения не найден');
        }

        $course->fromDtoEdit($courseDto);
        $this->getDoctrine()->getManager()->flush();

        return $this->sendResponseSuccessful(['success' => true], 201);
    }
}

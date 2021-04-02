<?php

namespace App\Controller;

use App\DTO\Course as CourseDto;
use App\Repository\CourseRepository;
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
     *     security={
     *         { "Bearer":{} },
     *     },
     * )
     * @OA\Tag(name="Course")
     */
    public function index(CourseRepository $courseRepository): Response
    {
        $courses = $courseRepository->findBy([], ['code' => 'ASC']);
        $coursesDto = [];
        foreach($courses as $course){
            $coursesDto[] = new CourseDto($course->getCode(), $course->getType(), $course->getPrice());
        }
        return $this->sendResponseSuccessful($coursesDto, Response::HTTP_OK);
    }
}

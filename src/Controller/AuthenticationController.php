<?php

namespace App\Controller;

use App\DTO\Token as TokenDTO;
use App\DTO\User as UserDTO;
use App\Entity\User;
use JMS\Serializer\SerializerBuilder;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api/v1")
 */
class AuthenticationController extends ApiController
{
    /**
     * @Route("/register", name="api_register", methods={"POST"})
     * @OA\Post(
     *     path="/api/v1/register",
     *     summary="Регистрация нового пользователя",
     *     @OA\RequestBody(
     *         description="JSON",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="username",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string"
     *                 ),
     *                 example={"username": "user10@test.com", "password": "123456"}
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Регистрация успешна",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="tocken",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="roles",
     *                     type="array",
     *                     @OA\Items(
     *                     type="string"
     *                     )
     *                 ),
     *                 example={"tocken": "tocken", "roles": "[ROLE_USER]"}
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибочные данные",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="error",
     *                     type="array",
     *                     @OA\Items(
     *                     type="string"
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                 ),
     *                 example={"error": "[error]", "code": "400"}
     *             ),
     *        )
     *     )
     * )
     * @OA\Tag(name="User")
     */
    public function register(
        Request $request,
        ValidatorInterface $validator,
        UserPasswordEncoderInterface $passwordEncoder,
        JWTTokenManagerInterface $JWTManager
    ): Response {
        //return new JsonResponse($request->getContent());
        $serializer = SerializerBuilder::create()->build();
        $userDto = $serializer->deserialize($request->getContent(), UserDTO::class, 'json');

        // проверка правил из DTO
        $errors = $validator->validate($userDto);
        if (count($errors)) {
            return $this->sendResponseBad($errors);
        }

        // создание пользователя
        $user = User::fromDto($userDto, $passwordEncoder);

        // проверка правил из Entity
        $errors = $validator->validate($user);
        if (count($errors)) {
            return $this->sendResponseBad($errors);
        }

        // добавление пользователя в БД
        $manager = $this->getDoctrine()->getManager();
        $manager->persist($user);
        $manager->flush();

        // создание токена JWT
        $token = $JWTManager->create($user);
        $tokenResponse = new TokenDTO($token, $user->getRoles());

        return $this->sendResponseSuccessful($tokenResponse, Response::HTTP_CREATED);
    }

    /**
     * @Route("/auth", name="api_authentication", methods={"POST"})
     * @OA\Post(
     *     path="/api/v1/auth",
     *     summary="Авторизация пользователя",
     *     @OA\RequestBody(
     *         description="JSON",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="username",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string"
     *                 ),
     *                 example={"username": "user@test.com", "password": "user@test.com"}
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Авторизация успешна",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="tocken",
     *                     type="string"
     *                 ),
     *                 example={"tocken": "tocken"}
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не верный пароль или логин",
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
     *                 example={"code": "401", "message": "Invalid credentials."}
     *             ),
     *        )
     *     )
     * )
     * @OA\Tag(name="User")
     */
    public function authentication(): void
    {
        // JWTAuthentication
    }
}
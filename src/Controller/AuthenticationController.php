<?php

namespace App\Controller;

use App\DTO\Token as TokenDTO;
use App\DTO\User as UserDTO;
use App\Entity\User;
use JMS\Serializer\SerializerBuilder;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
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
     * @OA\Response(
     *     response=200,
     *     description="Вовзвращает токен",
     *     @OA\Schema(type="string")
     * )
     * @OA\Post(
     *     @OA\Parameter(
     *         name="username",
     *         in="body",
     *         description="Почта",
     *         required=true,
     *     )
     * )
     * @OA\Tag(name="user")
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
     */
    public function authentication(): void
    {
        // JWTAuthentication
    }
}

<?php

namespace App\Controller;

use App\DTO\User as UserDto;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/v1/users")
 */
class UserController extends ApiController
{
    /**
     * @Route("/current", name="api_user_current", methods={"GET"})
     *
     * @OA\Get(
     *     path="/api/v1/users/current",
     *     summary="Получение текущего пользователя",
     *     security={
     *         { "Bearer":{} },
     *     },
     *     @OA\Response(
     *         response=200,
     *         description="Пользователь получен",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="username",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="balance",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="roles",
     *                     type="array",
     *                     @OA\Items(
     *                     type="string"
     *                     ),
     *                 ),
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не верный JWT токен",
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
     *                 example={"code": "401", "message": "JWT Token not found"}
     *             ),
     *        ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Текущий пользователь не определен",
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
     *                 example={"code": "404", "message": "Текущий пользователь не определен"}
     *             ),
     *        ),
     *     )
     * )
     * @OA\Tag(name="User")
     */
    public function current(): Response
    {
        $user = $this->getUser();
        if ($user) {
            $userDto = new UserDto();
            $userDto->setUsername($user->getEmail());
            $userDto->setRoles($user->getRoles());
            $userDto->setBalance($user->getBalance());

            return $this->sendResponseSuccessful($userDto, Response::HTTP_OK);
        }

        return $this->sendResponseBad(404, 'Текущий пользователь не определен');
    }
}

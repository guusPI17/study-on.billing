<?php

namespace App\Controller;

use App\DTO\Response as ResponseDTO;
use App\DTO\User;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/v1/users")
 */
class UserController extends ApiController
{
    /**
     * @OA\Components(
     *     @OA\SecurityScheme(
     *         securityScheme="bearerAuth",
     *         type="http",
     *         scheme="bearer",
     *     )
     * )
     */

    /**
     * @Route("/current", name="api_user_current", methods={"GET"})
     *
     * @OA\Get(
     *     path="/api/v1/users/current",
     *     summary="Получение текущего пользователя",
     *     security={
     *         { "bearerAuth":{} },
     *     },
     *     @OA\Response(
     *         response=200,
     *         description="Пользовтель получен",
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
     *                 example={"username": "user@test.com", "balance": "100", "roles":"[ROLES]"}
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
     *        )
     *     )
     * )
     * @OA\Tag(name="User")
     */
    public function current(): Response
    {
        $user = $this->getUser();
        if ($user) {
            $userDto = new User();
            $userDto->setUsername($user->getEmail());
            $userDto->setRoles($user->getRoles());
            $userDto->setBalance($user->getBalance());

            return $this->sendResponseSuccessful($userDto, Response::HTTP_OK);
        }

        $errors = ['Текущий пользователь не определен'];
        $responseDTO = new ResponseDTO($errors, Response::HTTP_NOT_FOUND);

        return $this->sendResponseSuccessful($responseDTO, Response::HTTP_NOT_FOUND);
    }
}

<?php

namespace App\Controller;

use App\DTO\Response as ResponseDTO;
use App\DTO\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/v1/users")
 */
class UserController extends ApiController
{
    /**
     * @Route("/current", name="api_user_current", methods={"POST"})
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

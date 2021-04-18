<?php

namespace App\Controller;

use App\DTO\Response as ResponseDTO;
use JMS\Serializer\SerializerBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

abstract class ApiController extends AbstractController
{
    protected function sendResponseBad(
        int $code,
        string $message,
        ConstraintViolationListInterface $validationErrors = null
    ): Response {
        $serializer = SerializerBuilder::create()->build();

        $responseDto = new ResponseDTO();
        $responseDto->setCode($code);
        $responseDto->setMessage($message);

        if ($validationErrors) {
            $errors = [];
            foreach ($validationErrors as $validationError) {
                $errors[] = $validationError->getMessage();
            }
            $responseDto->setError($errors);
        }

        return new JsonResponse(
            $serializer->serialize($responseDto, 'json'),
            $code,
            [],
            true
        );
    }

    protected function sendResponseSuccessful($data, int $status, array $headers = []): Response
    {
        $serializer = SerializerBuilder::create()->build();

        return new JsonResponse($serializer->serialize($data, 'json'), $status, $headers, true);
    }
}

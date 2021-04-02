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
    protected function sendResponseBad(ConstraintViolationListInterface $validationErrors): Response
    {
        $serializer = SerializerBuilder::create()->build();

        $errors = [];
        foreach ($validationErrors as $validationError) {
            $errors[] = $validationError->getMessage();
        }

        $responseDTO = new ResponseDTO($errors, 400);

        return new JsonResponse(
            $serializer->serialize($responseDTO, 'json'),
            400,
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

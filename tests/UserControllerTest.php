<?php

namespace App\Tests;

use App\DataFixtures\CourseFixtures;
use App\DataFixtures\UserFixtures;
use App\DTO\Response as ResponseDto;
use App\DTO\Token as TokenDto;
use App\DTO\User as UserDto;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Repository\UserRepository;
use App\Service\PaymentService;
use JMS\Serializer\SerializerInterface;

class UserControllerTest extends AbstractTest
{
    private $urlBase;
    private $passwordEncoder;
    private $paymentService;
    private $dataUser;
    private $dataAdmin;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    protected function getFixtures(): array
    {
        return [
            new UserFixtures($this->passwordEncoder, $this->paymentService),
            CourseFixtures::class
        ];
    }

    protected function setUp(): void
    {
        static::getClient();

        $this->passwordEncoder = self::$container->get('security.password_encoder');
        $this->paymentService = self::$container->get(PaymentService::class);
        $this->serializer = self::$container->get('jms_serializer');
        $this->urlBase = '/api/v1';

        $this->loadFixtures($this->getFixtures());

        $userRepository = self::$container->get(UserRepository::class);
        $this->dataAdmin = $userRepository->findOneBy(['email' => 'admin@test.com']);
        $this->dataUser = $userRepository->findOneBy(['email' => 'user@test.com']);
    }

    public function testCurrent(): void
    {
        $client = self::getClient();

        // авторизируемся
        /** @var TokenDto $authorizationToken */
        $authorizationToken = $this->authorization($this->dataAdmin);

        /// Начало первого теста - верные данные -->

        // отправка верного запроса
        $contentHeaders = [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $authorizationToken->getToken(),
            'CONTENT_TYPE' => 'application/json',
        ];

        $client->request(
            'get',
            $this->urlBase . '/users/current',
            [],
            [],
            $contentHeaders
        );
        // проверка статуса
        $this->assertResponseOk();

        // проверка заголовка
        self::assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'));

        /** @var UserDto $responseUser */
        $responseUser = $this->serializer->deserialize($client->getResponse()->getContent(), UserDto::class, 'json');

        // проверка данных пользователя
        self::assertEquals($responseUser->getBalance(),$this->dataAdmin->getBalance());
        self::assertContains($responseUser->getRoles()[0], $this->dataAdmin->getRoles());
        self::assertEquals($responseUser->getUsername(),$this->dataAdmin->getEmail());

        /// Конец первого теста <--

        /// Начало 2 теста - не верные данные(jws токен ошибочный) -->
        $this->errorResponse(
            'get',
            $this->urlBase . '/users/current',
            "error_token",
            401,
            "Invalid JWT Token");
        /// Конец 2 теста <--

        /// Начало 3 теста - не верные данные(jws токен отсутствует) -->
        $this->errorResponse(
            'get',
            $this->urlBase . '/users/current',
            "",
            401,
            "JWT Token not found");
        /// Конец 3 теста <--
    }

    private function errorResponse(string $method, string $uri, string $token, string $code, string $message): void
    {
        $client = self::getClient();;

        $contentHeaders = [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json',
        ];

        // запрос с ошибочным кодом
        $client->request(
            $method,
            $uri,
            [],
            [],
            $contentHeaders
        );
        // проверка статуса
        $this->assertResponseCode($code);

        // проверка заголовка
        self::assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'));

        /** @var ResponseDto $responseError */
        $responseError =
            $this->serializer->deserialize($client->getResponse()->getContent(), ResponseDto::class, 'json');
        self::assertEquals($responseError->getCode(), $code);
        self::assertEquals($responseError->getMessage(), $message);
    }

    private function authorization(User $dataAccount): TokenDto
    {
        $client = self::getClient();

        // json данных пользователя
        $userDto = new UserDto();
        $userDto->setUsername($dataAccount->getEmail());
        $userDto->setPassword($dataAccount->getEmail()); // пароль совпадает с почтой
        $serializerData = $this->serializer->serialize($userDto, 'json');

        // отправка запроса
        $client->request(
            'post',
            $this->urlBase . '/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $serializerData
        );

        // проверка статуса
        $this->assertResponseOk();

        // проверка заголовка
        self::assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'));

        // проверка наличия jwt и refresh токена
        /** @var TokenDto $responseToken */
        $responseToken = $this->serializer->deserialize($client->getResponse()->getContent(), TokenDto::class, 'json');
        self::assertNotNull($responseToken->getToken());
        self::assertNotNull($responseToken->getRefreshToken());
        return $responseToken;
    }
}

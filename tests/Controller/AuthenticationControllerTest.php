<?php

namespace App\Controller\Tests;

use App\DataFixtures\CourseFixtures;
use App\DataFixtures\UserFixtures;
use App\DTO\Response as ResponseDto;
use App\DTO\Token as TokenDto;
use App\DTO\User as UserDto;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\PaymentService;
use App\Tests\AbstractTest;
use JMS\Serializer\SerializerInterface;

class AuthenticationControllerTest extends AbstractTest
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
            CourseFixtures::class,
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

    public function testFailedLogin(): void
    {
        $client = self::getClient();

        // json с неверными данными пользователя
        $userDto = new UserDto();
        $userDto->setUsername('user@test.com');
        $userDto->setPassword('1');
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
        $this->assertResponseCode(401);

        // проверка заголовка
        self::assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'));

        // проверка данных токена
        /** @var TokenDto $responseToken */
        $responseToken = $this->serializer->deserialize($client->getResponse()->getContent(), TokenDto::class, 'json');
        self::assertEquals('Invalid credentials.', $responseToken->getMessage());
        self::assertEquals(401, $responseToken->getCode());
    }

    public function testSuccessfulLogin(): void
    {
        $this->authorization($this->dataUser);
    }

    public function testFailedRegister(): void
    {
        $client = self::getClient();

        $errorsResponse = [
            'username' => [
                'uniqueFalse' => 'Данная почта уже зарегистрированна.',
                'failedFormat' => 'Неверный формат почты.',
            ],
            'password' => ['smallLength' => 'Длина пароля должна быть минимум 6 символов.'],
        ];

        $checkData = [
            [
                'username' => [
                    'text' => 'test',
                    'request' => $errorsResponse['username']['failedFormat'],
                ],
                'password' => [
                    'text' => '1',
                    'request' => $errorsResponse['password']['smallLength'],
                ],
            ],
            [
                'username' => [
                    'text' => 'user@test.com',
                    'request' => $errorsResponse['username']['uniqueFalse'],
                ],
                'password' => [
                    'text' => '123456',
                    'request' => 0,
                ],
            ],
        ];

        foreach ($checkData as $i => $iValue) {
            // json с неверными данными пользователя
            $userDto = new UserDto();
            $userDto->setUsername($iValue['username']['text']);
            $userDto->setPassword($iValue['password']['text']);
            $serializerData = $this->serializer->serialize($userDto, 'json');

            // отправка запроса
            $client->request(
                'post',
                $this->urlBase . '/register',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                $serializerData
            );

            // проверка статуса
            $this->assertResponseCode(400);

            // проверка заголовка
            self::assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'));

            // проверка данных ответа
            /** @var ResponseDto $responseDto */
            $responseDto =
                $this->serializer->deserialize($client->getResponse()->getContent(), ResponseDto::class, 'json');
            if (0 != $iValue['username']['request']) {
                self::assertEquals($iValue['username']['request'], $responseDto->getError()[0]); // username
            }
            if (0 != $iValue['password']['request']) {
                self::assertEquals($iValue['password']['request'], $responseDto->getError()[1]); // password
            }
            self::assertEquals(400, $responseDto->getCode());
        }
    }

    public function testSuccessfulRegister(): void
    {
        $client = self::getClient();

        // создание json данных о пользователе
        $userDto = new UserDto();
        $userDto->setUsername('user1@test.com');
        $userDto->setPassword('123456');
        $serializerData = $this->serializer->serialize($userDto, 'json');

        // отправка запроса
        $client->request(
            'post',
            $this->urlBase . '/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $serializerData
        );

        // проверка статуса
        $this->assertResponseCode(201);

        // проверка заголовка
        self::assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'));

        // проверка наличия jwt и refresh токена
        /** @var TokenDto $responseToken */
        $responseToken = $this->serializer->deserialize($client->getResponse()->getContent(), TokenDto::class, 'json');
        self::assertNotNull($responseToken->getToken());
        self::assertNotNull($responseToken->getRefreshToken());

        // проверка роли пользователя
        self::assertContains('ROLE_USER', $responseToken->getRoles());
    }

    public function testRefreshToken(): void
    {
        $client = self::getClient();

        // авторизирауемся
        /** @var TokenDto $authorizationToken */
        $authorizationToken = $this->authorization($this->dataUser);

        // создаем данные для отпарвки
        $tokenDto = new TokenDto();
        $tokenDto->setRefreshToken($authorizationToken->getRefreshToken());
        $serializerData = $this->serializer->serialize($tokenDto, 'json');

        /// Начало первого теста - верные данные -->

        // верная отправка запроса
        $client->request(
            'post',
            $this->urlBase . '/token/refresh',
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

        /// Конец первого теста <--

        /// Начало 2 теста - не верные данные(jws токен не верный) -->
        $this->errorResponse(
            'post',
            $this->urlBase . '/token/refresh',
            '',
            401,
            'An authentication exception occurred.');
        /// Конец 2 теста <--
    }

    private function errorResponse(string $method, string $uri, string $token, string $code, string $message): void
    {
        $client = self::getClient();

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

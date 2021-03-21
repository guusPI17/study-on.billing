<?php

namespace App\Tests;

use App\DataFixtures\UserFixtures;
use App\DTO\Response as ResponseDto;
use App\DTO\Token as TokenDto;
use App\DTO\User as UserDto;
use JMS\Serializer\SerializerInterface;

class AuthenticationControllerTest extends AbstractTest
{
    private $urlBase = '/api/v1';

    /**
     * @var SerializerInterface
     */
    private $serializer;

    protected function getFixtures(): array
    {
        return [new UserFixtures(self::$container->get('security.password_encoder'))];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::$container->get('jms_serializer');
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
        $client = self::getClient();

        // json данных пользователя
        $userDto = new UserDto();
        $userDto->setUsername('user@test.com');
        $userDto->setPassword('user@test.com');
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

        // проверка наличия токена
        /** @var TokenDto $responseToken */
        $responseToken = $this->serializer->deserialize($client->getResponse()->getContent(), TokenDto::class, 'json');
        self::assertNotNull($responseToken->getToken());
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
            $responseDto = $this->serializer->deserialize($client->getResponse()->getContent(), ResponseDto::class, 'json');
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

        // проверка наличия токена
        /** @var TokenDto $responseToken */
        $responseToken = $this->serializer->deserialize($client->getResponse()->getContent(), TokenDto::class, 'json');
        self::assertNotNull($responseToken->getToken());

        // проверка роли пользователя
        self::assertContains('ROLE_USER', $responseToken->getRoles());
    }
}

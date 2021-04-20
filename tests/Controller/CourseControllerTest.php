<?php

namespace App\Controller\Tests;

use App\DataFixtures\CourseFixtures;
use App\DataFixtures\UserFixtures;
use App\DTO\Course as CourseDto;
use App\DTO\Pay as PayDto;
use App\DTO\Response as ResponseDto;
use App\DTO\Token as TokenDto;
use App\DTO\User as UserDto;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use App\Service\PaymentService;
use App\Tests\AbstractTest;
use JMS\Serializer\SerializerInterface;

class CourseControllerTest extends AbstractTest
{
    private $urlBase;
    private $dataUser;
    private $dataAdmin;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    protected function getFixtures(): array
    {
        return [
            UserFixtures::class,
            CourseFixtures::class,
        ];
    }

    protected function setUp(): void
    {
        static::getClient();

        $this->serializer = self::$container->get('jms_serializer');
        $this->urlBase = '/api/v1';

        $this->loadFixtures($this->getFixtures());

        $userRepository = self::$container->get(UserRepository::class);
        $this->dataAdmin = $userRepository->findOneBy(['email' => 'admin@test.com']);
        $this->dataUser = $userRepository->findOneBy(['email' => 'user@test.com']);
    }

    public function testNewCourse(): void
    {
        $client = self::getClient();

        // авторизация
        $authorizationToken = $this->authorization($this->dataAdmin);

        /// Начало 1 теста - верные данные -->

        // заголовки с верным аутиф.токеном
        $contentHeaders = [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $authorizationToken->getToken(),
            'CONTENT_TYPE' => 'application/json',
        ];

        // создание курса
        $code = 'test_code';
        $type = 'buy';
        $price = 152.3;
        $courseDto = new CourseDto($code, $type, $price);
        $courseDto->setTitle('Test title');
        $serializerData = $this->serializer->serialize($courseDto, 'json');

        $client->request(
            'post',
            $this->urlBase . '/courses/new',
            [],
            [],
            $contentHeaders,
            $serializerData
        );
        // проверка статуса
        $this->assertResponseCode(201);

        // проверка заголовка
        self::assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'));

        /** @var ResponseDto $response */
        $response =
            $this->serializer->deserialize($client->getResponse()->getContent(), ResponseDto::class, 'json');

        // находим в базе курсов
        $courseRepository = self::$container->get(CourseRepository::class);
        $course = $courseRepository->findOneBy(['code' => $code]);

        // проверка данных ответа
        self::assertEquals($response->getSuccess(), true);
        self::assertEquals($type, $course->getStringType());
        self::assertEquals($price, $course->getPrice());

        /// Конец 1 теста <--

        /// Начало 2 теста - данный курс уже существует -->

        // заголовки с верным аутиф.токеном
        $contentHeaders = [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $authorizationToken->getToken(),
            'CONTENT_TYPE' => 'application/json',
        ];

        // создание курса
        $code = 'deep_learning';
        $type = 'buy';
        $price = 152.3;
        $courseDto = new CourseDto($code, $type, $price);
        $courseDto->setTitle('Test title');
        $serializerData = $this->serializer->serialize($courseDto, 'json');

        $client->request(
            'post',
            $this->urlBase . '/courses/new',
            [],
            [],
            $contentHeaders,
            $serializerData
        );
        // проверка статуса
        $this->assertResponseCode(500);

        // проверка заголовка
        self::assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'));

        /** @var ResponseDto $response */
        $response =
            $this->serializer->deserialize($client->getResponse()->getContent(), ResponseDto::class, 'json');

        // проверка данных ответа
        self::assertEquals($response->getCode(), 500);
        self::assertEquals($response->getMessage(), 'Данный код курса уже существует');

        /// Конец 1 теста <--

        /// Начало 3 теста - не верные данные(jws токен ошибочный) -->
        $this->errorResponse(
            'post',
            $this->urlBase . '/courses/error_course/pay',
            'error_token',
            401,
            'Invalid JWT Token');
        /// Конец 3 теста <--

        /// Начало 4 теста - не верные данные(jws токен отсутствует) -->
        $this->errorResponse(
            'get',
            $this->urlBase . '/courses/error_course',
            '',
            401,
            'JWT Token not found');
        /// Конец 4 теста <--
    }

    public function testEditCourse(): void
    {
        $client = self::getClient();

        // авторизация
        $authorizationToken = $this->authorization($this->dataAdmin);

        /// Начало 1 теста - верные данные -->

        // заголовки с верным аутиф.токеном
        $contentHeaders = [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $authorizationToken->getToken(),
            'CONTENT_TYPE' => 'application/json',
        ];

        // создание курса
        $code = 'test_code';
        $type = 'buy';
        $price = 152.3;
        $courseDto = new CourseDto($code, $type, $price);
        $courseDto->setTitle('Test title');
        $serializerData = $this->serializer->serialize($courseDto, 'json');

        $client->request(
            'post',
            $this->urlBase . '/courses/deep_learning/edit',
            [],
            [],
            $contentHeaders,
            $serializerData
        );
        // проверка статуса
        $this->assertResponseOk();

        // проверка заголовка
        self::assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'));

        /** @var ResponseDto $response */
        $response =
            $this->serializer->deserialize($client->getResponse()->getContent(), ResponseDto::class, 'json');

        // находим в базе курсов
        $courseRepository = self::$container->get(CourseRepository::class);
        $course = $courseRepository->findOneBy(['code' => $code]);

        // проверка данных ответа
        self::assertEquals($response->getSuccess(), true);
        self::assertEquals($type, $course->getStringType());
        self::assertEquals($price, $course->getPrice());

        /// Конец 1 теста <--

        /// Начало 2 теста - не верный code course -->

        // создание любого курса
        $code = 'test_code';
        $type = 'buy';
        $price = 152.3;
        $courseDto = new CourseDto($code, $type, $price);
        $courseDto->setTitle('Test title');
        $serializerData = $this->serializer->serialize($courseDto, 'json');

        $this->errorResponse(
            'post',
            $this->urlBase . '/courses/error_code/edit',
            $authorizationToken->getToken(),
            404,
            'Курс для изменения не найден',
            $serializerData);
        /// Конец 2 теста <--

        /// Начало 3 теста - code курса на который хотят изменить уже существует -->

        // заголовки с верным аутиф.токеном
        $contentHeaders = [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $authorizationToken->getToken(),
            'CONTENT_TYPE' => 'application/json',
        ];

        // создание курса
        $code = 'statistics_course';
        $type = 'buy';
        $price = 152.3;
        $courseDto = new CourseDto($code, $type, $price);
        $courseDto->setTitle('Test title');
        $serializerData = $this->serializer->serialize($courseDto, 'json');

        $client->request(
            'post',
            $this->urlBase . '/courses/c_sharp_course/edit',
            [],
            [],
            $contentHeaders,
            $serializerData
        );
        // проверка статуса
        $this->assertResponseCode(500);

        // проверка заголовка
        self::assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'));

        /** @var ResponseDto $response */
        $response =
            $this->serializer->deserialize($client->getResponse()->getContent(), ResponseDto::class, 'json');

        // проверка данных ответа
        self::assertEquals($response->getCode(), 500);
        self::assertEquals($response->getMessage(), 'Данный код курса уже существует');

        /// Конец 3 теста <--

        /// Начало 4 теста - не верные данные(jws токен ошибочный) -->
        $this->errorResponse(
            'post',
            $this->urlBase . '/courses/error_course/pay',
            'error_token',
            401,
            'Invalid JWT Token');
        /// Конец 4 теста <--

        /// Начало 5 теста - не верные данные(jws токен отсутствует) -->
        $this->errorResponse(
            'get',
            $this->urlBase . '/courses/error_course',
            '',
            401,
            'JWT Token not found');
        /// Конец 5 теста <--
    }

    public function testCoursesList(): void
    {
        $client = self::getClient();

        /// Начало 1 теста - верные данные -->

        // отправка запроса
        $client->request(
            'get',
            $this->urlBase . '/courses',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        // проверка статуса
        $this->assertResponseOk();

        // проверка заголовка
        self::assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'));

        // проверка наличия jwt и refresh токена
        /** @var CourseDto[] $responseCourses */
        $responseCourses =
            $this->serializer->deserialize($client->getResponse()->getContent(), 'array<App\DTO\Course>', 'json');

        // проверка на количество курсов
        $courseRepository = self::$container->get(CourseRepository::class);
        $coursesEntity = $courseRepository->findAll();
        self::assertEquals(count($coursesEntity), count($responseCourses));

        // собираем массив для проверки данных по курсам
        $arrayCourses = [];
        foreach ($responseCourses as $responseCourse) {
            $key = $responseCourse->getCode();
            $arrayCourses[$key] = $responseCourse;
        }

        // проверка данных по курсам
        foreach ($coursesEntity as $courseEntity) {
            $code = $courseEntity->getCode();
            self::assertEquals($code, $arrayCourses[$code]->getCode());
            self::assertEquals($courseEntity->getPrice(), $arrayCourses[$code]->getPrice());
            self::assertEquals($courseEntity->getStringType(), $arrayCourses[$code]->getType());
        }
        /// Конец 1 теста <--
    }

    public function testCourseByCode(): void
    {
        $client = self::getClient();

        // авторизация
        $authorizationToken = $this->authorization($this->dataAdmin);

        /// Начало 1 теста - верные данные -->

        // заголовки с верным аутиф.токеном
        $contentHeaders = [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $authorizationToken->getToken(),
            'CONTENT_TYPE' => 'application/json',
        ];

        $courseRepository = self::$container->get(CourseRepository::class);
        $courses = $courseRepository->findAll();

        // запросы с верными данными
        foreach ($courses as $course) {
            $client->request(
                'get',
                $this->urlBase . '/courses/' . $course->getCode(),
                [],
                [],
                $contentHeaders
            );
            // проверка статуса
            $this->assertResponseOk();

            // проверка заголовка
            self::assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'));

            /** @var CourseDto $responseCourse */
            $responseCourse =
                $this->serializer->deserialize($client->getResponse()->getContent(), CourseDto::class, 'json');

            // проверка данных курса
            self::assertEquals($responseCourse->getCode(), $course->getCode());
            self::assertEquals($responseCourse->getType(), $course->getStringType());
            self::assertEquals($responseCourse->getPrice(), $course->getPrice());
        }
        /// Конец 1 теста <--

        /// Начало 2 теста - не верный code course -->
        $this->errorResponse(
            'get',
            $this->urlBase . '/courses/error_course',
            $authorizationToken->getToken(),
            404,
            'Данный курс не найден');
        /// Конец 2 теста <--

        /// Начало 3 теста - не верные данные(jws токен ошибочный) -->
        $this->errorResponse(
            'get',
            $this->urlBase . '/courses/error_course',
            'error_token',
            401,
            'Invalid JWT Token');
        /// Конец 3 теста <--

        /// Начало 4 теста - не верные данные(jws токен отсутствует) -->
        $this->errorResponse(
            'get',
            $this->urlBase . '/courses/error_course',
            '',
            401,
            'JWT Token not found');
        /// Конец 4 теста <--
    }

    public function testPayCourseByCode(): void
    {
        $client = self::getClient();

        // авторизация
        $authorizationToken = $this->authorization($this->dataAdmin);

        /// Начало 1 теста - верные данные -->

        // заголовки с верным аутиф.токеном
        $contentHeaders = [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $authorizationToken->getToken(),
            'CONTENT_TYPE' => 'application/json',
        ];

        $courseRepository = self::$container->get(CourseRepository::class);
        $course = $courseRepository->findOneBy(['code' => 'deep_learning']);

        $client->request(
            'post',
            $this->urlBase . '/courses/' . $course->getCode() . '/pay',
            [],
            [],
            $contentHeaders
        );
        // проверка статуса
        $this->assertResponseOk();

        // проверка заголовка
        self::assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'));

        /** @var PayDto $responsePay */
        $responsePay =
            $this->serializer->deserialize($client->getResponse()->getContent(), PayDto::class, 'json');

        // находим в базе транзакцию
        $transactionRepository = self::$container->get(TransactionRepository::class);
        $transaction = $transactionRepository->findOneBy(
            [
                'user' => $this->dataAdmin->getId(),
                'course' => $course->getId(),
            ]
        );

        // проверка данных ответа
        self::assertEquals($responsePay->getSuccess(), true);
        self::assertEquals($responsePay->getCourseType(), $course->getStringType());
        self::assertEquals($responsePay->getExpiresAt(), $transaction->getExpiresAt()->format('Y-m-d T H:i:s'));

        /// Конец 1 теста <--

        /// Начало 2 теста - не достаточно средств для покупки -->
        $courseRepository = self::$container->get(CourseRepository::class);
        $course = $courseRepository->findOneBy(['code' => 'c_sharp_course']);

        $this->errorResponse(
            'post',
            $this->urlBase . '/courses/' . $course->getCode() . '/pay',
            $authorizationToken->getToken(),
            406,
            'На вашем счету недостаточно средств');
        /// Конец 2 теста <--

        /// Начало 3 теста - не верный code course -->
        $this->errorResponse(
            'post',
            $this->urlBase . '/courses/error_course/pay',
            $authorizationToken->getToken(),
            404,
            'Данный курс не найден');
        /// Конец 3 теста <--

        /// Начало 4 теста - не верные данные(jws токен ошибочный) -->
        $this->errorResponse(
            'post',
            $this->urlBase . '/courses/error_course/pay',
            'error_token',
            401,
            'Invalid JWT Token');
        /// Конец 4 теста <--

        /// Начало 5 теста - не верные данные(jws токен отсутствует) -->
        $this->errorResponse(
            'get',
            $this->urlBase . '/courses/error_course',
            '',
            401,
            'JWT Token not found');
        /// Конец 5 теста <--
    }

    private function errorResponse(
        string $method,
        string $uri,
        string $token,
        string $code,
        string $message,
        string $content = null
    ): void {
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
            $contentHeaders,
            $content
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

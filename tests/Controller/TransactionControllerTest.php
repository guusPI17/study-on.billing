<?php

namespace App\Controller\Tests;

use App\DataFixtures\CourseFixtures;
use App\DataFixtures\TransactionFixtures;
use App\DataFixtures\UserFixtures;
use App\DTO\Response as ResponseDto;
use App\DTO\Token as TokenDto;
use App\DTO\Transaction as TransactionDto;
use App\DTO\User as UserDto;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use App\Service\PaymentService;
use App\Tests\AbstractTest;
use JMS\Serializer\SerializerInterface;

class TransactionControllerTest extends AbstractTest
{
    private $urlBase;
    private $passwordEncoder;
    private $paymentService;
    private $dataUser;
    private $dataAdmin;
    private $em;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    protected function getFixtures(): array
    {
        return [
            new UserFixtures($this->passwordEncoder, $this->paymentService),
            CourseFixtures::class,
            TransactionFixtures::class,
        ];
    }

    protected function setUp(): void
    {
        static::getClient();

        $this->passwordEncoder = self::$container->get('security.password_encoder');
        $this->em = self::$container->get('doctrine')->getManager();
        $this->paymentService = self::$container->get(PaymentService::class);
        $this->serializer = self::$container->get('jms_serializer');
        $this->urlBase = '/api/v1';

        $this->loadFixtures($this->getFixtures());

        $userRepository = self::$container->get(UserRepository::class);
        $this->dataAdmin = $userRepository->findOneBy(['email' => 'admin@test.com']);
        $this->dataUser = $userRepository->findOneBy(['email' => 'user@test.com']);
    }

    public function testFilter(): void
    {
        $client = self::getClient();

        // авторизация
        $authorizationToken = $this->authorization($this->dataAdmin);

        /// Начало первого теста - верные данные -->

        // заголовки с верным аутиф.токеном
        $contentHeaders = [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $authorizationToken->getToken(),
            'CONTENT_TYPE' => 'application/json',
        ];

        // отправка запроса без дополнительных фильтров
        $client->request(
            'get',
            $this->urlBase . '/transactions/filter',
            [],
            [],
            $contentHeaders
        );

        // проверка статуса
        $this->assertResponseOk();

        // проверка заголовка
        self::assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'));

        /** @var TransactionDto[] $responseTransactions */
        $responseTransactions =
            $this->serializer->deserialize($client->getResponse()->getContent(), 'array<App\DTO\Transaction>', 'json');

        $transactionRepository = self::$container->get(TransactionRepository::class);
        $transactionsEntity = $transactionRepository->findBy(['user' => $this->dataAdmin->getId()]);

        // количество транзакций в БД и в ответе на запрос
        self::assertEquals(count($responseTransactions), count($transactionsEntity));

        // собираем массив для проверки данных по курсам
        $arrayTransactions = [];
        foreach ($responseTransactions as $responseTransaction) {
            $key = $responseTransaction->getId();
            $arrayTransactions[$key] = $responseTransaction;
        }

        // проверка данных по транзакциям
        foreach ($transactionsEntity as $transactionEntity) {
            $id = $transactionEntity->getId();
            self::assertEquals($id, $arrayTransactions[$id]->getId());
            self::assertEquals($transactionEntity->getCreatedAt()->format('Y-m-d T H:i:s'),
                $arrayTransactions[$id]->getCreatedAt());
            self::assertEquals($transactionEntity->getStringType(), $arrayTransactions[$id]->getType());
            self::assertEquals($transactionEntity->getAmount(), $arrayTransactions[$id]->getAmount());
        }

        /// Конец первого теста <--

        /// Начало второго теста - верные данные -->

        // отправка запроса с фильтром skip_expired и type
        $query = 'skip_expired=1&type=payment';
        $client->request(
            'get',
            $this->urlBase . "/transactions/filter?$query",
            [],
            [],
            $contentHeaders
        );

        // проверка статуса
        $this->assertResponseOk();

        // проверка заголовка
        self::assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'));

        /** @var TransactionDto[] $responseTransactions */
        $responseTransactions =
            $this->serializer->deserialize($client->getResponse()->getContent(), 'array<App\DTO\Transaction>', 'json');

        // количество транзакций в БД и в ответе на запрос
        self::assertEquals(count($responseTransactions), 2);

        $courseRepository = self::$container->get(CourseRepository::class);
        $arrayCodesCourses = ['python_course', 'deep_learning'];

        // проверка верности данных из ответа на запрос(+ проверка на верную сортировку)
        foreach ($arrayCodesCourses as $i => $codeCourse) {
            $courseEntity = $courseRepository->findOneBy(['code' => $codeCourse]);
            $transactionEntity = $transactionRepository->findOneBy(
                [
                    'user' => $this->dataAdmin->getId(),
                    'course' => $courseEntity->getId(),
                ]
            );
            self::assertEquals($codeCourse, $responseTransactions[$i]->getCourseCode());
            self::assertEquals($transactionEntity->getCreatedAt()->format('Y-m-d T H:i:s'),
                $responseTransactions[$i]->getCreatedAt());
            self::assertEquals($transactionEntity->getStringType(), $responseTransactions[$i]->getType());
            self::assertEquals($transactionEntity->getAmount(), $responseTransactions[$i]->getAmount());
        }

        /// Конец второго теста <--

        /// Начало третьего теста - верные данные -->

        // отправка запроса с фильтром course_code
        $codeCourse = 'python_course';
        $query = "course_code=$codeCourse";
        $client->request(
            'get',
            $this->urlBase . "/transactions/filter?$query",
            [],
            [],
            $contentHeaders
        );

        // проверка статуса
        $this->assertResponseOk();

        // проверка заголовка
        self::assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'));

        /** @var TransactionDto[] $responseTransactions */
        $responseTransactions =
            $this->serializer->deserialize($client->getResponse()->getContent(), 'array<App\DTO\Transaction>', 'json');

        // количество транзакций в БД и в ответе на запрос
        self::assertEquals(count($responseTransactions), 1);

        $courseEntity = $courseRepository->findOneBy(['code' => $codeCourse]);
        $transactionEntity = $transactionRepository->findOneBy(
            [
                'user' => $this->dataAdmin->getId(),
                'course' => $courseEntity->getId(),
            ]
        );
        self::assertEquals($codeCourse, $responseTransactions[0]->getCourseCode());
        self::assertEquals($transactionEntity->getCreatedAt()->format('Y-m-d T H:i:s'),
            $responseTransactions[0]->getCreatedAt());
        self::assertEquals($transactionEntity->getStringType(), $responseTransactions[0]->getType());
        self::assertEquals($transactionEntity->getAmount(), $responseTransactions[0]->getAmount());

        /// Конец третьего теста <--

        /// Начало 4 теста - не верные данные(jws токен ошибочный) -->
        $this->errorResponse(
            'get',
            $this->urlBase . '/transactions/filter',
            'error_token',
            401,
            'Invalid JWT Token');
        /// Конец 4 теста <--

        /// Начало 5 теста - не верные данные(jws токен отсутствует) -->
        $this->errorResponse(
            'get',
            $this->urlBase . '/transactions/filter',
            '',
            401,
            'JWT Token not found');
        /// Конец 5 теста <--
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

<?php

namespace App\DataFixtures;

use App\Entity\Course;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class CourseFixtures extends Fixture implements FixtureGroupInterface
{
    private const TYPES_COURSE = [
        1 => 'rent',
        2 => 'free',
        3 => 'buy',
    ];

    public static function getGroups(): array
    {
        return ['group1'];
    }

    public function load(ObjectManager $manager)
    {
        $courses = [
            [
                'title' => 'Deep Learning (семестр 1, весна 2021): базовый поток',
                'code' => 'deep_learning',
                'type' => array_search('rent', self::TYPES_COURSE, true),
                'price' => '50',
            ],
            [
                'title' => 'C# для продвинутых',
                'code' => 'c_sharp_course',
                'type' => array_search('buy', self::TYPES_COURSE, true),
                'price' => '250',
            ],
            [
                'title' => 'Принципы дизайна исследований и статистики в медицине',
                'code' => 'statistics_course',
                'type' => array_search('rent', self::TYPES_COURSE, true),
                'price' => '30',
            ],
            [
                'title' => 'Курсы по дизайну',
                'code' => 'design_course',
                'type' => array_search('buy', self::TYPES_COURSE, true),
                'price' => '70',
            ],
            [
                'title' => 'Курсы Python',
                'code' => 'python_course',
                'type' => array_search('free', self::TYPES_COURSE, true),
                'price' => '0',
            ],
        ];

        foreach ($courses as $dataCourse) {
            $course = new Course();
            $course->setTitle($dataCourse['title']);
            $course->setCode($dataCourse['code']);
            $course->setType($dataCourse['type']);
            $course->setPrice($dataCourse['price']);

            $this->addReference($dataCourse['code'], $course);

            $manager->persist($course);
        }

        $manager->flush();
    }
}

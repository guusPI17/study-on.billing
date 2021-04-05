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
                'code' => 'deep_learning',
                'type' => array_search('rent', self::TYPES_COURSE, true),
                'price' => '50',
            ],
            [
                'code' => 'c_sharp_course',
                'type' => array_search('buy', self::TYPES_COURSE, true),
                'price' => '250',
            ],
            [
                'code' => 'statistics_course',
                'type' => array_search('rent', self::TYPES_COURSE, true),
                'price' => '30',
            ],
            [
                'code' => 'design_course',
                'type' => array_search('buy', self::TYPES_COURSE, true),
                'price' => '70',
            ],
            [
                'code' => 'python_course',
                'type' => array_search('free', self::TYPES_COURSE, true),
                'price' => '0',
            ],
        ];

        foreach ($courses as $dataCourse) {
            $course = new Course();
            $course->setCode($dataCourse['code']);
            $course->setType($dataCourse['type']);
            $course->setPrice($dataCourse['price']);

            $this->addReference($dataCourse['code'], $course);

            $manager->persist($course);
        }

        $manager->flush();
    }
}

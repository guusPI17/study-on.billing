<?php

namespace App\DataFixtures;

use App\Entity\Course;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseFixtures extends Fixture
{
    private const TYPES_COURSE = [
        1 => 'rent',
        2 => 'free',
        3 => 'buy',
    ];

    public function load(ObjectManager $manager)
    {
        $courses = [
            [
                'code' => 'deep_learning',
                'type' => array_search('rent', self::TYPES_COURSE, true),
                'price' => '50',
            ],
            [
                'code' => 'c#_course',
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

            $manager->persist($course);
        }

        $manager->flush();
    }
}

<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Service\UserRegistrationService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AddSystemUserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user= new User();
        $user->setId(1);
        $user->setChatId(0);
        $user->setUsername('System');
        $user->setFirstName('Симтема');
        $user->setLastName( null);
        $user->setState(UserRegistrationService::COMPLETE_STATE);
        $user->setCreatedAt(date_create_immutable());
        $user->setUpdatedAt(date_create_immutable());
        $manager->persist($user);
    }
}

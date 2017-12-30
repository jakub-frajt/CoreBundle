<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 20.10.17
 * Time: 15:12
 */

namespace UnitedCMS\CoreBundle\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use UnitedCMS\CoreBundle\Command\CreatePlatformAdminCommand;
use UnitedCMS\CoreBundle\Entity\User;
use UnitedCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class CreatePlatformAdminCommandTest extends DatabaseAwareTestCase
{
    public function testCreateOrganizationCommand() {

        $application = new Application(self::$kernel);
        $command = new CreatePlatformAdminCommand();
        $command->disableHidePasswordInput();
        $application->add($command);

        $command = $application->find('united:user:create');
        $commandTester = new CommandTester($command);

        $this->assertCount(0, $this->em->getRepository('UnitedCMSCoreBundle:User')->findAll());

        $firstName = $this->generateRandomMachineName(10);
        $lastName = $this->generateRandomMachineName(10);
        $email = $this->generateRandomMachineName(10) . '@' . $this->generateRandomMachineName(10) . '.com';
        $password = $this->generateRandomMachineName(10);

        $commandTester->setInputs(array($firstName, $lastName, $email, $password, 'Y'));
        $commandTester->execute(array('command' => $command->getName()));

        // Verify output
        $this->assertContains('Platform Admin was created!', $commandTester->getDisplay());

        // Verify creation
        $users = $this->em->getRepository('UnitedCMSCoreBundle:User')->findAll();
        $this->assertCount(1, $users);
        $this->assertEquals($firstName, $users[0]->getFirstname());
        $this->assertEquals($lastName, $users[0]->getLastname());
        $this->assertEquals($email, $users[0]->getEmail());
        $this->assertTrue($this->container->get('security.password_encoder')->isPasswordValid($users[0], $password));
        $this->assertContains(User::ROLE_PLATFORM_ADMIN, $users[0]->getRoles());


        // Now let's try to create another user with the same email.
        $commandTester->setInputs(array($firstName, $lastName, $email, $password, 'Y'));
        $commandTester->execute(array('command' => $command->getName()));
        $this->assertContains('There was an error while creating the user', $commandTester->getDisplay());
        $this->assertCount(1, $this->em->getRepository('UnitedCMSCoreBundle:User')->findAll());
    }
}
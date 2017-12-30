<?php

namespace src\UnitedCMS\CoreBundle\Tests\Security;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UnitedCMS\CoreBundle\Entity\ApiClient;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Entity\Setting;
use UnitedCMS\CoreBundle\Entity\SettingType;
use UnitedCMS\CoreBundle\Security\SettingVoter;
use UnitedCMS\CoreBundle\Tests\SecurityVoterTestCase;

class SettingVoterApiClientTest extends SecurityVoterTestCase
{

    /**
     * @var Domain
     */
    protected $domain1;

    /**
     * @var Domain
     */
    protected $domain2;

    /**
     * @var Setting
     */
    protected $setting1;

    /**
     * @var Setting
     */
    protected $setting2;

    /**
     * @var SettingType
     */
    protected $settingType1;

    /**
     * @var SettingType
     */
    protected $settingType2;

    public function setUp()
    {
        parent::setUp();

        $this->domain1 = new Domain();
        $this->domain1->setOrganization($this->org1);

        $this->domain2 = new Domain();
        $this->domain2->setOrganization($this->org2);

        $this->settingType1 = new SettingType();
        $this->settingType1->setDomain($this->domain1);
        $p1 = $this->settingType1->getPermissions();
        $p1[SettingVoter::UPDATE] = [Domain::ROLE_ADMINISTRATOR];
        $this->settingType1->setPermissions($p1);

        $this->settingType2 = new SettingType();
        $this->settingType2->setDomain($this->domain2);

        $this->setting1 = new Setting();
        $this->setting1->setSettingType($this->settingType1);

        $this->setting2 = new Setting();
        $this->setting2->setSettingType($this->settingType2);

        $admin = new ApiClient();
        $admin->setRoles([Domain::ROLE_ADMINISTRATOR]);
        $admin->setDomain($this->domain1);
        $this->u['domain_admin'] = new UsernamePasswordToken($admin, 'password', 'main', []);

        $user = new ApiClient();
        $user->setRoles([Domain::ROLE_EDITOR]);
        $user->setDomain($this->domain1);
        $this->u['domain_editor'] = new UsernamePasswordToken($user, 'password', 'main', []);
    }

    public function testCRUDActions()
    {

        $dm = $this->container->get('security.authorization_checker');

        // All other users can preform the actions they have access to.
        $this->container->get('security.token_storage')->setToken($this->u['domain_admin']);
        $this->assertTrue($dm->isGranted([SettingVoter::VIEW], $this->setting1));
        $this->assertTrue($dm->isGranted([SettingVoter::UPDATE], $this->setting1));

        $this->assertFalse($dm->isGranted([SettingVoter::VIEW], $this->setting2));
        $this->assertFalse($dm->isGranted([SettingVoter::UPDATE], $this->setting2));

        $this->container->get('security.token_storage')->setToken($this->u['domain_editor']);
        $this->assertFalse($dm->isGranted([SettingVoter::VIEW], $this->setting1));
        $this->assertFalse($dm->isGranted([SettingVoter::UPDATE], $this->setting1));

        $this->assertFalse($dm->isGranted([SettingVoter::VIEW], $this->setting2));
        $this->assertFalse($dm->isGranted([SettingVoter::UPDATE], $this->setting2));
    }
}
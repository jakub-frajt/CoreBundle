<?php

namespace UnitedCMS\CoreBundle\Tests\Entity;

use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Entity\DomainMember;
use UnitedCMS\CoreBundle\Entity\Organization;
use UnitedCMS\CoreBundle\Entity\OrganizationMember;
use UnitedCMS\CoreBundle\Entity\User;
use UnitedCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class RemoveMemberTest extends DatabaseAwareTestCase
{

    public function testRemoveOrganizationMemberShouldDeleteDomainMembers()
    {

        $org1 = new Organization();
        $org1->setTitle('Org1')->setIdentifier('org1');
        $org2 = new Organization();
        $org2->setTitle('Org2')->setIdentifier('org2');
        $user = new User();
        $user->setEmail('user@example.com')->setFirstname('User')->setLastname('User')->setPassword('XXX');
        $domain1 = new Domain();
        $domain2 = new Domain();
        $domain1->setTitle('Domain')->setIdentifier('domain');
        $domain2->setTitle('Domain')->setIdentifier('domain');
        $org1->addDomain($domain1);
        $org2->addDomain($domain2);

        $domainMember1 = new DomainMember();
        $domainMember1->setDomain($domain1);
        $domainMember2 = new DomainMember();
        $domainMember2->setDomain($domain2);
        $orgMember1 = new OrganizationMember();
        $orgMember1->setOrganization($org1);
        $orgMember2 = new OrganizationMember();
        $orgMember2->setOrganization($org2);

        $user
            ->addOrganization($orgMember1)
            ->addOrganization($orgMember2)
            ->addDomain($domainMember1)
            ->addDomain($domainMember2);

        $this->assertCount(0, $this->container->get('validator')->validate($user));
        $this->em->persist($org1);
        $this->em->persist($org2);
        $this->em->persist($domain1);
        $this->em->persist($domain2);
        $this->em->persist($user);
        $this->em->flush();
        $this->em->refresh($user);
        $this->em->refresh($org1);
        $this->em->refresh($org2);
        $this->em->refresh($domain1);
        $this->em->refresh($domain2);

        // Removing the user from org 1 should only remove it from domain 1 and not domain2
        $this->em->remove($user->getOrganizations()->first());
        $this->em->flush();
        $this->em->refresh($user);
        $this->em->refresh($org1);
        $this->em->refresh($org2);
        $this->em->refresh($domain1);
        $this->em->refresh($domain2);

        $this->assertCount(0, $org1->getUsers());
        $this->assertCount(1, $org2->getUsers());
        $this->assertCount(0, $org1->getDomains()->first()->getUsers());
        $this->assertCount(1, $org2->getDomains()->first()->getUsers());
        $this->assertCount(1, $user->getDomains());
        $this->assertCount(1, $user->getOrganizations());
    }
}
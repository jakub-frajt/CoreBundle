<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\FieldablePreview;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\Entity\Webhook;
use UniteCMS\CoreBundle\Field\FieldableValidation;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class ContentTypeEntityPersistentTest extends DatabaseAwareTestCase
{

    public function testValidateContentType()
    {

        // Try to validate empty ContentType.
        $contentType = new ContentType();
        $contentType->setIdentifier('')->setTitle('')->setDescription('')->setIcon('');
        $errors = static::$container->get('validator')->validate($contentType);
        $this->assertCount(3, $errors);

        $this->assertEquals('title', $errors->get(0)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(0)->getMessageTemplate());

        $this->assertEquals('identifier', $errors->get(1)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(1)->getMessageTemplate());

        $this->assertEquals('domain', $errors->get(2)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(2)->getMessageTemplate());

        // Try to save a too long icon name or an icon name with special chars.
        $contentType->setTitle('ct1')->setIdentifier('ct1')->setDomain(new Domain());
        $contentType->setIcon($this->generateRandomMachineName(256));
        $errors = static::$container->get('validator')->validate($contentType);
        $this->assertCount(1, $errors);
        $this->assertEquals('icon', $errors->get(0)->getPropertyPath());
        $this->assertEquals('too_long', $errors->get(0)->getMessageTemplate());

        $contentType->setIcon('# ');
        $errors = static::$container->get('validator')->validate($contentType);
        $this->assertCount(1, $errors);
        $this->assertEquals('icon', $errors->get(0)->getPropertyPath());
        $this->assertEquals('invalid_characters', $errors->get(0)->getMessageTemplate());

        // Try to save invalid title.
        $contentType->setIcon(null)->setTitle($this->generateRandomUTF8String(256));
        $errors = static::$container->get('validator')->validate($contentType);
        $this->assertCount(1, $errors);
        $this->assertEquals('title', $errors->get(0)->getPropertyPath());
        $this->assertEquals('too_long', $errors->get(0)->getMessageTemplate());

        // Try to save invalid identifier.
        $contentType->setTitle($this->generateRandomUTF8String(255))->setIdentifier('X ');
        $errors = static::$container->get('validator')->validate($contentType);
        $this->assertCount(1, $errors);
        $this->assertEquals('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('invalid_characters', $errors->get(0)->getMessageTemplate());

        // Try to save invalid identifier.
        $contentType->setIdentifier('1abc');
        $errors = static::$container->get('validator')->validate($contentType);
        $this->assertCount(1, $errors);
        $this->assertEquals('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('invalid_characters', $errors->get(0)->getMessageTemplate());

        $contentType->setIdentifier($this->generateRandomMachineName(201));
        $errors = static::$container->get('validator')->validate($contentType);
        $this->assertCount(1, $errors);
        $this->assertEquals('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('too_long', $errors->get(0)->getMessageTemplate());

        $contentType->setIdentifier('valid')->setValidations([new FieldableValidation('invalid')]);
        $errors = static::$container->get('validator')->validate($contentType);
        $this->assertCount(1, $errors);
        $this->assertEquals('validations[0][expression]', $errors->get(0)->getPropertyPath());
        $this->assertEquals('invalid_validations', $errors->get(0)->getMessageTemplate());

        $contentType->setIdentifier('valid')->setValidations([new FieldableValidation('1 == 1')]);
        $this->assertCount(0, static::$container->get('validator')->validate($contentType));

        // Validate different previews.
        $contentType->setPreview(null);
        $this->assertCount(0, static::$container->get('validator')->validate($contentType));

        $contentType->setPreview(new FieldablePreview('', ''));
        $errors = static::$container->get('validator')->validate($contentType);
        $this->assertCount(2, $errors);
        $this->assertEquals('preview.url', $errors->get(0)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('preview.query', $errors->get(1)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(1)->getMessageTemplate());

        $contentType->setPreview(new FieldablePreview('XXX', 'query { type }'));
        $errors = static::$container->get('validator')->validate($contentType);
        $this->assertCount(1, $errors);
        $this->assertEquals('preview.url', $errors->get(0)->getPropertyPath());
        $this->assertEquals('invalid_url', $errors->get(0)->getMessageTemplate());

        $contentType->setPreview(new FieldablePreview('example.com', 'query { type }'));
        $errors = static::$container->get('validator')->validate($contentType);
        $this->assertCount(1, $errors);
        $this->assertEquals('preview.url', $errors->get(0)->getPropertyPath());
        $this->assertEquals('invalid_url', $errors->get(0)->getMessageTemplate());

        $contentType->setPreview(new FieldablePreview('ftp://example.com', 'query { type }'));
        $errors = static::$container->get('validator')->validate($contentType);
        $this->assertCount(1, $errors);
        $this->assertEquals('preview.url', $errors->get(0)->getPropertyPath());
        $this->assertEquals('invalid_url', $errors->get(0)->getMessageTemplate());

        $contentType->setPreview(new FieldablePreview('https://example.com/' . str_replace('_', 'a', $this->generateRandomMachineName(255)), 'query { type }'));
        $errors = static::$container->get('validator')->validate($contentType);
        $this->assertCount(1, $errors);
        $this->assertEquals('preview.url', $errors->get(0)->getPropertyPath());
        $this->assertEquals('too_long', $errors->get(0)->getMessageTemplate());
        
        $contentType->setPreview(new FieldablePreview('https://example.com', 'XXX'));
        $errors = static::$container->get('validator')->validate($contentType);
        $this->assertCount(1, $errors);
        $this->assertEquals('preview.query', $errors->get(0)->getPropertyPath());
        $this->assertEquals('invalid_query', $errors->get(0)->getMessageTemplate());

        $contentType->setPreview(new FieldablePreview('https://example.com', 'type'));
        $errors = static::$container->get('validator')->validate($contentType);
        $this->assertCount(1, $errors);
        $this->assertEquals('preview.query', $errors->get(0)->getPropertyPath());
        $this->assertEquals('invalid_query', $errors->get(0)->getMessageTemplate());

        $contentType->setPreview(new FieldablePreview('https://example.com', 'query {}'));
        $errors = static::$container->get('validator')->validate($contentType);
        $this->assertCount(1, $errors);
        $this->assertEquals('preview.query', $errors->get(0)->getPropertyPath());
        $this->assertEquals('invalid_query', $errors->get(0)->getMessageTemplate());

        $contentType->setPreview(new FieldablePreview('https://example.com', 'foo { type }'));
        $errors = static::$container->get('validator')->validate($contentType);
        $this->assertCount(1, $errors);
        $this->assertEquals('preview.query', $errors->get(0)->getPropertyPath());
        $this->assertEquals('invalid_query', $errors->get(0)->getMessageTemplate());

        $contentType->setPreview(new FieldablePreview('https://example.com', 'query { foo }'));
        $this->assertCount(0, static::$container->get('validator')->validate($contentType));

        $contentType->setPreview(new FieldablePreview('https://example.com', 'query { foo { baa } }'));
        $this->assertCount(0, static::$container->get('validator')->validate($contentType));

        $contentType->setPreview(new FieldablePreview('https://example.com', '{ type }'));
        $this->assertCount(0, static::$container->get('validator')->validate($contentType));

        // validate Webhooks 
        $contentType->setWebhooks([]);
        $this->assertCount(0, static::$container->get('validator')->validate($contentType));

        $contentType->setWebhooks([
            new Webhook('', '', ''),
            new Webhook('XXX', 'XXX', 'csd <= ', 'csd <= ', -1),
            new Webhook('query { foo { baa } }', 'https://www.orf.at' . str_replace('_', 'a', $this->generateRandomMachineName(255)), 'event == "'.$this->generateRandomUTF8String(255).'"', true, $this->generateRandomUTF8String(255)),
            new Webhook('query { type }', 'https://www.orf.at', 'event == "update"', true, 'test121212', 'chuck norris'),
        ]);
        $errors = static::$container->get('validator')->validate($contentType);
        $this->assertCount(11, $errors);
        $this->assertEquals('webhooks[0].query', $errors->get(0)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('webhooks[0].url', $errors->get(1)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(1)->getMessageTemplate());
        $this->assertEquals('webhooks[0].condition', $errors->get(2)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(2)->getMessageTemplate());
        $this->assertEquals('webhooks[1].query', $errors->get(3)->getPropertyPath());
        $this->assertEquals('invalid_query', $errors->get(3)->getMessageTemplate());
        $this->assertEquals('webhooks[1].url', $errors->get(4)->getPropertyPath());
        $this->assertEquals('invalid_url', $errors->get(4)->getMessageTemplate());
        $this->assertEquals('webhooks[1].authentication_header', $errors->get(5)->getPropertyPath());
        $this->assertEquals('too_short', $errors->get(5)->getMessageTemplate());
        $this->assertEquals('webhooks[1].condition', $errors->get(6)->getPropertyPath());
        $this->assertEquals('invalid_expression', $errors->get(6)->getMessageTemplate());
        $this->assertEquals('webhooks[2].url', $errors->get(7)->getPropertyPath());
        $this->assertEquals('too_long', $errors->get(7)->getMessageTemplate());
        $this->assertEquals('webhooks[2].authentication_header', $errors->get(8)->getPropertyPath());
        $this->assertEquals('too_long', $errors->get(8)->getMessageTemplate());
        $this->assertEquals('webhooks[2].condition', $errors->get(9)->getPropertyPath());
        $this->assertEquals('too_long', $errors->get(9)->getMessageTemplate());
        $this->assertEquals('webhooks[3].content_type', $errors->get(10)->getPropertyPath());
        $this->assertEquals('invalid_webhook_content_type', $errors->get(10)->getMessageTemplate());

        $contentType->setWebhooks([
            new Webhook('{ type }', 'http://www.orf.at', 'event == "update"', false, 'abc12234234basdf'),
            new Webhook('query { foo { baa } }', 'https://www.orf.at', 'event == "delete"', true, ''),
            new Webhook('query { foo { baa } }', 'https://www.orf.at', 'event == "delete"', true, '', 'form_data')
        ]);
        $this->assertCount(0, static::$container->get('validator')->validate($contentType));
        
        // There can only be one identifier per domain with the same identifier.
        $org1 = new Organization();
        $org1->setIdentifier('org1')->setTitle('Org 1');
        $org2 = new Organization();
        $org2->setIdentifier('org2')->setTitle('Org 2');
        $domain1 = new Domain();
        $domain1->setIdentifier('org1_domain1')->setTitle('Domain11');
        $domain2 = new Domain();
        $domain2->setIdentifier('org1_domain2')->setTitle('Domain12');
        $domain21 = new Domain();
        $domain21->setIdentifier('org2_domain1')->setTitle('Domain21');
        $domain22 = new Domain();
        $domain22->setIdentifier('org2_domain2')->setTitle('Domain22');
        $org1->addDomain($domain1)->addDomain($domain21);
        $org2->addDomain($domain2)->addDomain($domain22);
        $contentType = new ContentType();
        $contentType->setIdentifier('org1_domain1_ct1')->setTitle('org1_domain1_ct1')->setDomain($domain1);
        $this->em->persist($org1);
        $this->em->persist($org2);
        $this->em->persist($domain1);
        $this->em->persist($domain2);
        $this->em->persist($domain21);
        $this->em->persist($domain22);
        $this->em->persist($contentType);
        $this->em->flush($contentType);
        $this->assertCount(0, static::$container->get('validator')->validate($contentType));

        // CT2 one the same domain with the same identifier should not be valid.
        $ct2 = new ContentType();
        $ct2->setIdentifier('org1_domain1_ct1')->setTitle('org1_domain1_ct1')->setDomain($domain1);
        $this->assertCount(1, static::$container->get('validator')->validate($ct2));

        $ct2->setIdentifier('org1_domain1_ct2');
        $this->assertCount(0, static::$container->get('validator')->validate($ct2));

        $ct2->setIdentifier('org1_domain1_ct1')->setDomain($domain2);
        $this->assertCount(0, static::$container->get('validator')->validate($ct2));

        $ct2->setIdentifier('org1_domain1_ct1')->setDomain($domain21);
        $this->assertCount(0, static::$container->get('validator')->validate($ct2));

        $ct2->setIdentifier('org1_domain1_ct1')->setDomain($domain22);
        $this->assertCount(0, static::$container->get('validator')->validate($ct2));


        // Try to set invalid permissions.
        $contentType->setPermissions(['invalid' => '(((']);
        $errors = static::$container->get('validator')->validate($contentType);
        $this->assertCount(1, $errors);
        $this->assertEquals('permissions', $errors->get(0)->getPropertyPath());
        $this->assertEquals('invalid_selection', $errors->get(0)->getMessageTemplate());

        // Test invalid view.
        $contentType->setPermissions([])->addView(new View());
        $errors = static::$container->get('validator')->validate($contentType);
        $this->assertGreaterThanOrEqual(1, $errors->count());
        $this->assertStringStartsWith('views', $errors->get(0)->getPropertyPath());

        // Test ContentType without all view.
        $contentType->getViews()->clear();
        $errors = static::$container->get('validator')->validate($contentType);
        $this->assertCount(1, $errors);
        $this->assertEquals('views', $errors->get(0)->getPropertyPath());
        $this->assertEquals('missing_default_view', $errors->get(0)->getMessageTemplate());

        // Test unique entity validation.
        $contentType2 = new ContentType();
        $contentType2->setTitle($contentType->getTitle())->setIdentifier($contentType->getIdentifier())->setDomain(
            $contentType->getDomain()
        );
        $errors = static::$container->get('validator')->validate($contentType2);
        $this->assertCount(1, $errors);

        $this->assertEquals('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('identifier_already_taken', $errors->get(0)->getMessageTemplate());

        // Deleting the "all" view from a contentType should not work.
        $ct3 = new ContentType();
        $ct3->setTitle('CT3')->setIdentifier('ct3')->setDomain($contentType->getDomain());
        $this->assertTrue($ct3->getViews()->containsKey(View::DEFAULT_VIEW_IDENTIFIER));

        $errors = static::$container->get('validator')->validate($ct3);
        $this->assertCount(0, $errors);

        $ct3->getViews()->remove(View::DEFAULT_VIEW_IDENTIFIER);
        $errors = static::$container->get('validator')->validate($ct3);
        $this->assertCount(1, $errors);
        $this->assertEquals('views', $errors->get(0)->getPropertyPath());
        $this->assertEquals('missing_default_view', $errors->get(0)->getMessageTemplate());

        // Try to delete other view
        $other_view = new View();
        $other_view->setIdentifier('other')->setTitle('Other')->setType('table');
        $ct3->setViews([$other_view]);
        $this->assertTrue($ct3->getViews()->containsKey(View::DEFAULT_VIEW_IDENTIFIER));
        $this->assertTrue($ct3->getViews()->containsKey('other'));

        $errors = static::$container->get('validator')->validate($ct3);
        $this->assertCount(0, $errors);
        $this->em->persist($ct3);
        $this->em->flush($ct3);
        $this->em->refresh($ct3);

        $ct3->getViews()->remove('other');
        $errors = static::$container->get('validator')->validate($ct3);
        $this->assertCount(0, $errors);
        $this->em->persist($ct3);
        $this->em->flush($ct3);
        $ct3 = $this->em->find('UniteCMSCoreBundle:ContentType', $ct3->getId());
        $this->assertFalse($ct3->getViews()->containsKey('other'));
    }

    public function testContentTypeWeight()
    {
        $org = new Organization();
        $org->setTitle('Org')->setIdentifier('org');
        $domain = new Domain();
        $domain->setOrganization($org)->setTitle('Domain')->setIdentifier('domain');

        $ct1 = new ContentType();
        $ct1->setIdentifier('ct1')->setTitle('CT1');
        $domain->addContentType($ct1);

        $ct2 = new ContentType();
        $ct2->setIdentifier('ct2')->setTitle('CT2');
        $domain->addContentType($ct2);

        $this->em->persist($org);
        $this->em->persist($domain);
        $this->em->flush();
        $this->em->refresh($ct1);
        $this->em->refresh($ct2);
        $this->assertEquals(0, $ct1->getWeight());
        $this->assertEquals(1, $ct2->getWeight());

        // Reorder
        $reorderedDomain = new Domain();
        $reorderedDomain->setOrganization($org)->setTitle($domain->getTitle())->setIdentifier($domain->getIdentifier());

        $ct1_clone = clone $ct1;
        $ct1_clone->setWeight(null);
        $ct2_clone = clone $ct2;
        $ct2_clone->setWeight(null);
        $reorderedDomain->addContentType($ct2_clone)->addContentType($ct1_clone);
        $domain->setFromEntity($reorderedDomain);

        $this->em->flush($domain);
        $this->em->refresh($domain);
        $this->assertEquals(1, $ct1->getWeight());
        $this->assertEquals(0, $ct2->getWeight());
    }

    public function testReservedIdentifiers()
    {
        $reserved = ContentType::RESERVED_IDENTIFIERS;
        $this->assertNotEmpty($reserved);

        $ct = new ContentType();
        $ct->setTitle('title')->setIdentifier(array_pop($reserved))->setDomain(new Domain());
        $errors = static::$container->get('validator')->validate($ct);
        $this->assertCount(1, $errors);
        $this->assertStringStartsWith('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('reserved_identifier', $errors->get(0)->getMessageTemplate());
    }

    public function testFindByIdentifiers()
    {
        $org = new Organization();
        $org->setTitle('Org')->setIdentifier('org');
        $this->em->persist($org);
        $this->em->flush($org);

        $domain = new Domain();
        $domain->setTitle('Domain')->setIdentifier('domain')->setOrganization($org);
        $this->em->persist($domain);
        $this->em->flush($domain);

        $contentType = new ContentType();
        $contentType->setIdentifier('ct1')->setTitle('Ct1')->setDomain($domain);
        $this->em->persist($contentType);
        $this->em->flush($contentType);

        // Try to find with invalid identifiers.
        $repo = $this->em->getRepository('UniteCMSCoreBundle:ContentType');
        $this->assertNull($repo->findByIdentifiers('foo', 'baa', 'luu'));
        $this->assertNull($repo->findByIdentifiers('org', 'baa', 'luu'));
        $this->assertNull($repo->findByIdentifiers('foo', 'domain', 'luu'));
        $this->assertNull($repo->findByIdentifiers('org', 'domain', 'luu'));
        $this->assertNull($repo->findByIdentifiers('foo', 'domain', 'ct1'));
        $this->assertNull($repo->findByIdentifiers('org', 'baa', 'ct1'));

        // Try to find with valid identifier.
        $this->assertEquals($contentType, $repo->findByIdentifiers('org', 'domain', 'ct1'));
    }

    public function testContentLabelProperty()
    {
        $ct = new ContentType();
        $this->assertEquals('{type} #{id}', $ct->getContentLabel());
        $this->assertEquals('Foo', $ct->setContentLabel('Foo')->getContentLabel());
    }

    public function testPersistAndLoadFieldableValidations() {

        $org = new Organization();
        $org->setTitle('Org')->setIdentifier('org');
        $this->em->persist($org);

        $domain = new Domain();
        $domain->setTitle('Domain')->setIdentifier('domain')->setOrganization($org);
        $this->em->persist($domain);

        $ct = new ContentType();
        $ct->setIdentifier('ct1')->setTitle('Ct1')->setDomain($domain);
        $ct->setValidations([
            new FieldableValidation('true', 'first validation', 'field1'),
            new FieldableValidation('true', '2nd validation', 'field2', ['DELETE'])
        ]);
        $ct->addValidation(new FieldableValidation('false', '3rd validation'));

        $this->em->persist($ct);
        $this->em->flush($ct);
        $ct->setValidations([]);
        $this->em->clear();

        $ct_reloaded = $this->em->getRepository('UniteCMSCoreBundle:ContentType')->findOneBy(['title' => 'Ct1']);

        $this->assertEquals($ct_reloaded->getValidations(), [
            new FieldableValidation('true', 'first validation', 'field1'),
            new FieldableValidation('true', '2nd validation', 'field2', ['DELETE']),
            new FieldableValidation('false', '3rd validation')
        ]);
    }
}

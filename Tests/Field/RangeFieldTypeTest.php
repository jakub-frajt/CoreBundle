<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class RangeFieldTypeTest extends FieldTypeTestCase
{
    public function testContentTypeFieldTypeWithEmptySettings()
    {

        // Content Type Field with empty settings should be valid.
        $ctField = $this->createContentTypeField('range');
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }

    public function testContentTypeFieldTypeWithInvalidSettings()
    {

        // Content Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('range');
        $ctField->setSettings(new FieldableFieldSettings(['min' => 0, 'max' => 100, 'step' => 1, 'foo' => 'baa']));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());

        // validate initial data
        $ctField->setSettings(new FieldableFieldSettings(['min' => 0, 'max' => 100, 'step' => 1, 'default' => 'baa']));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('invalid_initial_data', $errors->get(0)->getMessageTemplate());
    }

    public function testContentTypeFieldTypeWithValidSettings()
    {

        // Content Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('range');
        $ctField->setSettings(new FieldableFieldSettings(['min' => 0, 'max' => 100, 'step' => 1, 'default' => 50]));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }
}

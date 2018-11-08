<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class ChoiceFieldTypeTest extends FieldTypeTestCase
{
    public function testContentTypeFieldTypeWithEmptySettings()
    {

        // Content Type Field with empty settings should not be valid.
        $ctField = $this->createContentTypeField('choice');
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('required', $errors->get(0)->getMessageTemplate());
    }

    public function testContentTypeFieldTypeWithInvalidSettings()
    {

        // Content Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('choice');

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'choices' => ['foo' => 'baa'],
                'foo' => 'baa',
                'not_empty' => 123,
                'default' => true
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(3, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('noboolean_value', $errors->get(1)->getMessageTemplate());
        $this->assertEquals('invalid_initial_data', $errors->get(2)->getMessageTemplate());

        // check wrong empty data
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'choices' => ['foo1' => 'foo1', 'foo2' => 'foo2'],
                'default' => 'baa'
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('initial_data_not_inside_values', $errors->get(0)->getMessageTemplate());

    }

    public function testContentTypeFieldTypeWithValidSettings()
    {
        // Content Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('choice');

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'choices' => ['foo' => 'baa'],
                'default' => 'baa'
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }
}

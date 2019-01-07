<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 17.05.18
 * Time: 16:43
 */

namespace UniteCMS\CoreBundle\Tests\Expression;

use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use UniteCMS\CoreBundle\Expression\PlainExpressionLanguage;

define('TEST_PLAIN_EXPRESSION_LANGUAGE_TEST_CONSTANT', 'foo');

class PlainExpressionLanguageTest extends TestCase
{

    private $expression;

    public function setUp()
    {
        $this->expression = 'constant("TEST_PLAIN_EXPRESSION_LANGUAGE_TEST_CONSTANT")';
    }

    public function testConstantAvailable() {
        $lang = new ExpressionLanguage();
        $this->assertEquals('foo', $lang->evaluate($this->expression));
    }

    /**
     * @expectedException \Symfony\Component\ExpressionLanguage\SyntaxError
     */
    public function testConstantNotAvailable() {
        $plainLang = new PlainExpressionLanguage();
        $this->assertNull($plainLang->evaluate($this->expression));
    }
}
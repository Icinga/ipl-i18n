<?php

namespace ipl\Tests\I18n;

use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;

use function ipl\I18n\t;
use function ipl\I18n\tp;

class FunctionsTest extends \PHPUnit\Framework\TestCase
{
    public function testFunctionT()
    {
        StaticTranslator::$instance = new NoopTranslator();

        $this->assertEquals('test', t('test'));
    }

    public function testFunctionTp()
    {
        StaticTranslator::$instance = new NoopTranslator();

        $this->assertEquals('test', tp('test', 'tests', 1));
        $this->assertEquals('tests', tp('test', 'tests', 2));
    }
}

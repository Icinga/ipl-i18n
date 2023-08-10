<?php

namespace ipl\Tests\I18n;

use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\I18n\Translation;
use PHPUnit\Framework\TestCase;

class TranslationTest extends TestCase
{
    protected function setUp(): void
    {
        StaticTranslator::$instance = new NoopTranslator();
    }

    public function testNullCountsAreProperlyHandledByTranslatePlural()
    {
        $this->assertSame(
            'not one',
            $this->createTestClassWithoutDomain()
                ->translatePlural('one', 'not one', null)
        );
        $this->assertSame(
            'not one',
            $this->createTestClassWithDomain()
                ->translatePlural('one', 'not one', null)
        );
    }

    public function testNullCountsAreProperlyHandledByTranslatePluralInDomain()
    {
        $this->assertSame(
            'not one',
            $this->createTestClassWithoutDomain()
                ->translatePluralInDomain('test', 'one', 'not one', null)
        );
    }

    private function createTestClassWithoutDomain()
    {
        return new class {
            use Translation;
        };
    }

    private function createTestClassWithDomain()
    {
        return new class {
            use Translation;

            public function __construct()
            {
                $this->translationDomain = 'test';
            }
        };
    }
}

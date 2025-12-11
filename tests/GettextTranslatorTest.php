<?php

namespace ipl\Tests\I18n;

use ipl\I18n\GettextTranslator;

class GettextTranslatorTest extends \PHPUnit\Framework\TestCase
{
    private const TRANSLATIONS = __DIR__ . '/locale';

    public function testGetDefaultDomain()
    {
        $this->assertSame('default', (new GettextTranslator())->getDefaultDomain());
    }

    public function testSetDefaultDomain()
    {
        $this->assertSame(
            'special',
            (new GettextTranslator())->setDefaultDomain('special')->getDefaultDomain()
        );
    }

    public function testGetDefaultLocale()
    {
        $this->assertSame('en_US', (new GettextTranslator())->getDefaultLocale());
    }

    public function testSetDefaultLocale()
    {
        $this->assertSame(
            'de_DE',
            (new GettextTranslator())->setDefaultLocale('de_DE')->getDefaultLocale()
        );
    }

    public function testGetTranslationDirectoriesReturnsAnEmptyArrayIfNoTranslationAdded()
    {
        $this->assertSame([], (new GettextTranslator())->getTranslationDirectories());
    }

    public function testAddTranslationDirectoryWithDefaultDomain()
    {
        $translator = (new GettextTranslator())
            ->addTranslationDirectory(static::TRANSLATIONS);

        $this->assertSame(
            [
                $translator->getDefaultDomain() => static::TRANSLATIONS
            ],
            $translator->getTranslationDirectories()
        );
    }

    public function testAddTranslationDirectoryWithSpecialDomain()
    {
        $translator = (new GettextTranslator())
            ->addTranslationDirectory(static::TRANSLATIONS, 'special');

        $this->assertSame(
            [
                'special' => static::TRANSLATIONS
            ],
            $translator->getTranslationDirectories()
        );
    }

    public function testGetLoadedTranslationsReturnsAnEmptyArrayIfNoTranslationLoaded()
    {
        $this->assertSame([], (new GettextTranslator())->getLoadedTranslations());
    }

    public function testLoadTranslationAndGetLoadedTranslations()
    {
        $translator = (new GettextTranslator())
            ->addTranslationDirectory(static::TRANSLATIONS, 'special')
            ->addTranslationDirectory(static::TRANSLATIONS)
            ->loadTranslations();

        $this->assertSame(
            [
                'special'                       => static::TRANSLATIONS,
                $translator->getDefaultDomain() => static::TRANSLATIONS
            ],
            $translator->getLoadedTranslations()
        );
    }

    public function testLoadTranslationRegistersTheCorrectCodeset()
    {
        $translator = (new GettextTranslator())
            ->addTranslationDirectory(static::TRANSLATIONS, 'special')
            ->setLocale('de_DE');

        $this->assertEquals('Ümläüt€', $translator->translateInDomain('special', 'Umlauts'));
    }

    public function testGetLocaleReturnsNullIfNoLocaleSetUp()
    {
        $this->assertNull((new GettextTranslator())->getLocale());
    }

    public function testSetLocaleAndGetLocale()
    {
        $translator = (new GettextTranslator())
            ->addTranslationDirectory(static::TRANSLATIONS)
            ->setLocale('de_DE');

        $this->assertSame('de_DE.UTF-8', getenv('LANGUAGE'));
        $this->assertSame('de_DE.UTF-8', setlocale(LC_ALL, 0));
        $this->assertSame('de_DE', $translator->getLocale());
        $this->assertSame(
            $translator->getDefaultDomain(),
            textdomain(null)
        );
        $this->assertSame(
            [
                $translator->getDefaultDomain() => static::TRANSLATIONS
            ],
            $translator->getLoadedTranslations()
        );
    }

    public function testEncodeMessageWithContext()
    {
        $this->assertSame(
            "context\x04message",
            (new GettextTranslator())->encodeMessageWithContext('message', 'context')
        );
    }

    public function testListLocales()
    {
        $this->assertSame(
            ['de_DE', 'it_IT'],
            (new GettextTranslator())
                ->addTranslationDirectory(static::TRANSLATIONS)
                ->addTranslationDirectory(static::TRANSLATIONS, 'special')
                ->listLocales()
        );
    }

    public function testTranslate()
    {
        $translator = (new GettextTranslator())
            ->addTranslationDirectory(static::TRANSLATIONS)
            ->setLocale('de_DE');

        $this->assertSame('Benutzer', $translator->translate('user'));
    }

    public function testTranslateWithContext()
    {
        $translator = (new GettextTranslator())
            ->addTranslationDirectory(static::TRANSLATIONS)
            ->setLocale('de_DE');

        $this->assertSame('Anfrage', $translator->translate('request', 'context'));
    }

    public function testTranslateInDomain()
    {
        $translator = (new GettextTranslator())
            ->addTranslationDirectory(static::TRANSLATIONS, 'special')
            ->setLocale('de_DE');

        $this->assertSame('Benutzer (special)', $translator->translateInDomain('special', 'user'));
    }

    public function testTranslateInDomainWithContext()
    {
        $translator = (new GettextTranslator())
            ->addTranslationDirectory(static::TRANSLATIONS, 'special')
            ->setLocale('de_DE');

        $this->assertSame('Anfrage (special)', $translator->translateInDomain('special', 'request', 'context'));
    }

    public function testTranslateInDomainUsesDefaultDomainAsFallback()
    {
        $translator = (new GettextTranslator())
            ->addTranslationDirectory(static::TRANSLATIONS, 'special')
            ->addTranslationDirectory(static::TRANSLATIONS)
            ->setLocale('de_DE');

        $this->assertSame('Gruppe', $translator->translateInDomain('special', 'group'));
    }

    public function testTranslatePlural()
    {
        $translator = (new GettextTranslator())
            ->addTranslationDirectory(static::TRANSLATIONS)
            ->setLocale('de_DE');

        $this->assertSame(
            'ein Benutzer',
            $translator->translatePlural('%d user', '%d user', 1)
        );

        $this->assertSame(
            '42 Benutzer',
            sprintf($translator->translatePlural('%d user', '%d user', 42), 42)
        );
    }

    public function testTranslatePluralWithContext()
    {
        $translator = (new GettextTranslator())
            ->addTranslationDirectory(static::TRANSLATIONS)
            ->setLocale('de_DE');

        $this->assertSame(
            'eine Anfrage',
            $translator->translatePlural('%d request', '%d requests', 1, 'context')
        );

        $this->assertSame(
            '42 Anfragen',
            sprintf(
                $translator->translatePlural('%d request', '%d requests', 42, 'context'),
                42
            )
        );
    }

    public function testTranslatePluralInDomain()
    {
        $translator = (new GettextTranslator())
            ->addTranslationDirectory(static::TRANSLATIONS, 'special')
            ->setLocale('de_DE');

        $this->assertSame(
            'ein Benutzer (special)',
            $translator->translatePluralInDomain('special', '%d user', '%d user', 1)
        );

        $this->assertSame(
            '42 Benutzer (special)',
            sprintf(
                $translator->translatePluralInDomain('special', '%d user', '%d user', 42),
                42
            )
        );
    }

    public function testTranslatePluralInDomainWithContext()
    {
        $translator = (new GettextTranslator())
            ->addTranslationDirectory(static::TRANSLATIONS, 'special')
            ->setLocale('de_DE');

        $this->assertSame(
            'eine Anfrage (special)',
            $translator->translatePluralInDomain('special', '%d request', '%d requests', 1, 'context')
        );

        $this->assertSame(
            '42 Anfragen (special)',
            sprintf(
                $translator->translatePluralInDomain('special', '%d request', '%d requests', 42, 'context'),
                42
            )
        );
    }

    public function testTranslatePluralInDomainUsesDefaultDomainAsFallback()
    {
        $translator = (new GettextTranslator())
            ->addTranslationDirectory(static::TRANSLATIONS)
            ->addTranslationDirectory(static::TRANSLATIONS, 'special')
            ->setLocale('de_DE');

        $this->assertSame(
            'eine Gruppe',
            $translator->translatePluralInDomain('special', '%d group', '%d groups', 1)
        );

        $this->assertSame(
            '42 Gruppen',
            sprintf(
                $translator->translatePluralInDomain('special', '%d group', '%d groups', 42),
                42
            )
        );
    }
}

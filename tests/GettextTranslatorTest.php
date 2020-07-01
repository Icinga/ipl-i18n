<?php

namespace ipl\Tests\I18n;

use ipl\I18n\GettextTranslator;

class GettextTranslatorTest extends \PHPUnit\Framework\TestCase
{
    const TRANSLATIONS = __DIR__ . '/locale';

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

    public function testGetTranslationsReturnsAnEmptyArrayIfNoTranslationAdded()
    {
        $this->assertSame([], (new GettextTranslator())->getTranslations());
    }

    public function testAddTranslationWithDefaultDomain()
    {
        $translator = (new GettextTranslator())
            ->addTranslation('de_DE', static::TRANSLATIONS);

        $this->assertSame(
            [
                'de_DE' => [
                    $translator->getDefaultDomain() => static::TRANSLATIONS
                ]
            ],
            $translator->getTranslations()
        );
    }

    public function testAddTranslationWithSpecialDomain()
    {
        $translator = (new GettextTranslator())
            ->addTranslation('de_DE', static::TRANSLATIONS, 'special');

        $this->assertSame(
            [
                'de_DE' => [
                    'special' => static::TRANSLATIONS
                ]
            ],
            $translator->getTranslations()
        );
    }

    public function testGetLoadedTranslationsReturnsAnEmptyArrayIfNoTranslationLoaded()
    {
        $this->assertSame([], (new GettextTranslator())->getLoadedTranslations());
    }

    public function testLoadTranslationAndGetLoadedTranslations()
    {
        $translator = (new GettextTranslator())
            ->addTranslation('de_DE', static::TRANSLATIONS, 'special')
            ->addTranslation('de_DE', static::TRANSLATIONS)
            ->loadTranslation('de_DE');

        $this->assertSame(
            [
                'de_DE' => [
                    'special'                       => static::TRANSLATIONS,
                    $translator->getDefaultDomain() => static::TRANSLATIONS
                ]
            ],
            $translator->getLoadedTranslations()
        );
    }

    public function testGetLocaleReturnsNullIfNoLocaleSetUp()
    {
        $this->assertNull((new GettextTranslator())->getLocale());
    }

    public function testSetLocaleAndGetLocale()
    {
        $translator = (new GettextTranslator())
            ->addTranslation('de_DE', static::TRANSLATIONS)
            ->setLocale('de_DE');

        $this->assertSame('C.UTF-8', getenv('LANGUAGE'));
        $this->assertSame('C.UTF-8', setlocale(LC_MESSAGES, null));
        $this->assertSame('de_DE', $translator->getLocale());
        $this->assertSame(
            $translator->encodeDomainWithLocale($translator->getDefaultDomain(), $translator->getLocale()),
            textdomain(null)
        );
        $this->assertSame(
            [
                'de_DE' => [
                    $translator->getDefaultDomain() => static::TRANSLATIONS
                ]
            ],
            $translator->getLoadedTranslations()
        );
    }

    public function testEncodeDomainWithLocale()
    {
        $this->assertSame(
            'domain.locale',
            (new GettextTranslator())->encodeDomainWithLocale('domain', 'locale')
        );
    }

    public function testEncodeMessageWithContext()
    {
        $this->assertSame(
            "context\x04message",
            (new GettextTranslator())->encodeMessageWithContext('message', 'context')
        );
    }

    public function testTranslate()
    {
        $translator = (new GettextTranslator())
            ->addTranslation('de_DE', static::TRANSLATIONS)
            ->setLocale('de_DE');

        $this->assertSame('Benutzer', $translator->translate('user'));
    }

    public function testTranslateWithContext()
    {
        $translator = (new GettextTranslator())
            ->addTranslation('de_DE', static::TRANSLATIONS)
            ->setLocale('de_DE');

        $this->assertSame('Anfrage', $translator->translate('request', 'context'));
    }

    public function testTranslateWithLocale()
    {
        $translator = (new GettextTranslator())
            ->addTranslation('de_DE', static::TRANSLATIONS)
            ->addTranslation('it_IT', static::TRANSLATIONS)
            ->setLocale('de_DE')
            ->loadTranslation('it_IT');

        $this->assertSame('Benutzer', $translator->translate('user'));
        $this->assertSame('utente', $translator->translate('user', null, 'it_IT'));
    }

    public function testTranslateWithLocaleAndContext()
    {
        $translator = (new GettextTranslator())
            ->addTranslation('de_DE', static::TRANSLATIONS)
            ->addTranslation('it_IT', static::TRANSLATIONS)
            ->setLocale('de_DE')
            ->loadTranslation('it_IT');

        $this->assertSame('Benutzer', $translator->translate('user'));
        $this->assertSame('richiesta', $translator->translate('request', 'context', 'it_IT'));
    }

    public function testTranslateInDomain()
    {
        $translator = (new GettextTranslator())
            ->addTranslation('de_DE', static::TRANSLATIONS, 'special')
            ->setLocale('de_DE');

        $this->assertSame('Benutzer (special)', $translator->translateInDomain('special', 'user'));
    }

    public function testTranslateInDomainWithContext()
    {
        $translator = (new GettextTranslator())
            ->addTranslation('de_DE', static::TRANSLATIONS, 'special')
            ->setLocale('de_DE');

        $this->assertSame('Anfrage (special)', $translator->translateInDomain('special', 'request', 'context'));
    }

    public function testTranslateInDomainWithLocale()
    {
        $translator = (new GettextTranslator())
            ->addTranslation('de_DE', static::TRANSLATIONS, 'special')
            ->addTranslation('it_IT', static::TRANSLATIONS, 'special')
            ->setLocale('de_DE')
            ->loadTranslation('it_IT');

        $this->assertSame('utente (special)', $translator->translateInDomain('special', 'user', null, 'it_IT'));
    }

    public function testTranslateInDomainWithLocaleAndContext()
    {
        $translator = (new GettextTranslator())
            ->addTranslation('de_DE', static::TRANSLATIONS, 'special')
            ->addTranslation('it_IT', static::TRANSLATIONS, 'special')
            ->setLocale('de_DE')
            ->loadTranslation('it_IT');

        $this->assertSame(
            'richiesta (special)',
            $translator->translateInDomain('special', 'request', 'context', 'it_IT')
        );
    }

    public function testTranslateInDomainUsesDefaultDomainAsFallback()
    {
        $translator = (new GettextTranslator())
            ->addTranslation('de_DE', static::TRANSLATIONS)
            ->addTranslation('de_DE', static::TRANSLATIONS, 'special')
            ->setLocale('de_DE');

        $this->assertSame('Gruppe', $translator->translateInDomain('special', 'group'));
    }

    public function testTranslatePlural()
    {
        $translator = (new GettextTranslator())
            ->addTranslation('de_DE', static::TRANSLATIONS)
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
            ->addTranslation('de_DE', static::TRANSLATIONS)
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

    public function testTranslatePluralWithLocale()
    {
        $translator = (new GettextTranslator())
            ->addTranslation('de_DE', static::TRANSLATIONS)
            ->addTranslation('it_IT', static::TRANSLATIONS)
            ->setLocale('de_DE')
            ->loadTranslation('it_IT');

        $this->assertSame(
            'un utente',
            $translator->translatePlural('%d user', '%d user', 1, null, 'it_IT')
        );

        $this->assertSame(
            '42 utenti',
            sprintf(
                $translator->translatePlural('%d user', '%d user', 42, null, 'it_IT'),
                42
            )
        );
    }

    public function testTranslatePluralWithLocaleAndContext()
    {
        $translator = (new GettextTranslator())
            ->addTranslation('de_DE', static::TRANSLATIONS)
            ->addTranslation('it_IT', static::TRANSLATIONS)
            ->setLocale('de_DE')
            ->loadTranslation('it_IT');

        $this->assertSame(
            'una richiesta',
            sprintf(
                $translator->translatePlural('%d request', '%d requests', 1, 'context', 'it_IT'),
                1
            )
        );

        $this->assertSame(
            '42 richieste',
            sprintf(
                $translator->translatePlural('%d request', '%d requests', 42, 'context', 'it_IT'),
                42
            )
        );
    }

    public function testTranslatePluralInDomain()
    {
        $translator = (new GettextTranslator())
            ->addTranslation('de_DE', static::TRANSLATIONS, 'special')
            ->setLocale('de_DE');

        $this->assertSame(
            'ein Benutzer (special)',
            $translator->translatePluralInDomain('special','%d user', '%d user', 1)
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
            ->addTranslation('de_DE', static::TRANSLATIONS, 'special')
            ->setLocale('de_DE');

        $this->assertSame(
            'eine Anfrage (special)',
            $translator->translatePluralInDomain('special','%d request', '%d requests', 1, 'context')
        );

        $this->assertSame(
            '42 Anfragen (special)',
            sprintf(
                $translator->translatePluralInDomain('special', '%d request', '%d requests', 42, 'context'),
                42
            )
        );
    }

    public function testTranslatePluralInDomainWithLocale()
    {
        $translator = (new GettextTranslator())
            ->addTranslation('de_DE', static::TRANSLATIONS, 'special')
            ->addTranslation('it_IT', static::TRANSLATIONS, 'special')
            ->setLocale('de_DE')
            ->loadTranslation('it_IT');

        $this->assertSame(
            'un utente (special)',
            $translator->translatePluralInDomain('special','%d user', '%d user', 1, null, 'it_IT')
        );

        $this->assertSame(
            '42 utenti (special)',
            sprintf(
                $translator->translatePluralInDomain('special', '%d user', '%d user', 42, null, 'it_IT'),
                42
            )
        );
    }

    public function testTranslatePluralInDomainWithLocaleAndContext()
    {
        $translator = (new GettextTranslator())
            ->addTranslation('de_DE', static::TRANSLATIONS, 'special')
            ->addTranslation('it_IT', static::TRANSLATIONS, 'special')
            ->setLocale('de_DE')
            ->loadTranslation('it_IT');

        $this->assertSame(
            'una richiesta (special)',
            $translator->translatePluralInDomain('special','%d request', '%d requests', 1, 'context', 'it_IT')
        );

        $this->assertSame(
            '42 richieste (special)',
            sprintf(
                $translator->translatePluralInDomain('special', '%d request', '%d requests', 42, 'context', 'it_IT'),
                42
            )
        );
    }

    public function testTranslatePluralInDomainUsesDefaultDomainAsFallback()
    {
        $translator = (new GettextTranslator())
            ->addTranslation('de_DE', static::TRANSLATIONS)
            ->addTranslation('de_DE', static::TRANSLATIONS, 'special')
            ->setLocale('de_DE');

        $this->assertSame(
            'eine Gruppe',
            $translator->translatePluralInDomain('special','%d group', '%d groups', 1)
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

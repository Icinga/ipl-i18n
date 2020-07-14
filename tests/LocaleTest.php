<?php

namespace ipl\Tests\I18n;

use ipl\I18n\Locale;

class LocaleTest extends \PHPUnit\Framework\TestCase
{
    const AVAILABLE_TRANSLATIONS = ['en_US', 'de_DE', 'de_AT'];

    public function testWhetherGetPreferredFavorsPerfectMatches()
    {
        $this->assertEquals(
            'de_DE',
            (new Locale())->getPreferred('jp,de_DE;q=0.8,de;q=0.6', static::AVAILABLE_TRANSLATIONS),
            'Locale::getPreferredLocale() does not favor perfect matches'
        );
    }

    public function testWhetherGetPreferredReturnsThePreferredSimilarMatchEvenThoughAPerfectMatchWasFound()
    {
        $this->assertEquals(
            'de_DE',
            (new Locale())->getPreferred('de_CH,en_US;q=0.8', static::AVAILABLE_TRANSLATIONS),
            'Locale::getPreferredLocale() does not return the preferred similar match'
        );
    }

    public function testWhetherGetPreferredReturnsAPerfectMatchEvenThoughASimilarMatchWasFound()
    {
        $this->assertEquals(
            'de_AT',
            (new Locale())->getPreferred('de,de_AT;q=0.5', static::AVAILABLE_TRANSLATIONS),
            'Locale::getPreferredLocale() does not return a perfect '
            . 'match if a similar match with higher priority was found'
        );
    }

    public function testWhetherGetPreferredReturnsASimilarMatchIfNoPerfectMatchCouldBeFound()
    {
        $this->assertEquals(
            'de_DE',
            (new Locale())->getPreferred('de,en', static::AVAILABLE_TRANSLATIONS),
            'Locale::getPreferredLocale() does not return the most preferred similar match'
        );
    }

    public function testWhetherGetPreferredReturnsTheDefaultLocaleIfNoMatchCouldBeFound()
    {
        $locale = new Locale();

        $this->assertEquals(
            $locale->getDefaultLocale(),
            $locale->getPreferred('fr_FR,jp_JP', static::AVAILABLE_TRANSLATIONS),
            'Locale::getPreferredLocale() does not return the default locale if no match could be found'
        );
    }
}

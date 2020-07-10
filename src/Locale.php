<?php

namespace ipl\I18n;

class Locale
{
    /** @var string Default locale code */
    protected $defaultLocale = 'en_US';

    /**
     * Get the default locale
     *
     * @return string
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * Set the default locale
     *
     * @param string $defaultLocale
     *
     * @return $this
     */
    public function setDefaultLocale($defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;

        return $this;
    }

    /**
     * Split and return the language code and country code of the given locale or the current locale
     *
     * @param   string  $locale     The locale code to split, or null to split the current locale
     *
     * @return  object              An object with a 'language' and 'country' attribute
     */
    public function splitLocaleCode($locale = null)
    {
        $matches = array();
        $locale = $locale !== null ? $locale : setlocale(LC_ALL, 0);
        if (preg_match('@([a-z]{2})[_-]([a-z]{2})@i', $locale, $matches)) {
            list($languageCode, $countryCode) = array_slice($matches, 1);
        } elseif ($locale === 'C') {
            list($languageCode, $countryCode) = preg_split('@[_-]@', $this->defaultLocale, 2);
        } else {
            $languageCode = $locale;
            $countryCode = null;
        }

        return (object) array('language' => $languageCode, 'country' => $countryCode);
    }

    /**
     * Return the preferred locale based on the given HTTP header and the available translations
     *
     * @param   string  $header     The HTTP "Accept-Language" header
     * @param   array   $available  Available translations
     *
     * @return  string              The browser's preferred locale code
     */
    public function getPreferredLocaleCode($header, array $available)
    {
        $headerValues = explode(',', $header);
        for ($i = 0; $i < count($headerValues); $i++) {
            // In order to accomplish a stable sort we need to take the original
            // index into account as well during element comparison
            $headerValues[$i] = array($headerValues[$i], $i);
        }
        usort( // Sort DESC but keep equal elements ASC
            $headerValues,
            function ($a, $b) {
                $tagA = explode(';', $a[0], 2);
                $tagB = explode(';', $b[0], 2);
                $qValA = (float) (strpos($a[0], ';') > 0 ? substr(array_pop($tagA), 2) : 1);
                $qValB = (float) (strpos($b[0], ';') > 0 ? substr(array_pop($tagB), 2) : 1);
                return $qValA < $qValB ? 1 : ($qValA > $qValB ? -1 : ($a[1] > $b[1] ? 1 : ($a[1] < $b[1] ? -1 : 0)));
            }
        );
        for ($i = 0; $i < count($headerValues); $i++) {
            // We need to reset the array to its original structure once it's sorted
            $headerValues[$i] = $headerValues[$i][0];
        }
        $requestedLocales = array();
        foreach ($headerValues as $headerValue) {
            if (strpos($headerValue, ';') > 0) {
                $parts = explode(';', $headerValue, 2);
                $headerValue = $parts[0];
            }
            $requestedLocales[] = str_replace('-', '_', $headerValue);
        }
        $requestedLocales = array_combine(
            array_map('strtolower', array_values($requestedLocales)),
            array_values($requestedLocales)
        );

        $availableLocales = array_combine(
            array_map('strtolower', array_values($available)),
            array_values($available)
        );

        $similarMatch = null;

        foreach ($requestedLocales as $requestedLocaleLowered => $requestedLocale) {
            $localeObj = $this->splitLocaleCode($requestedLocaleLowered);

            if (isset($availableLocales[$requestedLocaleLowered])
                && (! $similarMatch || $this->splitLocaleCode($similarMatch)->language === $localeObj->language)
            ) {
                // Prefer perfect match only if no similar match has been found yet or the perfect match is more precise
                // than the similar match
                return $availableLocales[$requestedLocaleLowered];
            }

            if (! $similarMatch) {
                foreach ($availableLocales as $availableLocaleLowered => $availableLocale) {
                    if ($this->splitLocaleCode($availableLocaleLowered)->language === $localeObj->language) {
                        $similarMatch = $availableLocaleLowered;
                        break;
                    }
                }
            }
        }

        return $similarMatch ? $availableLocales[$similarMatch] : $this->defaultLocale;
    }
}

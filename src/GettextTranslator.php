<?php

namespace ipl\I18n;

use Icinga\Exception\IcingaException;

/**
 * Helper class to ease internationalization when using gettext
 */
class GettextTranslator
{
    /** @var string Default gettext domain */
    protected $defaultDomain = 'default';

    /** @var string Default locale code */
    protected $defaultLocale = 'en_US';

    /** @var array Available translations as array[$locale][$domain] => $directory */
    protected $translations = [];

    /** @var array Loaded translations as array[$locale][$domain] => $directory */
    protected $loadedTranslations = [];

    /** @var string Primary locale code used for translations */
    protected $locale;

    /**
     * Known gettext domains and directories
     *
     * @var array
     */
    private $knownDomains = array();

    /**
     * Get the default domain
     *
     * @return string
     */
    public function getDefaultDomain()
    {
        return $this->defaultDomain;
    }

    /**
     * Set the default domain
     *
     * @param string $defaultDomain
     *
     * @return $this
     */
    public function setDefaultDomain($defaultDomain)
    {
        $this->defaultDomain = $defaultDomain;

        return $this;
    }

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
     * Get available translations
     *
     * @return array Available translations as array[$locale][$domain] => $directory
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * Add a translation
     *
     * @param string $locale    Locale code
     * @param string $directory Path to translation file
     * @param string $domain    Optional domain of the translation
     *
     * @return $this
     */
    public function addTranslation($locale, $directory, $domain = null)
    {
        $this->translations[$locale][$domain ?: $this->defaultDomain] = $directory;

        return $this;
    }

    /**
     * Get loaded translations
     *
     * @return array Loaded translations as array[$locale][$domain] => $directory
     */
    public function getLoadedTranslations()
    {
        return $this->loadedTranslations;
    }

    /**
     * Load a translation so that gettext is able to locate its message catalogs
     *
     * {@link bindtextdomain()} is called internally for every domain and path that has been added for the given locale
     * with {@link addTranslation()}.
     *
     * @param string $locale Locale code
     *
     * @return $this
     * @throws \Exception If {@link bindtextdomain()} fails for a domain
     */
    public function loadTranslation($locale)
    {
        foreach ($this->translations[$locale] as $domain => $directory) {
            if (
                isset($this->loadedTranslations[$locale][$domain])
                && $this->loadedTranslations[$locale][$domain] === $directory
            ) {
                continue;
            }

            $domainWithLocale = $this->encodeDomainWithLocale($domain, $locale);

            if (bindtextdomain($domainWithLocale, $directory) !== $directory) {
                throw new \Exception(sprintf(
                    "Can't register domain '%s' with path '%s'",
                    $domain,
                    $directory
                ));
            }

            bind_textdomain_codeset($domain, 'UTF-8');

            $this->loadedTranslations[$locale][$domain] = $directory;
        }

        return $this;
    }

    /**
     * Get the primary locale code used for translations
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Setup the primary locale code to use for translations
     *
     * Calls {@link loadTranslation()} internally.
     *
     * @param string $locale Locale code
     *
     * @return $this
     * @throws \Exception If {@link bindtextdomain()} fails for a domain
     */
    public function setLocale($locale)
    {
        putenv('LANGUAGE=C.UTF-8');
        setlocale(LC_MESSAGES, 'C.UTF-8');

        $this->loadTranslation($locale);

        textdomain($this->encodeDomainWithLocale($this->getDefaultDomain(), $locale));

        $this->locale = $locale;

        return $this;
    }

    /**
     * Encode a domain with locale to the representation used for .mo files
     *
     * @param string $domain
     * @param string $locale
     *
     * @return string The encoded domain as domain + "." + locale
     */
    public function encodeDomainWithLocale($domain, $locale)
    {
        return $domain . '.' . $locale;
    }

    /**
     * Encode a message with context to the representation used in .mo files
     *
     * @param string $message
     * @param string $context
     *
     * @return string The encoded message as context + "\x04" + message
     */
    public function encodeMessageWithContext($message, $context)
    {
        // The encoding of a context and a message in a .mo file is
        // context + "\x04" + message (gettext version >= 0.15)
        return "{$context}\x04{$message}";
    }

    public function translate($message, $context = null, $locale = null)
    {
        if ($context !== null) {
            $messageForGettext = $this->encodeMessageWithContext($message, $context);
        } else {
            $messageForGettext = $message;
        }

        if ($locale !== null) {
            $translation = dgettext(
                $this->encodeDomainWithLocale($this->getDefaultDomain(), $locale),
                $messageForGettext
            );
        } else {
            $translation = gettext($messageForGettext);
        }

        if ($translation === $messageForGettext) {
            return $message;
        }

        return $translation;
    }

    public function translateInDomain($domain, $message, $context = null, $locale = null)
    {
        if ($context !== null) {
            $messageForGettext = $this->encodeMessageWithContext($message, $context);
        } else {
            $messageForGettext = $message;
        }

        $translation = dgettext(
            $this->encodeDomainWithLocale($domain, $locale ?: $this->getLocale()),
            $messageForGettext
        );

        if ($translation === $messageForGettext) {
            $translation = dgettext(
                $this->encodeDomainWithLocale($this->getDefaultDomain(), $locale ?: $this->getLocale()),
                $messageForGettext
            );
        }

        if ($translation === $messageForGettext) {
            return $message;
        }

        return $translation;
    }

    public function translatePlural($singular, $plural, $number, $context = null, $locale = null)
    {
        if ($context !== null) {
            $singularForGettext = $this->encodeMessageWithContext($singular, $context);
        } else {
            $singularForGettext = $singular;
        }

        if ($locale !== null) {
            $translation = dngettext(
                $this->encodeDomainWithLocale($this->getDefaultDomain(), $locale),
                $singularForGettext,
                $plural,
                $number
            );
        } else {
            $translation = ngettext(
                $singularForGettext,
                $plural,
                $number
            );
        }

        if ($translation === $singularForGettext) {
            return $number === 1 ? $singular : $plural;
        }

        return $translation;
    }

    public function translatePluralInDomain($domain, $singular, $plural, $number, $context = null, $locale = null)
    {
        if ($context !== null) {
            $singularForGettext = $this->encodeMessageWithContext($singular, $context);
        } else {
            $singularForGettext = $singular;
        }

        $translation = dngettext(
            $this->encodeDomainWithLocale($domain, $locale ?: $this->getLocale()),
            $singularForGettext,
            $plural,
            $number
        );

        $isSingular = $number === 1;

        if ($translation === ($isSingular ? $singularForGettext : $plural)) {
            $translation = dngettext(
                $this->encodeDomainWithLocale($this->getDefaultDomain(), $locale ?: $this->getLocale()),
                $singularForGettext,
                $plural,
                $number
            );
        }

        if ($translation === $singularForGettext) {
            return $isSingular ? $singular : $plural;
        }

        return $translation;
    }

    /**
     * Emulated pngettext()
     *
     * @link http://php.net/manual/de/book.gettext.php#89975
     *
     * @param $textSingular
     * @param $textPlural
     * @param $number
     * @param $domain
     * @param $context
     *
     * @return string
     */
    public function pngettext($textSingular, $textPlural, $number, $domain, $context)
    {
        $contextString = "{$context}\004{$textSingular}";

        $translation = dcngettext(
            $domain,
            $contextString,
            $textPlural,
            $number,
            defined('LC_MESSAGES') ? LC_MESSAGES : LC_ALL
        );

        if ($translation == $contextString || $translation == $textPlural) {
            return ($number == 1 ? $textSingular : $textPlural);
        } else {
            return $translation;
        }
    }

    /**
     * Register a new gettext domain
     *
     * @param   string  $name       The name of the domain to register
     * @param   string  $directory  The directory where message catalogs can be found
     *
     * @throws  IcingaException     In case the domain was not successfully registered
     */
    public function registerDomain($name, $directory)
    {
        if (bindtextdomain($name, $directory) === false) {
            throw new IcingaException(
                'Cannot register domain \'%s\' with path \'%s\'',
                $name,
                $directory
            );
        }
        bind_textdomain_codeset($name, 'UTF-8');
        $this->knownDomains[$name] = $directory;
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
     * Return a list of all locale codes currently available in the known domains
     *
     * @return  array
     */
    public function getAvailableLocaleCodes()
    {
        $codes = array($this->defaultLocale);
        foreach (array_values($this->knownDomains) as $directory) {
            $dh = opendir($directory);
            while (false !== ($name = readdir($dh))) {
                if (substr($name, 0, 1) !== '.'
                    && false === in_array($name, $codes)
                    && is_dir($directory . DIRECTORY_SEPARATOR . $name)
                ) {
                    $codes[] = $name;
                }
            }
        }
        sort($codes);

        return $codes;
    }

    /**
     * Return the preferred locale based on the given HTTP header and the available translations
     *
     * @param   string  $header     The HTTP "Accept-Language" header
     *
     * @return  string              The browser's preferred locale code
     */
    public function getPreferredLocaleCode($header)
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

        $availableLocales = $this->getAvailableLocaleCodes();
        $availableLocales = array_combine(
            array_map('strtolower', array_values($availableLocales)),
            array_values($availableLocales)
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

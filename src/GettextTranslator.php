<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

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

            $domainWithLocale = $domain . '.' . $locale;

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
     * Translate a string
     *
     * Falls back to the default domain in case the string cannot be translated using the given domain
     *
     * @param   string      $text       The string to translate
     * @param   string      $domain     The primary domain to use
     * @param   string|null $context    Optional parameter for context based translation
     *
     * @return  string                  The translated string
     */
    public function translate($text, $domain, $context = null)
    {
        if ($context !== null) {
            $res = $this->pgettext($text, $domain, $context);
            if ($res === $text && $domain !== $this->defaultDomain) {
                $res = $this->pgettext($text, $this->defaultDomain, $context);
            }
            return $res;
        }

        $res = dgettext($domain, $text);
        if ($res === $text && $domain !== $this->defaultDomain) {
            return dgettext($this->defaultDomain, $text);
        }
        return $res;
    }

    /**
     * Translate a plural string
     *
     * Falls back to the default domain in case the string cannot be translated using the given domain
     *
     * @param   string      $textSingular   The string in singular form to translate
     * @param   string      $textPlural     The string in plural form to translate
     * @param   integer     $number         The amount to determine from whether to return singular or plural
     * @param   string      $domain         The primary domain to use
     * @param   string|null $context        Optional parameter for context based translation
     *
     * @return string                       The translated string
     */
    public function translatePlural($textSingular, $textPlural, $number, $domain, $context = null)
    {
        if ($context !== null) {
            $res = $this->pngettext($textSingular, $textPlural, $number, $domain, $context);
            if (($res === $textSingular || $res === $textPlural) && $domain !== $this->defaultDomain) {
                $res = $this->pngettext($textSingular, $textPlural, $number, $this->defaultDomain, $context);
            }
            return $res;
        }

        $res = dngettext($domain, $textSingular, $textPlural, $number);
        if (($res === $textSingular || $res === $textPlural) && $domain !== $this->defaultDomain) {
            $res = dngettext($this->defaultDomain, $textSingular, $textPlural, $number);
        }
        return $res;
    }

    /**
     * Emulated pgettext()
     *
     * @link http://php.net/manual/de/book.gettext.php#89975
     *
     * @param $text
     * @param $domain
     * @param $context
     *
     * @return string
     */
    public function pgettext($text, $domain, $context)
    {
        $contextString = "{$context}\004{$text}";

        $translation = dcgettext(
            $domain,
            $contextString,
            defined('LC_MESSAGES') ? LC_MESSAGES : LC_ALL
        );

        if ($translation == $contextString) {
            return $text;
        } else {
            return $translation;
        }
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
     * Set the locale to use
     *
     * @param   string  $localeName     The name of the locale to use
     *
     * @throws  IcingaException         In case the locale's name is invalid
     */
    public function setupLocale($localeName)
    {
        if (setlocale(LC_ALL, $localeName . '.UTF-8') === false && setlocale(LC_ALL, $localeName) === false) {
            setlocale(LC_ALL, 'C'); // C == "use whatever is hardcoded"
            if ($localeName !== $this->defaultLocale) {
                throw new IcingaException(
                    'Cannot set locale \'%s\' for category \'LC_ALL\'',
                    $localeName
                );
            }
        } else {
            $locale = setlocale(LC_ALL, 0);
            putenv('LC_ALL=' . $locale); // Failsafe, Win and Unix
            putenv('LANG=' . $locale); // Windows fix, untested

            // https://www.gnu.org/software/gettext/manual/html_node/The-LANGUAGE-variable.html
            putenv('LANGUAGE=' . $localeName . ':' . getenv('LANGUAGE'));
        }
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

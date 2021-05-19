<?php

namespace ipl\I18n;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Translator using PHP's native [gettext](https://www.php.net/gettext) extension
 *
 * Since gettext is controlled via {@link setlocale()}, there are usually two drawbacks:
 * * Languages have to be installed and
 * * {@link setlocale()} is not threadsafe
 *
 * The `GettextTranslator` bypasses this with a little trick: The locale is always set to `C.UTF-8` via calls to
 * `setlocale(LC_MESSAGES, 'C.UTF-8');` and `putenv('LANGUAGE=C.UTF-8');`.
 * This way there are no thread safety issues and no languages need to be installed.
 *
 * For the locale to work anyway, it must be part of the domain and the .mo files must be named accordingly.
 * The commonly used directory structure changes as a result of this and **all** message catalogs must be beneath
 * `C/LC_MESSAGES`:
 *
 * ```
 * /path/to/locales/
 * └── C/
 *     └── LC_MESSAGES
 *         ├── $domain.$locale.mo (e.g. default.de_DE.mo)
 *         ├── $domain.$locale.mo (e.g. default.it_IT.mo)
 *         ├── $domain.$locale.mo (e.g. special.de_DE.mo)
 *         ├── $domain.$locale.mo (e.g. special.it_IT.mo)
 *         └── ...
 * ```
 *
 * Note that the encoding of domain with locale is abstracted away when using the translation functions and only needs
 * to be considered when providing message catalogs.
 *
 * # Example Usage
 *
 * ```php
 * $translator = (new GettextTranslator())
 *     ->addTranslationDirectory('/path/to/locales')
 *     ->addTranslationDirectory('/path/to/locales-of-domain', 'special') // Could also be the same directory as above
 *     ->setLocale('de_DE');
 *
 * $translator->translate('user');
 *
 * printf(
 *     $translator->translatePlural('%d user', '%d user', 42),
 *     42
 * );
 *
 * $translator->translateInDomain('special-domain', 'request');
 *
 * printf(
 *     $translator->translatePluralInDomain('special-domain', '%d request', '%d requests', 42),
 *     42
 * );
 *
 * // All translation functions also accept a context as last parameter
 * $translator->translate('group', 'a-context');
 * ```
 *
 */
class GettextTranslator
{
    /** @var string Default gettext domain */
    protected $defaultDomain = 'default';

    /** @var string Default locale code */
    protected $defaultLocale = 'en_US';

    /** @var array Known translation directories as array[$domain] => $directory */
    protected $translationDirectories = [];

    /** @var array Loaded translations as array[$locale][$domain] => $directory */
    protected $loadedTranslations = [];

    /** @var string Primary locale code used for translations */
    protected $locale;

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
     * @return array Available translations as array[$domain] => $directory
     */
    public function getTranslationDirectories()
    {
        return $this->translationDirectories;
    }

    /**
     * Add a translation directory
     *
     * @param string $directory Path to translation files
     * @param string $domain    Optional domain of the translation
     *
     * @return $this
     */
    public function addTranslationDirectory($directory, $domain = null)
    {
        $this->translationDirectories[$domain ?: $this->defaultDomain] = $directory;

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
        foreach ($this->translationDirectories as $domain => $directory) {
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

    public function translate($message, $context = null)
    {
        if ($context !== null) {
            $messageForGettext = $this->encodeMessageWithContext($message, $context);
        } else {
            $messageForGettext = $message;
        }

        $translation = gettext($messageForGettext);

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
            $this->encodeDomainWithLocale($domain, $this->getLocale()),
            $messageForGettext
        );

        if ($translation === $messageForGettext) {
            $translation = dgettext(
                $this->encodeDomainWithLocale($this->getDefaultDomain(), $this->getLocale()),
                $messageForGettext
            );
        }

        if ($translation === $messageForGettext) {
            return $message;
        }

        return $translation;
    }

    public function translatePlural($singular, $plural, $number, $context = null)
    {
        if ($context !== null) {
            $singularForGettext = $this->encodeMessageWithContext($singular, $context);
        } else {
            $singularForGettext = $singular;
        }


        $translation = ngettext(
            $singularForGettext,
            $plural,
            $number
        );

        if ($translation === $singularForGettext) {
            return $number === 1 ? $singular : $plural;
        }

        return $translation;
    }

    public function translatePluralInDomain($domain, $singular, $plural, $number, $context = null)
    {
        if ($context !== null) {
            $singularForGettext = $this->encodeMessageWithContext($singular, $context);
        } else {
            $singularForGettext = $singular;
        }

        $translation = dngettext(
            $this->encodeDomainWithLocale($domain, $this->getLocale()),
            $singularForGettext,
            $plural,
            $number
        );

        $isSingular = $number === 1;

        if ($translation === ($isSingular ? $singularForGettext : $plural)) {
            $translation = dngettext(
                $this->encodeDomainWithLocale($this->getDefaultDomain(), $this->getLocale()),
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
     * List available locales by traversing the translation directories from {@link addTranslationDirectory()}
     *
     * @return string[] Array of available locale codes
     */
    public function listLocales()
    {
        $locales = [];

        foreach (array_unique($this->getTranslationDirectories()) as $directory) {
            $fs = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                $directory,
                FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS
            ));

            foreach ($fs as $file) {
                if (
                    ! $file->isFile()
                    || $file->getExtension() !== 'mo'
                ) {
                    continue;
                }

                list($actualLocale) = array_slice(
                    explode(DIRECTORY_SEPARATOR, $file->getPath()),
                    -2,
                    1
                );
                if ($actualLocale !== 'C') {
                    continue;
                }

                $locale = explode('.', $file->getBasename('.mo'));

                $locales[] = array_pop($locale);
            }
        }

        $locales = array_filter(array_unique($locales));

        sort($locales);

        return $locales;
    }
}

<?php

namespace ipl\I18n;

trait Translation
{
    /**
     * Translate a message
     *
     * @param string $message
     * @param string $context Message context
     *
     * @return string Translated message or original message if no translation is found
     */
    public function translate($message, $context = null)
    {
        return StaticTranslator::$instance->translate($message, $context);
    }

    /**
     * Translate a message in the given domain
     *
     * If no translation is found in the specified domain, the translation is also searched for in the default domain.
     *
     * @param string $domain
     * @param string $message
     * @param string $context Message context
     *
     * @return string Translated message or original message if no translation is found
     */
    public function translateInDomain($domain, $message, $context = null)
    {
        return StaticTranslator::$instance->translateInDomain($domain, $message, $context);
    }

    /**
     * Translate a plural message
     *
     * The returned message is based on the given number to decide between the singular and plural forms.
     * That is also the case if no translation is found.
     *
     * @param string $singular Singular message
     * @param string $plural   Plural message
     * @param int    $number   Number to decide between the returned singular and plural forms
     * @param string $context  Message context
     *
     * @return string Translated message or original message if no translation is found
     */
    public function translatePlural($singular, $plural, $number, $context = null)
    {
        return StaticTranslator::$instance->translatePlural($singular, $plural, $number, $context);
    }

    /**
     * Translate a plural message in the given domain
     *
     * If no translation is found in the specified domain, the translation is also searched for in the default domain.
     *
     * The returned message is based on the given number to decide between the singular and plural forms.
     * That is also the case if no translation is found.
     *
     * @param string $domain
     * @param string $singular Singular message
     * @param string $plural   Plural message
     * @param int    $number   Number to decide between the returned singular and plural forms
     * @param string $context  Message context
     *
     * @return string Translated message or original message if no translation is found
     */
    public function translatePluralInDomain($domain, $singular, $plural, $number, $context = null)
    {
        return StaticTranslator::$instance->translatePluralInDomain($domain, $singular, $plural, $number, $context);
    }
}

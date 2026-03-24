# Icinga PHP Library - Internationalization (i18n)

`ipl/i18n` provides a translation suite built on PHP's native
[gettext](https://www.php.net/gettext) extension. It handles locale
negotiation from HTTP `Accept-Language` headers, wires a static
translator instance for use anywhere in an application, and exposes
convenient global helper functions and a trait for use in classes.

## Installation

The recommended way to install this library is via
[Composer](https://getcomposer.org):

```shell
composer require ipl/i18n
```

`ipl/i18n` requires PHP 7.2 or later with the `intl` and `gettext`
extensions.

Translation catalogs are expected in gettext's standard directory
layout:

```text
/path/to/locales/<locale>/LC_MESSAGES/<domain>.mo
```

`.mo` files are compiled binary catalogs produced from human-editable `.po`
files via `msgfmt`. If you do not specify a domain, `ipl/i18n` uses the
`default` domain.

## Usage

### Setting Up the Translator

Bootstrap the translator once at application startup and assign it to
`StaticTranslator` so that the global helper functions and the `Translation`
trait resolve it automatically from anywhere in your application.
`addTranslationDirectory()` accepts an optional second argument to register a
named domain — see [Working With Multiple Domains](#working-with-multiple-domains-and-message-context)
for details:

```php
use ipl\I18n\GettextTranslator;
use ipl\I18n\StaticTranslator;

$translator = (new GettextTranslator())
    ->addTranslationDirectory('/path/to/locales')
    ->setLocale('de_DE');

StaticTranslator::$instance = $translator;
```

### Global Helper Functions

Once `StaticTranslator::$instance` is set, import `t()` and `tp()` via
`use function` and call them anywhere in your application:

```php
use function ipl\I18n\t;
use function ipl\I18n\tp;

echo t('Save changes');

// $count selects the plural form, printf substitutes %d.
printf(tp('%d item', '%d items', $count), $count);
```

Both functions forward to the static translator instance, so they
always reflect the currently active locale.

### Translation Trait

Add the `Translation` trait to any class that needs to translate
messages. Set `$translationDomain` to scope translations to a specific
gettext domain, or leave it `null` (the default) to use the default domain:

```php
use ipl\I18n\Translation;

class MyModule
{
    use Translation;

    protected ?string $translationDomain = 'mydomain';

    public function getLabel(): string
    {
        return $this->translate('Dashboard');
    }

    public function getItemLabel(int $count): string
    {
        // $count selects the plural form, sprintf substitutes %d.
        return sprintf(
            $this->translatePlural('%d item', '%d items', $count),
            $count
        );
    }
}
```

### Working With Multiple Domains and Message Context

Register additional translation directories under named domains and use
the domain- and context-aware methods to resolve messages precisely.
Context disambiguates source strings that share the same English word
but require different translations — for example, `"request"` as an
HTTP request versus a change request:

```php
use ipl\I18n\GettextTranslator;

$translator = (new GettextTranslator())
    ->addTranslationDirectory('/path/to/default/locales')
    ->addTranslationDirectory('/path/to/configuration/locales', 'configuration')
    ->setLocale('de_DE');

// Same source string, different translations depending on context:
// 'http'   → an HTTP request
// 'change' → a change request
echo $translator->translate('request', 'http');
echo $translator->translate('request', 'change');

// Resolve messages from the `configuration` domain.
echo $translator->translateInDomain('configuration', 'configuration item');
printf(
    $translator->translatePluralInDomain(
        'configuration', '%d job', '%d jobs', 2, 'deployment queue'
    ),
    2
);
```

### Detecting the User's Language

Browsers send an `Accept-Language` header with each request listing the
user's preferred languages in priority order (e.g.,
`de-DE,de;q=0.9,en-US;q=0.8`). Use `Locale::getPreferred()` to match
that list against the locales your application actually has available
and activate the best fit:

```php
use ipl\I18n\Locale;

$availableLocales = $translator->listLocales();
$preferredLocale = (new Locale())->getPreferred(
    $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
    $availableLocales
);

$translator->setLocale($preferredLocale);
```

If no exact or language-level match is available, locale selection falls
back to `en_US` by default. Use `setDefaultLocale()` on the `Locale`
instance to change the fallback.

### Testing With NoopTranslator

In tests you typically do not want to depend on compiled `.mo` catalog
files being present on disk. `NoopTranslator` satisfies the translator
interface by returning every message unchanged, so your test suite runs
without any gettext setup while still exercising all code paths that
call `t()`, `tp()`, or the `Translation` trait:

```php
use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;

StaticTranslator::$instance = new NoopTranslator();
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of notable changes.

## License

`ipl/i18n` is licensed under the terms of the [MIT License](LICENSE.md).

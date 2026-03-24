# Changelog

All notable changes to this library are documented in this file.

## [Unreleased]

## [1.0.0] - 2026-03-24

- **Breaking** Raise minimum PHP version to 8.2 (#13)
- Add strict type declarations (#13, #26)

## [0.2.2] - 2024-04-08

- Fix `GettextTranslator::setLocale()` not setting the intl
  extension's default locale (#22)
- Add PHP 8.3 support

## [0.2.1] - 2023-09-21

- Fix `Translation::translatePlural()` passing null as count to
  `ngettext()` on PHP 8.1 (#17)
- Add PHP 8.2 support

## [0.2.0] - 2022-06-15

- **Breaking** Drop support for PHP 5.6, 7.0, and 7.1 (#12)
- Add PHP 8.1 support (#12)

## [0.1.1] - 2022-03-23

- Declare `ext-intl` as required in `composer.json` (#11)

## [0.1.0] - 2021-06-15

- Initial release providing `GettextTranslator`, `NoopTranslator`,
  `StaticTranslator`, `Locale`, and the `Translation` trait
- Add global helper functions `t()` and `tp()` wrapping
  `StaticTranslator` (#1)

[Unreleased]: https://github.com/Icinga/ipl-i18n/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/Icinga/ipl-i18n/releases/tag/v1.0.0
[0.2.2]: https://github.com/Icinga/ipl-i18n/releases/tag/v0.2.2
[0.2.1]: https://github.com/Icinga/ipl-i18n/releases/tag/v0.2.1
[0.2.0]: https://github.com/Icinga/ipl-i18n/releases/tag/v0.2.0
[0.1.1]: https://github.com/Icinga/ipl-i18n/releases/tag/v0.1.1
[0.1.0]: https://github.com/Icinga/ipl-i18n/releases/tag/v0.1.0

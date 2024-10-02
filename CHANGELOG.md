# Changelog

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]


## [1.0.4] (2024-10-02)

 * Correctly handle XML namespaces. [#39]
 * Do not throw exceptions for invalid resize filters. [#42]

## [1.0.3] (2021-08-10)

 * Fix bug with empty SVG files. [#33]

## [1.0.2] (2020-10-31)

 * Fix deprecation warning in PHP 8.0. [#28]

## [1.0.1] (2020-07-20)

 * Fix small pixel values for aspect ratio size. [#24]
 * Fix `crop()`, `resize()` and `thumbnail()` for aspect ratio images. [#25]

## [1.0.0] (2020-06-13)

## [1.0.0-RC1] (2020-05-27)

 * Increase required PHP version to 7.2.
 * Increase required imagine/imagine version to 1.0.
 * Remove UndefinedBox and RelativeBox.
 * Support PHP version 8.0. [#19]
 * Improve aspect ratio computation. [#21]

## [0.2.3] (2020-05-21)

 * Don’t throw exceptions for LIBXML warnings. [#13]
 * Fix bug with viewBox computation.
 * Use NotSupportedException if available. [#15]
 * Deprecate UndefinedBox and RelativeBox, replace them with SvgBox. [#14]

## [0.2.2] (2019-01-27)

 * Compatibility with Imagine 1.0.
 * Fix bug with LC_NUMERIC locale.
 * Add contents for the CHANGELOG.md file.

## [0.2.1] (2018-03-02)

 * Implement effects. [#9], [#10]

## [0.2.0] (2017-12-27)

 * Drop support for PHP 5.5.

## [0.1.5] (2017-09-14)

 * Always set a `viewBox` attribute. [#8]
 * Skip cropping if the size doesn’t change. [#8]

## [0.1.4] (2017-05-20)

 * Compatibility with Imagine 0.7.
 * Implement the `ImageInterface::strip()` method. [#7]
 * Implement the `ImageInterface::usePalette()` method. [#7]

## [0.1.3] (2017-02-14)

 * Rename the Test namespace to Tests.
 * Add an .editorconfig file.

## [0.1.2] (2016-11-22)

 * Fix “A non well formed numeric value encountered” error. [#2]

## [0.1.1] (2016-09-28)

 * Better validation and security at loading XML files.
 * Better test coverage.

## [0.1.0] (2016-07-29)

 * Initial release

[Unreleased]: https://github.com/contao/imagine-svg/compare/1.0.4...HEAD
[1.0.4]: https://github.com/contao/imagine-svg/compare/1.0.3...1.0.4
[1.0.3]: https://github.com/contao/imagine-svg/compare/1.0.2...1.0.3
[1.0.2]: https://github.com/contao/imagine-svg/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/contao/imagine-svg/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/contao/imagine-svg/compare/1.0.0-RC1...1.0.0
[1.0.0-RC1]: https://github.com/contao/imagine-svg/compare/0.2.3...1.0.0-RC1
[0.2.3]: https://github.com/contao/imagine-svg/compare/0.2.2...0.2.3
[0.2.2]: https://github.com/contao/imagine-svg/compare/0.2.1...0.2.2
[0.2.1]: https://github.com/contao/imagine-svg/compare/0.2.0...0.2.1
[0.2.0]: https://github.com/contao/imagine-svg/compare/0.1.5...0.2.0
[0.1.5]: https://github.com/contao/imagine-svg/compare/0.1.4...0.1.5
[0.1.4]: https://github.com/contao/imagine-svg/compare/0.1.3...0.1.4
[0.1.3]: https://github.com/contao/imagine-svg/compare/0.1.2...0.1.3
[0.1.2]: https://github.com/contao/imagine-svg/compare/0.1.1...0.1.2
[0.1.1]: https://github.com/contao/imagine-svg/compare/0.1.0...0.1.1
[0.1.0]: https://github.com/contao/imagine-svg/commits/0.1.0

[#42]: https://github.com/contao/imagine-svg/issues/42
[#39]: https://github.com/contao/imagine-svg/issues/39
[#33]: https://github.com/contao/imagine-svg/issues/33
[#28]: https://github.com/contao/imagine-svg/issues/28
[#25]: https://github.com/contao/imagine-svg/issues/25
[#24]: https://github.com/contao/imagine-svg/issues/24
[#21]: https://github.com/contao/imagine-svg/issues/21
[#19]: https://github.com/contao/imagine-svg/issues/19
[#15]: https://github.com/contao/imagine-svg/issues/15
[#14]: https://github.com/contao/imagine-svg/issues/14
[#13]: https://github.com/contao/imagine-svg/issues/13
[#10]: https://github.com/contao/imagine-svg/issues/10
[#9]: https://github.com/contao/imagine-svg/issues/9
[#8]: https://github.com/contao/imagine-svg/issues/8
[#7]: https://github.com/contao/imagine-svg/issues/7
[#2]: https://github.com/contao/imagine-svg/issues/2

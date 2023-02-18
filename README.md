Contao Imagine SVG library
==========================

[![](https://img.shields.io/github/actions/workflow/status/contao/imagine-svg/ci.yml?branch=1.x&style=flat-square)](https://github.com/contao/imagine-svg/actions?query=branch%3A1.x)
[![](https://img.shields.io/codecov/c/github/contao/imagine-svg/1.x.svg?style=flat-square)](https://codecov.io/gh/contao/imagine-svg)
[![](https://img.shields.io/packagist/v/contao/imagine-svg.svg?style=flat-square)](https://packagist.org/packages/contao/imagine-svg)
[![](https://img.shields.io/packagist/dt/contao/imagine-svg.svg?style=flat-square)](https://packagist.org/packages/contao/imagine-svg)

This project implements the interfaces of [Imagine][1] and allows you to make
simple modifications to SVG images. It is used in [Contao][2] to handle
on-the-fly resizing of SVG images.

Installation
------------

```sh
php composer.phar require contao/imagine-svg
```

Usage
-----

```php
use Contao\ImagineSvg\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Point;

$imagine = new Imagine();

$imagine
    ->open('/path/to/image.svg')
    ->crop(new Point(50, 50), new Box(100, 100))
    ->resize(new Box(40, 40))
    ->save('/path/to/thumbnail.svg')
;

$image = $imagine->open('/path/to/image.svg');

$image->effects()
    ->gamma(1.5)
    ->negative()
    ->grayscale()
    ->colorize($color)
    ->sharpen()
    ->blur(2)
;

$image->save('/path/to/image.svg');
```

Because of the nature of SVG images, the `getSize()` method differs a little bit
from other implementations. You can check the return value like in this example:

```php
use Contao\ImagineSvg\Imagine;
use Contao\ImagineSvg\SvgBox;

$imagine = new Imagine();
$size = $imagine->open('/path/to/image.svg')->getSize();

if (SvgBox::TYPE_NONE === $size->getType()) {
    // The image has no defined size
} elseif (SvgBox::TYPE_ASPECT_RATIO === $size->getType()) {
    // The image has a relative size, $size->getWidth() and $size->getHeight()
    // should be treated as an aspect ratio
} else {
    // The image has a defined size like a regular image
    // $size->getType() would return SvgBox::TYPE_ABSOLUTE in this case
}
```

[1]: https://github.com/avalanche123/Imagine
[2]: https://contao.org

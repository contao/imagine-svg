Contao Imagine SVG library
==========================

[![](https://img.shields.io/travis/contao/imagine-svg/master.svg?style=flat-square)](https://travis-ci.org/contao/imagine-svg/)
[![](https://img.shields.io/scrutinizer/g/contao/imagine-svg/master.svg?style=flat-square)](https://scrutinizer-ci.com/g/contao/imagine-svg/)
[![](https://img.shields.io/coveralls/contao/imagine-svg/master.svg?style=flat-square)](https://coveralls.io/github/contao/imagine-svg)
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
```

Because of the nature of SVG images, the `getSize()` method differs a little bit
from other implementations. You can check the return value like in this example:

```php
use Contao\ImagineSvg\Imagine;
use Contao\ImagineSvg\RelativeBoxInterface;
use Contao\ImagineSvg\UndefinedBoxInterface;

$imagine = new Imagine();
$size = $imagine->open('/path/to/image.svg')->getSize();

if ($size instanceof UndefinedBoxInterface) {
    // The image has no defined size
} elseif ($size instanceof RelativeBoxInterface) {
    // The image has a relative size, $size->getWidth() and $size->getHeight()
    // should be treated as an aspect ratio
} else {
    // The image has a defined size like a regular image
}
```

[1]: https://github.com/avalanche123/Imagine
[2]: https://contao.org

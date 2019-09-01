<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ImagineSvg\Tests;

use Contao\ImagineSvg\Effects;
use Contao\ImagineSvg\Imagine;
use Contao\ImagineSvg\UndefinedBox;
use Imagine\Exception\InvalidArgumentException;
use Imagine\Exception\NotSupportedException;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\Palette\Color\RGB as ColorRgb;
use Imagine\Image\Palette\RGB as PaletteRgb;
use Imagine\Utils\Matrix;
use PHPUnit\Framework\TestCase;

class EffectsTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\ImagineSvg\Effects', new Effects(new \DOMDocument()));
    }

    public function testGamma()
    {
        $dom = (new Imagine())->create(new UndefinedBox())->getDomDocument();
        $effects = new Effects($dom);

        $this->assertSame($effects, $effects->gamma(1));
        $this->assertTrue($dom->documentElement->firstChild->hasAttribute('filter'));

        /** @var \DOMElement $filter */
        $filter = $dom->getElementsByTagName('filter')[0];
        $filterId = explode(')', explode('#', $dom->documentElement->firstChild->getAttribute('filter'))[1])[0];

        $this->assertSame($filterId, $filter->getAttribute('id'));
        $this->assertSame('feComponentTransfer', $filter->firstChild->nodeName);
        $this->assertSame('gamma', $filter->firstChild->firstChild->getAttribute('type'));
        $this->assertSame('1', $filter->firstChild->firstChild->getAttribute('exponent'));
        $this->assertSame(3, $filter->firstChild->childNodes->length);
        $this->assertSame(1, $filter->firstChild->getElementsByTagName('feFuncR')->length);
        $this->assertSame(1, $filter->firstChild->getElementsByTagName('feFuncG')->length);
        $this->assertSame(1, $filter->firstChild->getElementsByTagName('feFuncB')->length);

        $effects->gamma(1.6);

        $this->assertSame('url(#'.$filterId.')', $dom->documentElement->firstChild->getAttribute('filter'));
        $this->assertSame(2, $filter->childNodes->length);
        $this->assertSame('feComponentTransfer', $filter->lastChild->nodeName);
        $this->assertSame('gamma', $filter->lastChild->firstChild->getAttribute('type'));
        $this->assertSame('0.625', $filter->lastChild->firstChild->getAttribute('exponent'));

        $this->expectException(InvalidArgumentException::class);

        $effects->gamma(0);
    }

    public function testGammaWithLocale()
    {
        $this->executeTestWithLocale('testGamma');
    }

    public function testNegative()
    {
        $dom = (new Imagine())->create(new UndefinedBox())->getDomDocument();
        $effects = new Effects($dom);

        $this->assertSame($effects, $effects->negative());
        $this->assertTrue($dom->documentElement->firstChild->hasAttribute('filter'));

        /** @var \DOMElement $filter */
        $filter = $dom->getElementsByTagName('filter')[0];
        $filterId = explode(')', explode('#', $dom->documentElement->firstChild->getAttribute('filter'))[1])[0];

        $this->assertSame($filterId, $filter->getAttribute('id'));
        $this->assertSame('feColorMatrix', $filter->firstChild->nodeName);
        $this->assertSame('matrix', $filter->firstChild->getAttribute('type'));

        $this->assertSame([
            '-1', '0', '0', '0', '1',
            '0', '-1', '0', '0', '1',
            '0', '0', '-1', '0', '1',
            '0', '0',  '0', '1', '0',
        ], preg_split('/\s+/', $filter->firstChild->getAttribute('values')));
    }

    public function testGrayscale()
    {
        $dom = (new Imagine())->create(new UndefinedBox())->getDomDocument();
        $effects = new Effects($dom);

        $this->assertSame($effects, $effects->grayscale());
        $this->assertTrue($dom->documentElement->firstChild->hasAttribute('filter'));

        /** @var \DOMElement $filter */
        $filter = $dom->getElementsByTagName('filter')[0];
        $filterId = explode(')', explode('#', $dom->documentElement->firstChild->getAttribute('filter'))[1])[0];

        $this->assertSame($filterId, $filter->getAttribute('id'));
        $this->assertSame('feColorMatrix', $filter->firstChild->nodeName);
        $this->assertSame('saturate', $filter->firstChild->getAttribute('type'));
        $this->assertSame('0', $filter->firstChild->getAttribute('values'));
    }

    public function testColorize()
    {
        $dom = (new Imagine())->create(new UndefinedBox())->getDomDocument();
        $effects = new Effects($dom);
        $color = new ColorRgb(new PaletteRgb(), [255, 127.5, 63.75], 100);

        $this->assertSame($effects, $effects->colorize($color));
        $this->assertTrue($dom->documentElement->firstChild->hasAttribute('filter'));

        /** @var \DOMElement $filter */
        $filter = $dom->getElementsByTagName('filter')[0];
        $filterId = explode(')', explode('#', $dom->documentElement->firstChild->getAttribute('filter'))[1])[0];

        $this->assertSame($filterId, $filter->getAttribute('id'));
        $this->assertSame('feColorMatrix', $filter->firstChild->nodeName);
        $this->assertSame('matrix', $filter->firstChild->getAttribute('type'));
        $this->assertSame('1 0 0 0 1 0 1 0 0 0.5 0 0 1 0 0.25 0 0 0 1 0', $filter->firstChild->getAttribute('values'));

        $color = new ColorRgb(new PaletteRgb(), [63.75, 255, 127.5], 100);
        $effects->colorize($color);

        $this->assertSame('url(#'.$filterId.')', $dom->documentElement->firstChild->getAttribute('filter'));
        $this->assertSame(2, $filter->childNodes->length);
        $this->assertSame('feColorMatrix', $filter->lastChild->nodeName);
        $this->assertSame('matrix', $filter->lastChild->getAttribute('type'));
        $this->assertSame('1 0 0 0 0.25 0 1 0 0 1 0 0 1 0 0.5 0 0 0 1 0', $filter->lastChild->getAttribute('values'));

        $this->expectException(NotSupportedException::class);

        $effects->colorize($this->createMock(ColorInterface::class));
    }

    public function testSharpen()
    {
        $dom = (new Imagine())->create(new UndefinedBox())->getDomDocument();
        $effects = new Effects($dom);

        $this->assertSame($effects, $effects->sharpen());
        $this->assertTrue($dom->documentElement->firstChild->hasAttribute('filter'));

        /** @var \DOMElement $filter */
        $filter = $dom->getElementsByTagName('filter')[0];
        $filterId = explode(')', explode('#', $dom->documentElement->firstChild->getAttribute('filter'))[1])[0];

        $this->assertSame($filterId, $filter->getAttribute('id'));
        $this->assertSame('feConvolveMatrix', $filter->firstChild->nodeName);

        $this->assertSame('1', $filter->firstChild->getAttribute('kernelUnitLength'));

        $this->assertSame([
            '-0.02', '-0.12', '-0.02',
            '-0.12',  '1.56', '-0.12',
            '-0.02', '-0.12', '-0.02',
        ], preg_split('/\s+/', $filter->firstChild->getAttribute('kernelMatrix')));
    }

    public function testBlur()
    {
        $dom = (new Imagine())->create(new UndefinedBox())->getDomDocument();
        $effects = new Effects($dom);

        $this->assertSame($effects, $effects->blur(' 1.50'));
        $this->assertTrue($dom->documentElement->firstChild->hasAttribute('filter'));

        /** @var \DOMElement $filter */
        $filter = $dom->getElementsByTagName('filter')[0];
        $filterId = explode(')', explode('#', $dom->documentElement->firstChild->getAttribute('filter'))[1])[0];

        $this->assertSame($filterId, $filter->getAttribute('id'));
        $this->assertSame('feGaussianBlur', $filter->firstChild->nodeName);
        $this->assertSame('1.5', $filter->firstChild->getAttribute('stdDeviation'));

        $effects->blur();

        $this->assertSame('url(#'.$filterId.')', $dom->documentElement->firstChild->getAttribute('filter'));
        $this->assertSame(2, $filter->childNodes->length);
        $this->assertSame('feGaussianBlur', $filter->lastChild->nodeName);
        $this->assertSame('1', $filter->lastChild->getAttribute('stdDeviation'));

        $this->expectException(InvalidArgumentException::class);

        $effects->blur(0);
    }

    public function testBlurWithLocale()
    {
        $this->executeTestWithLocale('testBlur');
    }

    public function testBrightness()
    {
        $dom = (new Imagine())->create(new UndefinedBox())->getDomDocument();
        $effects = new Effects($dom);

        $this->assertSame($effects, $effects->brightness(10));
        $this->assertTrue($dom->documentElement->firstChild->hasAttribute('filter'));

        /** @var \DOMElement $filter */
        $filter = $dom->getElementsByTagName('filter')[0];
        $filterId = explode(')', explode('#', $dom->documentElement->firstChild->getAttribute('filter'))[1])[0];

        $this->assertSame($filterId, $filter->getAttribute('id'));
        $this->assertSame('feComponentTransfer', $filter->firstChild->nodeName);
        $this->assertSame('linear', $filter->firstChild->firstChild->getAttribute('type'));
        $this->assertSame('0.1', $filter->firstChild->firstChild->getAttribute('intercept'));
        $this->assertSame(3, $filter->firstChild->childNodes->length);
        $this->assertSame(1, $filter->firstChild->getElementsByTagName('feFuncR')->length);
        $this->assertSame(1, $filter->firstChild->getElementsByTagName('feFuncG')->length);
        $this->assertSame(1, $filter->firstChild->getElementsByTagName('feFuncB')->length);

        $effects->brightness(-99);

        $this->assertSame('url(#'.$filterId.')', $dom->documentElement->firstChild->getAttribute('filter'));
        $this->assertSame(2, $filter->childNodes->length);
        $this->assertSame('feComponentTransfer', $filter->lastChild->nodeName);
        $this->assertSame('linear', $filter->lastChild->lastChild->getAttribute('type'));
        $this->assertSame('-0.99', $filter->lastChild->lastChild->getAttribute('intercept'));

        $effects->brightness(-100);
        $this->assertSame('-1', $filter->lastChild->lastChild->getAttribute('intercept'));

        $effects->brightness(100);
        $this->assertSame('1', $filter->lastChild->lastChild->getAttribute('intercept'));

        $this->expectException(InvalidArgumentException::class);

        $effects->brightness(101);
    }

    public function testBrightnessWithLocale()
    {
        $this->executeTestWithLocale('testBrightness');
    }

    public function testConvolve()
    {
        if (!class_exists(Matrix::class)) {
            $this->markTestSkipped('Effects::convolve() in only available since Imagine 1.0');
        }

        $dom = (new Imagine())->create(new UndefinedBox())->getDomDocument();
        $effects = new Effects($dom);

        $this->assertSame($effects, $effects->convolve(new Matrix(3, 3, [-1.9, 0.02, -1, -1, 10, -1.0, -1, -3.12])));
        $this->assertTrue($dom->documentElement->firstChild->hasAttribute('filter'));

        /** @var \DOMElement $filter */
        $filter = $dom->getElementsByTagName('filter')[0];
        $filterId = explode(')', explode('#', $dom->documentElement->firstChild->getAttribute('filter'))[1])[0];

        $this->assertSame($filterId, $filter->getAttribute('id'));
        $this->assertSame('feConvolveMatrix', $filter->firstChild->nodeName);
        $this->assertSame('1', $filter->firstChild->getAttribute('kernelUnitLength'));
        $this->assertSame(
            ['-1.9', '0.02', '-1', '-1', '10', '-1', '-1', '-3.12', '0'],
            preg_split('/\s+/', $filter->firstChild->getAttribute('kernelMatrix'))
        );
        $this->assertEmpty($filter->firstChild->getAttribute('divisor'));
        $this->assertEmpty($filter->firstChild->getAttribute('order'));

        $effects->convolve(new Matrix(5, 3, [-1, -1, -1, -1, -1, -1, -1, 9]));

        $this->assertSame('url(#'.$filterId.')', $dom->documentElement->firstChild->getAttribute('filter'));
        $this->assertSame(2, $filter->childNodes->length);
        $this->assertSame('feConvolveMatrix', $filter->lastChild->nodeName);
        $this->assertSame('1', $filter->lastChild->getAttribute('kernelUnitLength'));
        $this->assertSame(
            ['-1', '-1', '-1', '-1', '-1', '-1', '-1', '9', '0', '0', '0', '0', '0', '0', '0'],
            preg_split('/\s+/', $filter->lastChild->getAttribute('kernelMatrix'))
        );
        $this->assertSame('1', $filter->lastChild->getAttribute('divisor'));
        $this->assertSame('5 3', $filter->lastChild->getAttribute('order'));

        $effects->convolve(new Matrix(3, 3, [0, 0, 0, 0, 0, 0, 0, 0]));
        $this->assertSame('feConvolveMatrix', $filter->lastChild->nodeName);
        $this->assertSame('1', $filter->lastChild->getAttribute('kernelUnitLength'));
        $this->assertSame(
            ['0', '0', '0', '0', '0', '0', '0', '0', '0'],
            preg_split('/\s+/', $filter->lastChild->getAttribute('kernelMatrix'))
        );
        $this->assertEmpty($filter->lastChild->getAttribute('divisor'));
        $this->assertEmpty($filter->firstChild->getAttribute('order'));
    }

    public function testConvolveWithLocale()
    {
        $this->executeTestWithLocale('testConvolve');
    }

    public function testMultipleFilters()
    {
        $image = (new Imagine())->create(new UndefinedBox());

        $dom = $image->getDomDocument();
        $dom->documentElement->appendChild($dom->createElement('path'));

        $image->effects()
            ->gamma(1.5)
            ->negative()
            ->grayscale()
            ->colorize(new ColorRgb(new PaletteRgb(), [255, 0, 0], 100))
            ->sharpen()
            ->blur(2)
        ;

        $this->assertTrue($dom->documentElement->firstChild->hasAttribute('filter'));

        /** @var \DOMElement $filter */
        $filter = $dom->getElementsByTagName('filter')[0];
        $filterId1 = explode(')', explode('#', $dom->documentElement->firstChild->getAttribute('filter'))[1])[0];

        $this->assertSame($filterId1, $filter->getAttribute('id'));
        $this->assertSame(6, $filter->childNodes->length);

        $dom->documentElement->firstChild->setAttribute('filter', 'url(#differentId)');
        $image->effects()->grayscale();

        $this->assertTrue($dom->documentElement->firstChild->hasAttribute('filter'));

        $filter = $dom->getElementsByTagName('filter')[0];
        $filterId2 = explode(')', explode('#', $dom->documentElement->firstChild->getAttribute('filter'))[1])[0];

        /** @var \DOMElement $g */
        $g = $dom->getElementsByTagName('g')[1];

        $this->assertNotSame($filterId1, $filterId2);
        $this->assertSame($filterId2, $filter->getAttribute('id'));
        $this->assertSame(1, $filter->childNodes->length);
        $this->assertSame(2, $dom->getElementsByTagName('g')->length);
        $this->assertSame('url(#differentId)', $g->getAttribute('filter'));
    }

    /**
     * @param string $methodName
     */
    private function executeTestWithLocale($methodName)
    {
        $locale = setlocale(LC_NUMERIC, 0);
        if (false === $locale) {
            $this->markTestSkipped('Your platform does not support locales.');
        }
        try {
            $requiredLocales = ['de_DE.UTF-8', 'de_DE.UTF8', 'de_DE.utf-8', 'de_DE.utf8', 'German_Germany.1252'];
            if (false === setlocale(LC_NUMERIC, $requiredLocales)) {
                $this->markTestSkipped('Could not set any of required locales: '.implode(', ', $requiredLocales));
            }
            $this->$methodName();
        } finally {
            setlocale(LC_NUMERIC, $locale);
        }
    }
}

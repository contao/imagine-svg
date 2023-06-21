<?php

declare(strict_types=1);

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
use Contao\ImagineSvg\SvgBox;
use Imagine\Exception\InvalidArgumentException;
use Imagine\Exception\NotSupportedException;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\Palette\Color\RGB as ColorRgb;
use Imagine\Image\Palette\RGB as PaletteRgb;
use Imagine\Utils\Matrix;
use PHPUnit\Framework\TestCase;

class EffectsTest extends TestCase
{
    public function testInstantiation(): void
    {
        $this->assertInstanceOf(Effects::class, new Effects(new \DOMDocument()));
    }

    public function testGamma(): void
    {
        $dom = (new Imagine())->create(SvgBox::createTypeNone())->getDomDocument();
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

    public function testGammaWithLocale(): void
    {
        $this->executeTestWithLocale('testGamma');
    }

    public function testNegative(): void
    {
        $dom = (new Imagine())->create(SvgBox::createTypeNone())->getDomDocument();
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
            '0', '0', '0', '1', '0',
        ], preg_split('/\s+/', $filter->firstChild->getAttribute('values')));
    }

    public function testGrayscale(): void
    {
        $dom = (new Imagine())->create(SvgBox::createTypeNone())->getDomDocument();
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

    public function testColorize(): void
    {
        $dom = (new Imagine())->create(SvgBox::createTypeNone())->getDomDocument();
        $effects = new Effects($dom);
        $color = new ColorRgb(new PaletteRgb(), [255, 51, 85], 100);

        $this->assertSame($effects, $effects->colorize($color));
        $this->assertTrue($dom->documentElement->firstChild->hasAttribute('filter'));

        /** @var \DOMElement $filter */
        $filter = $dom->getElementsByTagName('filter')[0];
        $filterId = explode(')', explode('#', $dom->documentElement->firstChild->getAttribute('filter'))[1])[0];

        $this->assertSame($filterId, $filter->getAttribute('id'));
        $this->assertSame('feColorMatrix', $filter->firstChild->nodeName);
        $this->assertSame('matrix', $filter->firstChild->getAttribute('type'));
        $this->assertSame('1 0 0 0 1 0 1 0 0 0.2 0 0 1 0 0.3333333 0 0 0 1 0', $filter->firstChild->getAttribute('values'));

        $color = new ColorRgb(new PaletteRgb(), [51, 255, 85], 100);
        $effects->colorize($color);

        $this->assertSame('url(#'.$filterId.')', $dom->documentElement->firstChild->getAttribute('filter'));
        $this->assertSame(2, $filter->childNodes->length);
        $this->assertSame('feColorMatrix', $filter->lastChild->nodeName);
        $this->assertSame('matrix', $filter->lastChild->getAttribute('type'));
        $this->assertSame('1 0 0 0 0.2 0 1 0 0 1 0 0 1 0 0.3333333 0 0 0 1 0', $filter->lastChild->getAttribute('values'));

        $this->expectException(NotSupportedException::class);

        $effects->colorize($this->createMock(ColorInterface::class));
    }

    public function testSharpen(): void
    {
        $dom = (new Imagine())->create(SvgBox::createTypeNone())->getDomDocument();
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
            '-0.12', '1.56', '-0.12',
            '-0.02', '-0.12', '-0.02',
        ], preg_split('/\s+/', $filter->firstChild->getAttribute('kernelMatrix')));
    }

    public function testBlur(): void
    {
        $dom = (new Imagine())->create(SvgBox::createTypeNone())->getDomDocument();
        $effects = new Effects($dom);

        $this->assertSame($effects, $effects->blur(1.5));
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

    public function testBlurWithLocale(): void
    {
        $this->executeTestWithLocale('testBlur');
    }

    public function testBrightness(): void
    {
        $dom = (new Imagine())->create(SvgBox::createTypeNone())->getDomDocument();
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

    public function testBrightnessWithLocale(): void
    {
        $this->executeTestWithLocale('testBrightness');
    }

    public function testConvolve(): void
    {
        if (!class_exists(Matrix::class)) {
            $this->markTestSkipped('Effects::convolve() in only available since Imagine 1.0');
        }

        $dom = (new Imagine())->create(SvgBox::createTypeNone())->getDomDocument();
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

    public function testConvolveWithLocale(): void
    {
        $this->executeTestWithLocale('testConvolve');
    }

    public function testMultipleFilters(): void
    {
        $image = (new Imagine())->create(SvgBox::createTypeNone());

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

        $this->assertSame(
            implode(
                '',
                [
                    '<?xml version="1.0"?>',
                    "\n",
                    '<svg xmlns="http://www.w3.org/2000/svg" version="1.1">',
                    '<g filter="url(#svgImagineFilterV1_6437663136623533)">',
                    '<filter id="svgImagineFilterV1_6437663136623533">',
                    '<feColorMatrix type="saturate" values="0" color-interpolation-filters="sRGB"/>',
                    '</filter>',
                    '<g filter="url(#differentId)">',
                    '<filter id="svgImagineFilterV1_6334646239396237">',
                    '<feComponentTransfer color-interpolation-filters="sRGB">',
                    '<feFuncR type="gamma" exponent="0.6666667"/>',
                    '<feFuncG type="gamma" exponent="0.6666667"/>',
                    '<feFuncB type="gamma" exponent="0.6666667"/>',
                    '</feComponentTransfer>',
                    '<feColorMatrix type="matrix" values="-1 0 0 0 1 0 -1 0 0 1 0 0 -1 0 1 0 0  0 1 0" color-interpolation-filters="sRGB"/>',
                    '<feColorMatrix type="saturate" values="0" color-interpolation-filters="sRGB"/>',
                    '<feColorMatrix type="matrix" values="1 0 0 0 1 0 1 0 0 0 0 0 1 0 0 0 0 0 1 0" color-interpolation-filters="sRGB"/>',
                    '<feConvolveMatrix kernelUnitLength="1" kernelMatrix="-0.02 -0.12 -0.02 -0.12  1.56 -0.12 -0.02 -0.12 -0.02" color-interpolation-filters="sRGB"/>',
                    '<feGaussianBlur stdDeviation="2" color-interpolation-filters="sRGB"/>',
                    '</filter>',
                    '<path/>',
                    '</g>',
                    '</g>',
                    '</svg>',
                    "\n",
                ]
            ),
            $image->get('svg')
        );
    }

    private function executeTestWithLocale(string $methodName): void
    {
        $locale = setlocale(LC_NUMERIC, '0');

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

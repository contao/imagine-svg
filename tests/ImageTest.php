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
use Contao\ImagineSvg\Image;
use Contao\ImagineSvg\Imagine;
use Contao\ImagineSvg\SvgBox;
use Imagine\Effects\EffectsInterface;
use Imagine\Exception\InvalidArgumentException;
use Imagine\Exception\NotSupportedException;
use Imagine\Exception\OutOfBoundsException;
use Imagine\Image\Box;
use Imagine\Image\Fill\FillInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\Metadata\MetadataBag;
use Imagine\Image\Palette\PaletteInterface;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;
use Imagine\Image\ProfileInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class ImageTest extends TestCase
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->rootDir = __DIR__.'/tmp';
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown(): void
    {
        parent::tearDown();

        if (file_exists($this->rootDir)) {
            (new Filesystem())->remove($this->rootDir);
        }
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf('Contao\ImagineSvg\Image', new Image(new \DOMDocument(), new MetadataBag()));
    }

    public function testGetDomDocument(): void
    {
        $document = new \DOMDocument();
        $image = new Image($document, new MetadataBag());

        $this->assertSame($document, $image->getDomDocument());
    }

    public function testCopy(): void
    {
        $document = new \DOMDocument();
        $image1 = new Image($document, new MetadataBag());
        $image2 = $image1->copy();

        $this->assertSame($document, $image1->getDomDocument());
        $this->assertNotSame($document, $image2->getDomDocument());
    }

    public function testClone(): void
    {
        $document = new \DOMDocument();
        $image1 = new Image($document, new MetadataBag());
        $image2 = clone $image1;

        $this->assertSame($document, $image1->getDomDocument());
        $this->assertNotSame($document, $image2->getDomDocument());
    }

    public function testCrop(): void
    {
        $imagine = new Imagine();
        $image = $imagine->create(new Box(100, 100));

        $this->assertSame(100, $image->getSize()->getWidth());
        $this->assertSame(100, $image->getSize()->getHeight());
        $this->assertSame('0 0 100 100', $image->getDomDocument()->documentElement->getAttribute('viewBox'));

        $this->assertSame($image, $image->crop(new Point(25, 25), new Box(50, 50)));

        $this->assertSame(50, $image->getSize()->getWidth());
        $this->assertSame(50, $image->getSize()->getHeight());
        $this->assertSame('0 0 50 50', $image->getDomDocument()->documentElement->getAttribute('viewBox'));

        $imageBefore = $image->get('svg');
        $this->assertSame($image, $image->crop(new Point(0, 0), new Box(50, 50)));
        $this->assertSame($imageBefore, $image->get('svg'));

        $image->getDomDocument()->documentElement->removeAttribute('viewBox');

        $imageBefore = $image->get('svg');
        $image->crop(new Point(0, 0), new Box(50, 50));
        $this->assertSame($imageBefore, $image->get('svg'));

        $image->getDomDocument()->documentElement->setAttribute('viewBox', '0 0 50 50');
        $image->getDomDocument()->documentElement->removeAttribute('width');
        $image->getDomDocument()->documentElement->removeAttribute('height');

        $this->assertSame(SvgBox::TYPE_ASPECT_RATIO, $image->getSize()->getType());
        $image->crop(new Point(0, 0), new Box(50, 50));
        $this->assertSame(SvgBox::TYPE_ABSOLUTE, $image->getSize()->getType());

        $this->expectException(OutOfBoundsException::class);

        $image->crop(new Point(60, 60), new Box(50, 50));
    }

    public function testResize(): void
    {
        $imagine = new Imagine();
        $image = $imagine->create(new Box(100, 100));

        $this->assertSame(100, $image->getSize()->getWidth());
        $this->assertSame(100, $image->getSize()->getHeight());

        $resized = $image->resize(new Box(50, 50));

        $this->assertSame(50, $image->getSize()->getWidth());
        $this->assertSame(50, $image->getSize()->getHeight());
        $this->assertSame($image, $resized, 'Should return itself');

        $image->getDomDocument()->documentElement->removeAttribute('viewBox');
        $image->getDomDocument()->documentElement->setAttribute('width', '50px');
        $image->getDomDocument()->documentElement->setAttribute('height', '3.125em');
        $image->resize(new Box(100, 100));

        $this->assertSame(100, $image->getSize()->getWidth());
        $this->assertSame(100, $image->getSize()->getHeight());

        $this->assertSame(
            '0 0 50 50',
            $image->getDomDocument()->documentElement->getAttribute('viewBox'),
            'Viewbox should get fixed'
        );

        $image->getDomDocument()->documentElement->removeAttribute('width');
        $image->getDomDocument()->documentElement->removeAttribute('height');
        $image->getDomDocument()->documentElement->setAttribute('viewBox', '0 0 100 100');
        $image->resize(new Box(100, 100));

        $this->assertSame(100, $image->getSize()->getWidth());
        $this->assertSame(100, $image->getSize()->getHeight());

        $this->assertSame(
            '100',
            $image->getDomDocument()->documentElement->getAttribute('width'),
            'Relative dimensions should get absolute'
        );

        $this->assertSame(
            '100',
            $image->getDomDocument()->documentElement->getAttribute('height'),
            'Relative dimensions should get absolute'
        );

        $image->getDomDocument()->documentElement->removeAttribute('viewBox');
        $this->assertSame($image, $image->resize(new Box(100, 100)));

        $this->assertSame(100, $image->getSize()->getWidth());
        $this->assertSame(100, $image->getSize()->getHeight());

        $this->assertSame(
            '',
            $image->getDomDocument()->documentElement->getAttribute('viewBox'),
            'Viewbox should not get modified if no resize is necessary'
        );

        $image->getDomDocument()->documentElement->removeAttribute('height');
        $this->assertSame($image, $image->resize(new Box(200, 200)));

        $this->assertSame(200, $image->getSize()->getWidth());
        $this->assertSame(200, $image->getSize()->getHeight());

        $this->assertSame(
            '',
            $image->getDomDocument()->documentElement->getAttribute('viewBox'),
            'Viewbox should not get modified if only one dimension is set'
        );

        $this->expectException(InvalidArgumentException::class);

        $image->resize(new Box(25, 25), ImageInterface::FILTER_POINT);
    }

    public function testSave(): void
    {
        $path = $this->rootDir;

        if (!is_dir(\dirname($path))) {
            mkdir(\dirname($path), 0777, true);
        }

        $imagine = new Imagine();
        $image = $imagine->create(new Box(100, 100));
        $this->assertSame($image, $image->save($path));

        $contents = file_get_contents($path);

        $document = new \DOMDocument();
        $document->loadXML($contents);

        $this->assertSame('svg', $document->documentElement->tagName);
        $this->assertSame('100', $document->documentElement->getAttribute('width'));
        $this->assertSame('100', $document->documentElement->getAttribute('height'));

        $image->save($path.'.svg');

        $contents = file_get_contents($path.'.svg');

        $document = new \DOMDocument();
        $document->loadXML($contents);

        $this->assertSame('svg', $document->documentElement->tagName);
        $this->assertSame('100', $document->documentElement->getAttribute('width'));
        $this->assertSame('100', $document->documentElement->getAttribute('height'));

        $image->save($path.'.foo', ['format' => 'svg']);

        $contents = file_get_contents($path.'.foo');

        $document = new \DOMDocument();
        $document->loadXML($contents);

        $this->assertSame('svg', $document->documentElement->tagName);
        $this->assertSame('100', $document->documentElement->getAttribute('width'));
        $this->assertSame('100', $document->documentElement->getAttribute('height'));

        $image->save($path.'.svgz');

        $contents = gzdecode(file_get_contents($path.'.svgz'));

        $document = new \DOMDocument();
        $document->loadXML($contents);

        $this->assertSame('svg', $document->documentElement->tagName);
        $this->assertSame('100', $document->documentElement->getAttribute('width'));
        $this->assertSame('100', $document->documentElement->getAttribute('height'));

        unlink($path);
        unlink($path.'.svg');
        unlink($path.'.foo');
        unlink($path.'.svgz');
    }

    public function testShow(): void
    {
        $imagine = new Imagine();
        $image = $imagine->create(new Box(100, 100));

        ob_start();
        $this->assertSame(
            $image,
            @$image->show('svg') // suppress headers already sent warning
        );
        $contents = ob_get_clean();

        $document = new \DOMDocument();
        $document->loadXML($contents);

        $this->assertSame('svg', $document->documentElement->tagName);
        $this->assertSame('100', $document->documentElement->getAttribute('width'));
        $this->assertSame('100', $document->documentElement->getAttribute('height'));

        ob_start();
        @$image->show('svgz'); // suppress headers already sent warning
        $contents = ob_get_clean();

        $document = new \DOMDocument();
        $document->loadXML(gzdecode($contents));

        $this->assertSame('svg', $document->documentElement->tagName);
        $this->assertSame('100', $document->documentElement->getAttribute('width'));
        $this->assertSame('100', $document->documentElement->getAttribute('height'));
    }

    public function testGet(): void
    {
        $imagine = new Imagine();
        $image = $imagine->create(new Box(100, 100));

        $document = new \DOMDocument();
        $document->loadXML($image->get('svg'));

        $this->assertSame('svg', $document->documentElement->tagName);
        $this->assertSame('100', $document->documentElement->getAttribute('width'));
        $this->assertSame('100', $document->documentElement->getAttribute('height'));

        $document = new \DOMDocument();
        $document->loadXML(gzdecode($image->get('svgz')));

        $this->assertSame('svg', $document->documentElement->tagName);
        $this->assertSame('100', $document->documentElement->getAttribute('width'));
        $this->assertSame('100', $document->documentElement->getAttribute('height'));

        $this->expectException(InvalidArgumentException::class);

        $image->get('jpg');
    }

    public function testToString(): void
    {
        $imagine = new Imagine();
        $image = $imagine->create(new Box(100, 100));

        $document = new \DOMDocument();
        $document->loadXML((string) $image);

        $this->assertSame('svg', $document->documentElement->tagName);
        $this->assertSame('100', $document->documentElement->getAttribute('width'));
        $this->assertSame('100', $document->documentElement->getAttribute('height'));
    }

    public function testGetSize(): void
    {
        $imagine = new Imagine();
        $image = $imagine->create(new Box(100, 100));
        $svg = $image->getDomDocument()->documentElement;

        $this->assertSame(SvgBox::TYPE_ABSOLUTE, $image->getSize()->getType());
        $this->assertSame(100, $image->getSize()->getWidth());
        $this->assertSame(100, $image->getSize()->getHeight());

        $svg->setAttribute('height', '50');

        $this->assertSame(SvgBox::TYPE_ABSOLUTE, $image->getSize()->getType());
        $this->assertSame(100, $image->getSize()->getWidth());
        $this->assertSame(50, $image->getSize()->getHeight());

        $svg->setAttribute('viewBox', '0 0 200 100');
        $svg->removeAttribute('height');

        $this->assertSame(SvgBox::TYPE_ABSOLUTE, $image->getSize()->getType());
        $this->assertSame(100, $image->getSize()->getWidth());
        $this->assertSame(50, $image->getSize()->getHeight());

        $svg->setAttribute('height', '200');
        $svg->removeAttribute('width');

        $this->assertSame(SvgBox::TYPE_ABSOLUTE, $image->getSize()->getType());
        $this->assertSame(400, $image->getSize()->getWidth());
        $this->assertSame(200, $image->getSize()->getHeight());

        $svg->removeAttribute('height');

        $this->assertSame(SvgBox::TYPE_ASPECT_RATIO, $image->getSize()->getType());
        $this->assertSame(200, $image->getSize()->getWidth());
        $this->assertSame(100, $image->getSize()->getHeight());

        $svg->setAttribute('viewBox', '0 0 1 0.5');

        $this->assertSame(SvgBox::TYPE_ASPECT_RATIO, $image->getSize()->getType());
        $this->assertSame(2, $image->getSize()->getWidth() / $image->getSize()->getHeight());

        $svg->setAttribute('viewBox', '0 0 0.001 0.000333');

        $this->assertSame(SvgBox::TYPE_ASPECT_RATIO, $image->getSize()->getType());
        $this->assertSame(1 / 0.333, $image->getSize()->getWidth() / $image->getSize()->getHeight());

        $svg->removeAttribute('viewBox');

        $this->assertSame(SvgBox::TYPE_NONE, $image->getSize()->getType());
        $this->assertSame(300, $image->getSize()->getWidth());
        $this->assertSame(150, $image->getSize()->getHeight());

        $svg->setAttribute('width', '100');

        $this->assertSame(SvgBox::TYPE_NONE, $image->getSize()->getType());
        $this->assertSame(300, $image->getSize()->getWidth());
        $this->assertSame(150, $image->getSize()->getHeight());

        $svg->removeAttribute('width');
        $svg->setAttribute('height', '100');

        $this->assertSame(SvgBox::TYPE_NONE, $image->getSize()->getType());
        $this->assertSame(300, $image->getSize()->getWidth());
        $this->assertSame(150, $image->getSize()->getHeight());
    }

    /**
     * @param string $value
     * @param int    $expected
     *
     * @dataProvider getGetSizePixelValues
     */
    public function testGetSizePixelValues($value, $expected): void
    {
        $imagine = new Imagine();
        $image = $imagine->create(new Box(100, 100));

        $document = $image->getDomDocument();
        $document->documentElement->setAttribute('width', $value);
        $document->documentElement->setAttribute('height', $value);
        $document->documentElement->removeAttribute('viewBox');

        if (null === $expected) {
            $this->assertSame(SvgBox::TYPE_NONE, $image->getSize()->getType());
        } else {
            $this->assertSame($expected, $image->getSize()->getWidth());
            $this->assertSame($expected, $image->getSize()->getHeight());
        }
    }

    public function getGetSizePixelValues(): array
    {
        return [
            'No unit' => ['1234.5', 1235],
            'px' => ['1234.5px', 1235],
            'em' => ['1em', 16],
            'ex' => ['2ex', 16],
            'pt' => ['12pt', 16],
            'pc' => ['1pc', 16],
            'in' => [(1 / 6).'in', 16],
            'cm' => [(2.54 / 6).'cm', 16],
            'mm' => [(25.4 / 6).'mm', 16],
            'No unit with spacing' => [" \r \n \t 1234.5 \r \n \t ", 1235],
            'em with spacing' => [" \r \n \t 1234.5em \r \n \t ", 19752],
            'unknown' => ['100vw', null],
            'unknown mmm' => ['1mmm', null],
            'invalid' => ['abc', null],
            'invalid number' => ['12.34.5', null],
        ];
    }

    public function testPaste(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->expectException(NotSupportedException::class);

        $image->paste(new Image(new \DOMDocument(), new MetadataBag()), new Point(0, 0));
    }

    public function testRotate(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->expectException(NotSupportedException::class);

        $image->rotate(90);
    }

    public function testFlipHorizontally(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->expectException(NotSupportedException::class);

        $image->flipHorizontally();
    }

    public function testFlipVertically(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->expectException(NotSupportedException::class);

        $image->flipVertically();
    }

    /**
     * @dataProvider getStrip
     */
    public function testStrip(string $svg, string $expected): void
    {
        $image = (new Imagine())
            ->load($svg)
            ->strip()
        ;

        $this->assertSame($expected, $image->get('svg'));
    }

    public function getStrip(): array
    {
        return [
            'Comment' => [
                "<?xml version=\"1.0\"?>\n<svg><!-- comment --></svg>\n",
                "<?xml version=\"1.0\"?>\n<svg/>\n",
            ],
            'Multiple comments' => [
                "<?xml version=\"1.0\"?><!-- comment -->\n<!-- comment --><svg><!-- comment --></svg><!-- comment -->\n",
                "<?xml version=\"1.0\"?>\n<svg/>\n",
            ],
            'Complex comments' => [
                "<?xml version=\"1.0\"?>\n<svg><!-- <!- -> -> \n</svg>\n  --></svg>\n",
                "<?xml version=\"1.0\"?>\n<svg/>\n",
            ],
        ];
    }

    public function testDraw(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->expectException(NotSupportedException::class);

        $image->draw();
    }

    public function testEffects(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->assertInstanceOf(EffectsInterface::class, $image->effects());
        $this->assertInstanceOf(Effects::class, $image->effects());
    }

    public function testApplyMask(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->expectException(NotSupportedException::class);

        $image->applyMask(new Image(new \DOMDocument(), new MetadataBag()));
    }

    public function testFill(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->expectException(NotSupportedException::class);

        $image->fill($this->createMock(FillInterface::class));
    }

    public function testMask(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->expectException(NotSupportedException::class);

        $image->mask();
    }

    public function testHistogram(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->expectException(NotSupportedException::class);

        $image->histogram();
    }

    public function testGetColorAt(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->expectException(NotSupportedException::class);

        $image->getColorAt(new Point(0, 0));
    }

    public function testLayers(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->expectException(NotSupportedException::class);

        $image->layers();
    }

    public function testInterlace(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->expectException(NotSupportedException::class);

        $image->interlace('');
    }

    public function testPalette(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->assertInstanceOf(RGB::class, $image->palette());
    }

    public function testProfile(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->expectException(NotSupportedException::class);

        $image->profile($this->createMock(ProfileInterface::class));
    }

    public function testUsePalette(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());
        $paletteRgb = $this->createMock(RGB::class);

        $this->assertSame($image, $image->usePalette($paletteRgb));
        $this->assertSame($paletteRgb, $image->palette());

        $this->expectException(NotSupportedException::class);

        $image->usePalette($this->createMock(PaletteInterface::class));
    }
}

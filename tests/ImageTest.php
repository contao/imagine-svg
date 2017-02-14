<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ImagineSvg\Tests;

use Contao\ImagineSvg\Image;
use Contao\ImagineSvg\Imagine;
use Imagine\Image\ImageInterface;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Image\Metadata\MetadataBag;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests the Image class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class ImageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->rootDir = __DIR__.'/tmp';
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        if (file_exists($this->rootDir)) {
            (new Filesystem())->remove($this->rootDir);
        }
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\ImagineSvg\Image', new Image(new \DOMDocument(), new MetadataBag()));
    }

    /**
     * Tests the getDomDocument() method.
     */
    public function testGetDomDocument()
    {
        $document = new \DOMDocument();
        $image = new Image($document, new MetadataBag());

        $this->assertSame($document, $image->getDomDocument());
    }

    /**
     * Tests the copy() method.
     */
    public function testCopy()
    {
        $document = new \DOMDocument();
        $image1 = new Image($document, new MetadataBag());
        $image2 = $image1->copy();

        $this->assertSame($document, $image1->getDomDocument());
        $this->assertNotSame($document, $image2->getDomDocument());
    }

    /**
     * Tests the __clone() method.
     */
    public function testClone()
    {
        $document = new \DOMDocument();
        $image1 = new Image($document, new MetadataBag());
        $image2 = clone $image1;

        $this->assertSame($document, $image1->getDomDocument());
        $this->assertNotSame($document, $image2->getDomDocument());
    }

    /**
     * Tests the crop() method.
     */
    public function testCrop()
    {
        $imagine = new Imagine();
        $image = $imagine->create(new Box(100, 100));

        $this->assertEquals(100, $image->getSize()->getWidth());
        $this->assertEquals(100, $image->getSize()->getHeight());

        $image->crop(new Point(25, 25), new Box(50, 50));

        $this->assertEquals(50, $image->getSize()->getWidth());
        $this->assertEquals(50, $image->getSize()->getHeight());

        $this->setExpectedException('Imagine\Exception\OutOfBoundsException');

        $image->crop(new Point(60, 60), new Box(50, 50));
    }

    /**
     * Tests the resize() method.
     */
    public function testResize()
    {
        $imagine = new Imagine();
        $image = $imagine->create(new Box(100, 100));

        $this->assertEquals(100, $image->getSize()->getWidth());
        $this->assertEquals(100, $image->getSize()->getHeight());

        $resized = $image->resize(new Box(50, 50));

        $this->assertEquals(50, $image->getSize()->getWidth());
        $this->assertEquals(50, $image->getSize()->getHeight());
        $this->assertSame($image, $resized, 'Should return itself');

        $image->getDomDocument()->documentElement->removeAttribute('viewBox');
        $image->resize(new Box(100, 100));

        $this->assertEquals(100, $image->getSize()->getWidth());
        $this->assertEquals(100, $image->getSize()->getHeight());

        $this->assertEquals(
            '0 0 50 50',
            $image->getDomDocument()->documentElement->getAttribute('viewBox'),
            'Viewbox should get fixed'
        );

        $image->getDomDocument()->documentElement->removeAttribute('width');
        $image->getDomDocument()->documentElement->removeAttribute('height');
        $image->getDomDocument()->documentElement->setAttribute('viewBox', '0 0 100 100');
        $image->resize(new Box(100, 100));

        $this->assertEquals(100, $image->getSize()->getWidth());
        $this->assertEquals(100, $image->getSize()->getHeight());

        $this->assertEquals(
            '100',
            $image->getDomDocument()->documentElement->getAttribute('width'),
            'Relative dimensions should get absolute'
        );

        $this->assertEquals(
            '100',
            $image->getDomDocument()->documentElement->getAttribute('height'),
            'Relative dimensions should get absolute'
        );

        $image->getDomDocument()->documentElement->removeAttribute('viewBox');
        $image->resize(new Box(100, 100));

        $this->assertEquals(100, $image->getSize()->getWidth());
        $this->assertEquals(100, $image->getSize()->getHeight());

        $this->assertEquals(
            '',
            $image->getDomDocument()->documentElement->getAttribute('viewBox'),
            'Viewbox should not get modified if no resize is necessary'
        );

        $this->setExpectedException('Imagine\Exception\InvalidArgumentException');

        $image->resize(new Box(25, 25), ImageInterface::FILTER_POINT);
    }

    /**
     * Tests the save() method.
     */
    public function testSave()
    {
        $path = $this->rootDir;

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $imagine = new Imagine();
        $image = $imagine->create(new Box(100, 100));
        $image->save($path);

        $contents = file_get_contents($path);

        $document = new \DOMDocument();
        $document->loadXML($contents);

        $this->assertEquals('svg', $document->documentElement->tagName);
        $this->assertEquals(100, $document->documentElement->getAttribute('width'));
        $this->assertEquals(100, $document->documentElement->getAttribute('height'));

        $image->save($path.'.svg');

        $contents = file_get_contents($path.'.svg');

        $document = new \DOMDocument();
        $document->loadXML($contents);

        $this->assertEquals('svg', $document->documentElement->tagName);
        $this->assertEquals(100, $document->documentElement->getAttribute('width'));
        $this->assertEquals(100, $document->documentElement->getAttribute('height'));

        $image->save($path.'.foo', ['format' => 'svg']);

        $contents = file_get_contents($path.'.foo');

        $document = new \DOMDocument();
        $document->loadXML($contents);

        $this->assertEquals('svg', $document->documentElement->tagName);
        $this->assertEquals(100, $document->documentElement->getAttribute('width'));
        $this->assertEquals(100, $document->documentElement->getAttribute('height'));

        $image->save($path.'.svgz');

        $contents = gzdecode(file_get_contents($path.'.svgz'));

        $document = new \DOMDocument();
        $document->loadXML($contents);

        $this->assertEquals('svg', $document->documentElement->tagName);
        $this->assertEquals(100, $document->documentElement->getAttribute('width'));
        $this->assertEquals(100, $document->documentElement->getAttribute('height'));

        unlink($path);
        unlink($path.'.svg');
        unlink($path.'.foo');
        unlink($path.'.svgz');
    }

    /**
     * Tests the show() method.
     */
    public function testShow()
    {
        $imagine = new Imagine();
        $image = $imagine->create(new Box(100, 100));

        ob_start();
        @$image->show('svg'); // suppress headers already sent warning
        $contents = ob_get_clean();

        $document = new \DOMDocument();
        $document->loadXML($contents);

        $this->assertEquals('svg', $document->documentElement->tagName);
        $this->assertEquals(100, $document->documentElement->getAttribute('width'));
        $this->assertEquals(100, $document->documentElement->getAttribute('height'));

        ob_start();
        @$image->show('svgz'); // suppress headers already sent warning
        $contents = ob_get_clean();

        $document = new \DOMDocument();
        $document->loadXML(gzdecode($contents));

        $this->assertEquals('svg', $document->documentElement->tagName);
        $this->assertEquals(100, $document->documentElement->getAttribute('width'));
        $this->assertEquals(100, $document->documentElement->getAttribute('height'));
    }

    /**
     * Tests the get() method.
     */
    public function testGet()
    {
        $imagine = new Imagine();
        $image = $imagine->create(new Box(100, 100));

        $document = new \DOMDocument();
        $document->loadXML($image->get('svg'));

        $this->assertEquals('svg', $document->documentElement->tagName);
        $this->assertEquals(100, $document->documentElement->getAttribute('width'));
        $this->assertEquals(100, $document->documentElement->getAttribute('height'));

        $document = new \DOMDocument();
        $document->loadXML(gzdecode($image->get('svgz')));

        $this->assertEquals('svg', $document->documentElement->tagName);
        $this->assertEquals(100, $document->documentElement->getAttribute('width'));
        $this->assertEquals(100, $document->documentElement->getAttribute('height'));

        $this->setExpectedException('Imagine\Exception\InvalidArgumentException');

        $image->get('jpg');
    }

    /**
     * Tests the __toString() method.
     */
    public function testToString()
    {
        $imagine = new Imagine();
        $image = $imagine->create(new Box(100, 100));

        $document = new \DOMDocument();
        $document->loadXML((string) $image);

        $this->assertEquals('svg', $document->documentElement->tagName);
        $this->assertEquals(100, $document->documentElement->getAttribute('width'));
        $this->assertEquals(100, $document->documentElement->getAttribute('height'));
    }

    /**
     * Tests the getSize() method.
     */
    public function testGetSize()
    {
        $imagine = new Imagine();
        $image = $imagine->create(new Box(100, 100));
        $svg = $image->getDomDocument()->documentElement;

        $this->assertNotInstanceOf('Contao\ImagineSvg\RelativeBoxInterface', $image->getSize());
        $this->assertEquals(100, $image->getSize()->getWidth());
        $this->assertEquals(100, $image->getSize()->getHeight());

        $svg->setAttribute('height', 50);

        $this->assertNotInstanceOf('Contao\ImagineSvg\RelativeBoxInterface', $image->getSize());
        $this->assertEquals(100, $image->getSize()->getWidth());
        $this->assertEquals(50, $image->getSize()->getHeight());

        $svg->setAttribute('viewBox', '0 0 200 100');
        $svg->removeAttribute('height');

        $this->assertNotInstanceOf('Contao\ImagineSvg\RelativeBoxInterface', $image->getSize());
        $this->assertEquals(100, $image->getSize()->getWidth());
        $this->assertEquals(50, $image->getSize()->getHeight());

        $svg->setAttribute('height', 200);
        $svg->removeAttribute('width');

        $this->assertNotInstanceOf('Contao\ImagineSvg\RelativeBoxInterface', $image->getSize());
        $this->assertEquals(400, $image->getSize()->getWidth());
        $this->assertEquals(200, $image->getSize()->getHeight());

        $svg->removeAttribute('height');

        $this->assertInstanceOf('Contao\ImagineSvg\RelativeBoxInterface', $image->getSize());
        $this->assertEquals(200, $image->getSize()->getWidth());
        $this->assertEquals(100, $image->getSize()->getHeight());

        $svg->setAttribute('viewBox', '0 0 1 0.5');

        $this->assertInstanceOf('Contao\ImagineSvg\RelativeBoxInterface', $image->getSize());
        $this->assertEquals(1 / 0.5, $image->getSize()->getWidth() / $image->getSize()->getHeight());

        $svg->setAttribute('viewBox', '0 0 0.001 0.000333');

        $this->assertInstanceOf('Contao\ImagineSvg\RelativeBoxInterface', $image->getSize());
        $this->assertEquals(1 / 0.333, $image->getSize()->getWidth() / $image->getSize()->getHeight());

        $svg->removeAttribute('viewBox');

        $this->assertInstanceOf('Contao\ImagineSvg\UndefinedBoxInterface', $image->getSize());
        $this->assertEquals(0, $image->getSize()->getWidth());
        $this->assertEquals(0, $image->getSize()->getHeight());

        $svg->setAttribute('width', 100);

        $this->assertInstanceOf('Contao\ImagineSvg\UndefinedBoxInterface', $image->getSize());
        $this->assertEquals(0, $image->getSize()->getWidth());
        $this->assertEquals(0, $image->getSize()->getHeight());

        $svg->removeAttribute('width');
        $svg->setAttribute('height', 100);

        $this->assertInstanceOf('Contao\ImagineSvg\UndefinedBoxInterface', $image->getSize());
        $this->assertEquals(0, $image->getSize()->getWidth());
        $this->assertEquals(0, $image->getSize()->getHeight());
    }

    /**
     * Tests the getSize() method.
     *
     * @param string $value
     * @param int    $expected
     *
     * @dataProvider getGetSizePixelValues
     */
    public function testGetSizePixelValues($value, $expected)
    {
        $imagine = new Imagine();
        $image = $imagine->create(new Box(100, 100));
        $document = $image->getDomDocument();

        $document->documentElement->setAttribute('width', $value);
        $document->documentElement->setAttribute('height', $value);
        $this->assertEquals($expected, $image->getSize()->getWidth());
        $this->assertEquals($expected, $image->getSize()->getHeight());
    }

    /**
     * Provides the data for the testGetSizePixelValues() method.
     *
     * @return array
     */
    public function getGetSizePixelValues()
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
            'unknown' => ['100vw', 0],
            'unknown mmm' => ['1mmm', 0],
            'invalid' => ['abc', 0],
            'invalid number' => ['12.34.5', 0],
        ];
    }

    /**
     * Tests the paste() method.
     */
    public function testPaste()
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->setExpectedException('Imagine\Exception\RuntimeException');

        $image->paste(new Image(new \DOMDocument(), new MetadataBag()), new Point(0, 0));
    }

    /**
     * Tests the rotate() method.
     */
    public function testRotate()
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->setExpectedException('Imagine\Exception\RuntimeException');

        $image->rotate(90);
    }

    /**
     * Tests the flipHorizontally() method.
     */
    public function testFlipHorizontally()
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->setExpectedException('Imagine\Exception\RuntimeException');

        $image->flipHorizontally();
    }

    /**
     * Tests the flipVertically() method.
     */
    public function testFlipVertically()
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->setExpectedException('Imagine\Exception\RuntimeException');

        $image->flipVertically();
    }

    /**
     * Tests the strip() method.
     */
    public function testStrip()
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->setExpectedException('Imagine\Exception\RuntimeException');

        $image->strip();
    }

    /**
     * Tests the draw() method.
     */
    public function testDraw()
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->setExpectedException('Imagine\Exception\RuntimeException');

        $image->draw();
    }

    /**
     * Tests the effects() method.
     */
    public function testEffects()
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->setExpectedException('Imagine\Exception\RuntimeException');

        $image->effects();
    }

    /**
     * Tests the applyMask() method.
     */
    public function testApplyMask()
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->setExpectedException('Imagine\Exception\RuntimeException');

        $image->applyMask(new Image(new \DOMDocument(), new MetadataBag()));
    }

    /**
     * Tests the fill() method.
     */
    public function testFill()
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->setExpectedException('Imagine\Exception\RuntimeException');

        $image->fill($this->getMock('Imagine\Image\Fill\FillInterface'));
    }

    /**
     * Tests the mask() method.
     */
    public function testMask()
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->setExpectedException('Imagine\Exception\RuntimeException');

        $image->mask();
    }

    /**
     * Tests the histogram() method.
     */
    public function testHistogram()
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->setExpectedException('Imagine\Exception\RuntimeException');

        $image->histogram();
    }

    /**
     * Tests the getColorAt() method.
     */
    public function testGetColorAt()
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->setExpectedException('Imagine\Exception\RuntimeException');

        $image->getColorAt(new Point(0, 0));
    }

    /**
     * Tests the layers() method.
     */
    public function testLayers()
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->setExpectedException('Imagine\Exception\RuntimeException');

        $image->layers();
    }

    /**
     * Tests the interlace() method.
     */
    public function testInterlace()
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->setExpectedException('Imagine\Exception\RuntimeException');

        $image->interlace('');
    }

    /**
     * Tests the palette() method.
     */
    public function testPalette()
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->setExpectedException('Imagine\Exception\RuntimeException');

        $image->palette();
    }

    /**
     * Tests the profile() method.
     */
    public function testProfile()
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->setExpectedException('Imagine\Exception\RuntimeException');

        $image->profile($this->getMock('Imagine\Image\ProfileInterface'));
    }

    /**
     * Tests the usePalette() method.
     */
    public function testUsePalette()
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->setExpectedException('Imagine\Exception\RuntimeException');

        $image->usePalette($this->getMock('Imagine\Image\Palette\PaletteInterface'));
    }
}

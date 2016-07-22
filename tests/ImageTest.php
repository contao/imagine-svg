<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ImagineSvg\Test;

use Contao\ImagineSvg\Image;
use Contao\ImagineSvg\Imagine;
use Imagine\Image\ImageInterface;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Image\Metadata\MetadataBag;
use Symfony\Filesystem\Filesystem;

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
        $this->rootDir = __DIR__ . '/tmp';
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
        $this->assertEquals('0 0 50 50', $image->getDomDocument()->documentElement->getAttribute('viewBox'), 'Viewbox should get fixed');

        $image->getDomDocument()->documentElement->removeAttribute('width');
        $image->getDomDocument()->documentElement->removeAttribute('height');
        $image->getDomDocument()->documentElement->setAttribute('viewBox', '0 0 100 100');
        $image->resize(new Box(100, 100));

        $this->assertEquals(100, $image->getSize()->getWidth());
        $this->assertEquals(100, $image->getSize()->getHeight());
        $this->assertEquals('100', $image->getDomDocument()->documentElement->getAttribute('width'), 'Relative dimensions should get absolute');
        $this->assertEquals('100', $image->getDomDocument()->documentElement->getAttribute('height'), 'Relative dimensions should get absolute');

        $image->getDomDocument()->documentElement->removeAttribute('viewBox');
        $image->resize(new Box(100, 100));

        $this->assertEquals(100, $image->getSize()->getWidth());
        $this->assertEquals(100, $image->getSize()->getHeight());
        $this->assertEquals('', $image->getDomDocument()->documentElement->getAttribute('viewBox'), 'Viewbox should not get modified if no resize is necessary');

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

        $image->save($path . '.svg');

        $contents = file_get_contents($path . '.svg');
        $document = new \DOMDocument();
        $document->loadXML($contents);

        $this->assertEquals('svg', $document->documentElement->tagName);
        $this->assertEquals(100, $document->documentElement->getAttribute('width'));
        $this->assertEquals(100, $document->documentElement->getAttribute('height'));

        $image->save($path . '.svgz');

        $contents = gzdecode(file_get_contents($path . '.svgz'));
        $document = new \DOMDocument();
        $document->loadXML($contents);

        $this->assertEquals('svg', $document->documentElement->tagName);
        $this->assertEquals(100, $document->documentElement->getAttribute('width'));
        $this->assertEquals(100, $document->documentElement->getAttribute('height'));

        unlink($path);
        unlink($path . '.svg');
        unlink($path . '.svgz');
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
     * @param string $value    The pixel value
     * @param int    $expected The expected value
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
     * @return array The data
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
            'in' => [(1 / 6) . 'in', 16],
            'cm' => [(2.54 / 6) . 'cm', 16],
            'mm' => [(25.4 / 6) . 'mm', 16],
            'unknown' => ['100vw', 0],
            'invalid' => ['abc', 0],
        ];
    }
}

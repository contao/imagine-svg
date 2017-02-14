<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ImagineSvg\Tests;

use Contao\ImagineSvg\Imagine;
use Contao\ImagineSvg\UndefinedBox;
use Imagine\Image\Box;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests the Imagine class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class ImagineTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Imagine
     */
    private $imagine;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * Sets up the imagine instance.
     */
    public function setUp()
    {
        $this->imagine = new Imagine();
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
        $this->assertInstanceOf('Contao\ImagineSvg\Imagine', $this->imagine);
    }

    /**
     * Tests the create() method.
     */
    public function testCreate()
    {
        $image = $this->imagine->create(new Box(100, 100));
        $svg = $image->getDomDocument()->documentElement;

        $this->assertInstanceOf('Contao\ImagineSvg\Image', $image);
        $this->assertInstanceOf('Imagine\Image\ImageInterface', $image);

        $this->assertEquals('svg', $svg->tagName);
        $this->assertEquals('1.1', $svg->getAttribute('version'));
        $this->assertEquals('http://www.w3.org/2000/svg', $svg->getAttribute('xmlns'));
        $this->assertEquals('100', $svg->getAttribute('width'));
        $this->assertEquals('100', $svg->getAttribute('height'));

        $image = $this->imagine->create(new UndefinedBox());
        $svg = $image->getDomDocument()->documentElement;

        $this->assertFalse($svg->hasAttribute('width'));
        $this->assertFalse($svg->hasAttribute('height'));
    }

    /**
     * Tests the create() method with a color.
     */
    public function testCreateWithColor()
    {
        $color = $this->getMock('Imagine\Image\Palette\Color\ColorInterface');

        $this->setExpectedException('Imagine\Exception\InvalidArgumentException');

        $this->imagine->create(new Box(100, 100), $color);
    }

    /**
     * Tests the open() method.
     */
    public function testOpen()
    {
        $path = $this->rootDir;

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $xml = '<?xml version="1.0"?>'
            .'<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="100" height="100"></svg>'
        ;

        file_put_contents($path.'.svg', $xml);

        $image = $this->imagine->open($path.'.svg');

        $this->assertInstanceOf('Contao\ImagineSvg\Image', $image);
        $this->assertInstanceOf('Imagine\Image\ImageInterface', $image);

        $this->assertEquals(100, $image->getSize()->getWidth());
        $this->assertEquals(100, $image->getSize()->getHeight());

        unlink($path.'.svg');
        $image->save();

        $this->assertFileExists($path.'.svg');

        file_put_contents($path.'.svgz', gzencode($xml));

        $image = $this->imagine->open($path.'.svgz');

        $this->assertInstanceOf('Contao\ImagineSvg\Image', $image);
        $this->assertInstanceOf('Imagine\Image\ImageInterface', $image);

        $this->assertEquals(100, $image->getSize()->getWidth());
        $this->assertEquals(100, $image->getSize()->getHeight());

        unlink($path.'.svgz');
        $image->save();

        $this->assertFileExists($path.'.svgz');

        file_put_contents($path, $xml);

        $image = $this->imagine->open($path);

        $this->assertInstanceOf('Contao\ImagineSvg\Image', $image);
        $this->assertInstanceOf('Imagine\Image\ImageInterface', $image);

        $this->assertEquals(100, $image->getSize()->getWidth());
        $this->assertEquals(100, $image->getSize()->getHeight());

        unlink($path);
        $image->save();

        $this->assertFileExists($path);

        unlink($path);
        unlink($path.'.svg');
        unlink($path.'.svgz');
    }

    /**
     * Tests the load() method.
     */
    public function testLoad()
    {
        $xml = '<?xml version="1.0"?>'
            .'<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="100" height="100"></svg>'
        ;

        $image = $this->imagine->load($xml);

        $this->assertInstanceOf('Contao\ImagineSvg\Image', $image);
        $this->assertInstanceOf('Imagine\Image\ImageInterface', $image);

        $this->assertEquals(100, $image->getSize()->getWidth());
        $this->assertEquals(100, $image->getSize()->getHeight());

        $image = $this->imagine->load(gzencode($xml));

        $this->assertInstanceOf('Contao\ImagineSvg\Image', $image);
        $this->assertInstanceOf('Imagine\Image\ImageInterface', $image);

        $this->assertEquals(100, $image->getSize()->getWidth());
        $this->assertEquals(100, $image->getSize()->getHeight());

        $this->setExpectedException('Imagine\Exception\RuntimeException');

        $image->save();
    }

    /**
     * Tests the load() method with an invalid SVG image.
     */
    public function testLoadInvalidSvg()
    {
        $this->setExpectedException('Imagine\Exception\RuntimeException');

        $this->imagine->load('<?xml version="1.0"?><notasvg/>');
    }

    /**
     * Tests the load() method with an invalid XML file.
     */
    public function testLoadInvalidXml()
    {
        $this->setExpectedException('Imagine\Exception\RuntimeException');

        $this->imagine->load('<?xml version="1.0"?><svg><invalid>');
    }

    /**
     * Tests the read() method.
     */
    public function testRead()
    {
        $xml = '<?xml version="1.0"?>'
            .'<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="100" height="100"></svg>'
        ;

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $xml);
        rewind($stream);

        $image = $this->imagine->read($stream);

        $this->assertInstanceOf('Contao\ImagineSvg\Image', $image);
        $this->assertInstanceOf('Imagine\Image\ImageInterface', $image);

        $this->assertEquals(100, $image->getSize()->getWidth());
        $this->assertEquals(100, $image->getSize()->getHeight());

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, gzencode($xml));
        rewind($stream);

        $image = $this->imagine->read($stream);

        $this->assertInstanceOf('Contao\ImagineSvg\Image', $image);
        $this->assertInstanceOf('Imagine\Image\ImageInterface', $image);

        $this->assertEquals(100, $image->getSize()->getWidth());
        $this->assertEquals(100, $image->getSize()->getHeight());

        $this->setExpectedException('Imagine\Exception\RuntimeException');

        $image->save();
    }

    /**
     * Tests the read() method with an invalid resource.
     */
    public function testReadInvalidResource()
    {
        $this->setExpectedException('Imagine\Exception\InvalidArgumentException');

        $this->imagine->read('not a resource');
    }

    /**
     * Tests the font() method.
     */
    public function testFont()
    {
        $color = $this->getMock('Imagine\Image\Palette\Color\ColorInterface');

        $this->setExpectedException('Imagine\Exception\RuntimeException');

        $this->imagine->font($this->rootDir, 10, $color);
    }
}

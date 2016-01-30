<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\ImagineSvg;

use Contao\CoreBundle\ImagineSvg\Image;
use Contao\CoreBundle\ImagineSvg\Imagine;
use Contao\CoreBundle\ImagineSvg\UndefinedBox;
use Contao\CoreBundle\Test\TestCase;
use Imagine\Image\ImageInterface;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Image\Metadata\MetadataBag;

/**
 * Tests the Imagine class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class ImagineTest extends TestCase
{
    /**
     * @var Imagine
     */
    private $imagine;

    /**
     * Sets up the imagine instance
     */
    public function setUp()
    {
        $this->imagine = new Imagine;
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\\CoreBundle\\ImagineSvg\\Imagine', $this->imagine);
    }

    /**
     * Tests the create() method.
     */
    public function testCreate()
    {
        $image = $this->imagine->create(new Box(100, 100));
        $svg = $image->getDomDocument()->documentElement;

        $this->assertInstanceOf('Contao\\CoreBundle\\ImagineSvg\\Image', $image);
        $this->assertInstanceOf('Imagine\\Image\\ImageInterface', $image);

        $this->assertEquals('svg', $svg->tagName);
        $this->assertEquals('1.1', $svg->getAttribute('version'));
        $this->assertEquals('http://www.w3.org/2000/svg', $svg->getAttribute('xmlns'));
        $this->assertEquals('100', $svg->getAttribute('width'));
        $this->assertEquals('100', $svg->getAttribute('height'));

        $image = $this->imagine->create(new UndefinedBox);
        $svg = $image->getDomDocument()->documentElement;

        $this->assertFalse($svg->hasAttribute('width'));
        $this->assertFalse($svg->hasAttribute('height'));
    }

    /**
     * Tests the open() method.
     */
    public function testOpen()
    {
        $path = $this->getRootDir() . '/system/tmp/images/image';
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $xml = '<?xml version="1.0"?>' .
            '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="100" height="100"></svg>';

        file_put_contents($path . '.svg', $xml);

        $image = $this->imagine->open($path . '.svg');

        $this->assertInstanceOf('Contao\\CoreBundle\\ImagineSvg\\Image', $image);
        $this->assertInstanceOf('Imagine\\Image\\ImageInterface', $image);

        $this->assertEquals(100, $image->getSize()->getWidth());
        $this->assertEquals(100, $image->getSize()->getHeight());

        unlink($path . '.svg');
        $image->save();

        $this->assertFileExists($path . '.svg');

        file_put_contents($path . '.svgz', gzencode($xml));

        $image = $this->imagine->open($path . '.svgz');

        $this->assertInstanceOf('Contao\\CoreBundle\\ImagineSvg\\Image', $image);
        $this->assertInstanceOf('Imagine\\Image\\ImageInterface', $image);

        $this->assertEquals(100, $image->getSize()->getWidth());
        $this->assertEquals(100, $image->getSize()->getHeight());

        unlink($path . '.svgz');
        $image->save();

        $this->assertFileExists($path . '.svgz');

        file_put_contents($path, $xml);

        $image = $this->imagine->open($path);

        $this->assertInstanceOf('Contao\\CoreBundle\\ImagineSvg\\Image', $image);
        $this->assertInstanceOf('Imagine\\Image\\ImageInterface', $image);

        $this->assertEquals(100, $image->getSize()->getWidth());
        $this->assertEquals(100, $image->getSize()->getHeight());

        unlink($path);
        $image->save();

        $this->assertFileExists($path);

        unlink($path);
        unlink($path . '.svg');
        unlink($path . '.svgz');
    }

    /**
     * Tests the load() method.
     */
    public function testLoad()
    {
        $xml = '<?xml version="1.0"?>' .
            '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="100" height="100"></svg>';

        $image = $this->imagine->load($xml);

        $this->assertInstanceOf('Contao\\CoreBundle\\ImagineSvg\\Image', $image);
        $this->assertInstanceOf('Imagine\\Image\\ImageInterface', $image);

        $this->assertEquals(100, $image->getSize()->getWidth());
        $this->assertEquals(100, $image->getSize()->getHeight());

        $image = $this->imagine->load(gzencode($xml));

        $this->assertInstanceOf('Contao\\CoreBundle\\ImagineSvg\\Image', $image);
        $this->assertInstanceOf('Imagine\\Image\\ImageInterface', $image);

        $this->assertEquals(100, $image->getSize()->getWidth());
        $this->assertEquals(100, $image->getSize()->getHeight());

        $this->setExpectedException('Imagine\\Exception\\RuntimeException');
        $image->save();
    }

    /**
     * Tests the read() method.
     */
    public function testRead()
    {
        $xml = '<?xml version="1.0"?>' .
            '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="100" height="100"></svg>';

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $xml);
        rewind($stream);
        $image = $this->imagine->read($stream);

        $this->assertInstanceOf('Contao\\CoreBundle\\ImagineSvg\\Image', $image);
        $this->assertInstanceOf('Imagine\\Image\\ImageInterface', $image);

        $this->assertEquals(100, $image->getSize()->getWidth());
        $this->assertEquals(100, $image->getSize()->getHeight());

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, gzencode($xml));
        rewind($stream);
        $image = $this->imagine->read($stream);

        $this->assertInstanceOf('Contao\\CoreBundle\\ImagineSvg\\Image', $image);
        $this->assertInstanceOf('Imagine\\Image\\ImageInterface', $image);

        $this->assertEquals(100, $image->getSize()->getWidth());
        $this->assertEquals(100, $image->getSize()->getHeight());

        $this->setExpectedException('Imagine\\Exception\\RuntimeException');
        $image->save();
    }

}

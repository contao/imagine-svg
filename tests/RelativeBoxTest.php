<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ImagineSvg\Tests;

use Contao\ImagineSvg\RelativeBox;
use Imagine\Image\Point;

/**
 * Tests the RelativeBox class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class RelativeBoxTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $box = new RelativeBox(100, 100);

        $this->assertInstanceOf('Contao\ImagineSvg\RelativeBox', $box);
        $this->assertInstanceOf('Contao\ImagineSvg\RelativeBoxInterface', $box);

        $this->setExpectedException('Imagine\Exception\InvalidArgumentException');

        new RelativeBox(0, 0);
    }

    /**
     * Tests the getWidth() method.
     */
    public function testGetWidth()
    {
        $box = new RelativeBox(100, 100);

        $this->assertEquals(100, $box->getWidth());
    }

    /**
     * Tests the getHeight() method.
     */
    public function testGetHeight()
    {
        $box = new RelativeBox(100, 100);

        $this->assertEquals(100, $box->getHeight());
    }

    /**
     * Tests the scale() method.
     */
    public function testScale()
    {
        $box = new RelativeBox(100, 100);

        $this->assertEquals(new RelativeBox(200, 200), $box->scale(2));
    }

    /**
     * Tests the increase() method.
     */
    public function testIncrease()
    {
        $box = new RelativeBox(100, 100);

        $this->assertEquals(new RelativeBox(200, 200), $box->increase(100));
    }

    /**
     * Tests the contains() method.
     */
    public function testContains()
    {
        $box = new RelativeBox(100, 100);

        $this->assertTrue($box->contains(new RelativeBox(100, 100)));
        $this->assertTrue($box->contains(new RelativeBox(100, 100), new Point(0, 0)));
        $this->assertTrue($box->contains(new RelativeBox(50, 50), new Point(50, 50)));
        $this->assertFalse($box->contains(new RelativeBox(100, 100), new Point(1, 1)));
        $this->assertFalse($box->contains(new RelativeBox(99, 100), new Point(1, 1)));
        $this->assertFalse($box->contains(new RelativeBox(100, 99), new Point(1, 1)));
        $this->assertFalse($box->contains(new RelativeBox(51, 50), new Point(50, 50)));
    }

    /**
     * Tests the square() method.
     */
    public function testSquare()
    {
        $box = new RelativeBox(100, 100);
        $this->assertEquals(10000, $box->square());

        $box = new RelativeBox(50, 50);
        $this->assertEquals(2500, $box->square());
    }

    /**
     * Tests the __toString() method.
     */
    public function testToString()
    {
        $box = new RelativeBox(100, 100);
        $this->assertEquals('100x100', (string) $box);

        $box = new RelativeBox(50, 50);
        $this->assertEquals('50x50', (string) $box);
    }

    /**
     * Tests the widen() method.
     */
    public function testWiden()
    {
        $box = new RelativeBox(100, 100);

        $this->assertEquals(new RelativeBox(200, 200), $box->widen(200));
    }

    /**
     * Tests the heighten() method.
     */
    public function testHeighten()
    {
        $box = new RelativeBox(100, 100);

        $this->assertEquals(new RelativeBox(200, 200), $box->heighten(200));
    }
}

<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ImagineSvg\Tests;

use Contao\ImagineSvg\UndefinedBox;

/**
 * Tests the UndefinedBox class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class UndefinedBoxTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $box = new UndefinedBox();

        $this->assertInstanceOf('Contao\ImagineSvg\UndefinedBox', $box);
        $this->assertInstanceOf('Contao\ImagineSvg\UndefinedBoxInterface', $box);
    }

    /**
     * Tests the getWidth() method.
     */
    public function testGetWidth()
    {
        $box = new UndefinedBox();

        $this->assertEquals(0, $box->getWidth());
    }

    /**
     * Tests the getHeight() method.
     */
    public function testGetHeight()
    {
        $box = new UndefinedBox();

        $this->assertEquals(0, $box->getHeight());
    }

    /**
     * Tests the scale() method.
     */
    public function testScale()
    {
        $box = new UndefinedBox();

        $this->assertEquals(new UndefinedBox(), $box->scale(2));
    }

    /**
     * Tests the increase() method.
     */
    public function testIncrease()
    {
        $box = new UndefinedBox();

        $this->assertEquals(new UndefinedBox(), $box->increase(100));
    }

    /**
     * Tests the contains() method.
     */
    public function testContains()
    {
        $box = new UndefinedBox();
        $point = $this->getMock('Imagine\Image\PointInterface');

        $this->assertFalse($box->contains(new UndefinedBox()));
        $this->assertFalse($box->contains(new UndefinedBox(), $point));
    }

    /**
     * Tests the square() method.
     */
    public function testSquare()
    {
        $box = new UndefinedBox();

        $this->assertEquals(0, $box->square());
    }

    /**
     * Tests the __toString() method.
     */
    public function testToString()
    {
        $box = new UndefinedBox();

        $this->assertEquals('undefined', (string) $box);
    }

    /**
     * Tests the widen() method.
     */
    public function testWiden()
    {
        $box = new UndefinedBox();

        $this->assertEquals(new UndefinedBox(), $box->widen(100));
    }

    /**
     * Tests the heighten() method.
     */
    public function testHeighten()
    {
        $box = new UndefinedBox();

        $this->assertEquals(new UndefinedBox(), $box->heighten(100));
    }
}

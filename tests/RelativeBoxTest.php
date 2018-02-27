<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ImagineSvg\Tests;

use Contao\ImagineSvg\RelativeBox;
use Imagine\Exception\InvalidArgumentException;
use Imagine\Image\Point;
use PHPUnit\Framework\TestCase;

class RelativeBoxTest extends TestCase
{
    public function testInstantiation()
    {
        $box = new RelativeBox(100, 100);

        $this->assertInstanceOf('Contao\ImagineSvg\RelativeBox', $box);
        $this->assertInstanceOf('Contao\ImagineSvg\RelativeBoxInterface', $box);

        $this->expectException(InvalidArgumentException::class);

        new RelativeBox(0, 0);
    }

    public function testGetWidth()
    {
        $box = new RelativeBox(100, 100);

        $this->assertSame(100, $box->getWidth());
    }

    public function testGetHeight()
    {
        $box = new RelativeBox(100, 100);

        $this->assertSame(100, $box->getHeight());
    }

    public function testScale()
    {
        $box = new RelativeBox(100, 100);

        $this->assertEquals(new RelativeBox(200, 200), $box->scale(2));
    }

    public function testIncrease()
    {
        $box = new RelativeBox(100, 100);

        $this->assertEquals(new RelativeBox(200, 200), $box->increase(100));
    }

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

    public function testSquare()
    {
        $box = new RelativeBox(100, 100);
        $this->assertSame(10000, $box->square());

        $box = new RelativeBox(50, 50);
        $this->assertSame(2500, $box->square());
    }

    public function testToString()
    {
        $box = new RelativeBox(100, 100);
        $this->assertSame('100x100', (string) $box);

        $box = new RelativeBox(50, 50);
        $this->assertSame('50x50', (string) $box);
    }

    public function testWiden()
    {
        $box = new RelativeBox(100, 100);

        $this->assertEquals(new RelativeBox(200, 200), $box->widen(200));
    }

    public function testHeighten()
    {
        $box = new RelativeBox(100, 100);

        $this->assertEquals(new RelativeBox(200, 200), $box->heighten(200));
    }
}

<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ImagineSvg\Tests;

use Contao\ImagineSvg\UndefinedBox;
use Imagine\Image\PointInterface;
use PHPUnit\Framework\TestCase;

class UndefinedBoxTest extends TestCase
{
    public function testInstantiation()
    {
        $box = new UndefinedBox();

        $this->assertInstanceOf('Contao\ImagineSvg\UndefinedBox', $box);
        $this->assertInstanceOf('Contao\ImagineSvg\UndefinedBoxInterface', $box);
    }

    public function testGetWidth()
    {
        $box = new UndefinedBox();

        $this->assertSame(0, $box->getWidth());
    }

    public function testGetHeight()
    {
        $box = new UndefinedBox();

        $this->assertSame(0, $box->getHeight());
    }

    public function testScale()
    {
        $box = new UndefinedBox();

        $this->assertEquals(new UndefinedBox(), $box->scale(2));
    }

    public function testIncrease()
    {
        $box = new UndefinedBox();

        $this->assertEquals(new UndefinedBox(), $box->increase(100));
    }

    public function testContains()
    {
        $box = new UndefinedBox();
        $point = $this->createMock(PointInterface::class);

        $this->assertFalse($box->contains(new UndefinedBox()));
        $this->assertFalse($box->contains(new UndefinedBox(), $point));
    }

    public function testSquare()
    {
        $box = new UndefinedBox();

        $this->assertSame(0, $box->square());
    }

    public function testToString()
    {
        $box = new UndefinedBox();

        $this->assertSame('undefined', (string) $box);
    }

    public function testWiden()
    {
        $box = new UndefinedBox();

        $this->assertEquals(new UndefinedBox(), $box->widen(100));
    }

    public function testHeighten()
    {
        $box = new UndefinedBox();

        $this->assertEquals(new UndefinedBox(), $box->heighten(100));
    }
}

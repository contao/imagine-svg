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

use Contao\ImagineSvg\SvgBox;
use Imagine\Exception\InvalidArgumentException;
use Imagine\Image\BoxInterface;
use Imagine\Image\Point;
use PHPUnit\Framework\TestCase;

class SvgBoxTest extends TestCase
{
    public function testInstantiation(): void
    {
        $box = new SvgBox(100, 100);

        $this->assertInstanceOf(BoxInterface::class, $box);

        $box = SvgBox::createTypeAbsolute(100, 100);

        $this->assertInstanceOf(BoxInterface::class, $box);

        $box = SvgBox::createTypeAspectRatio(100, 100);

        $this->assertInstanceOf(BoxInterface::class, $box);

        $box = SvgBox::createTypeNone();

        $this->assertInstanceOf(BoxInterface::class, $box);

        $this->expectException(InvalidArgumentException::class);

        new SvgBox(0, 0);
    }

    public function testGetWidth(): void
    {
        $this->assertSame(100, (new SvgBox(100, 100))->getWidth());
        $this->assertSame(100, SvgBox::createTypeAbsolute(100, 100)->getWidth());
        $this->assertSame(100, SvgBox::createTypeAspectRatio(100, 100)->getWidth());
        $this->assertSame(300, SvgBox::createTypeNone()->getWidth());
    }

    public function testGetHeight(): void
    {
        $this->assertSame(100, (new SvgBox(100, 100))->getHeight());
        $this->assertSame(100, SvgBox::createTypeAbsolute(100, 100)->getHeight());
        $this->assertSame(100, SvgBox::createTypeAspectRatio(100, 100)->getHeight());
        $this->assertSame(150, SvgBox::createTypeNone()->getHeight());
    }

    public function testScale(): void
    {
        $this->assertSameBox(
            new SvgBox(200, 200),
            (new SvgBox(100, 100))->scale(2)
        );
        $this->assertSameBox(
            SvgBox::createTypeAspectRatio(200, 200),
            SvgBox::createTypeAspectRatio(100, 100)->scale(2)
        );
        $this->assertSameBox(
            SvgBox::createTypeNone(),
            SvgBox::createTypeNone()->scale(2)
        );
    }

    public function testIncrease(): void
    {
        $this->assertSameBox(
            new SvgBox(200, 200),
            (new SvgBox(100, 100))->increase(100)
        );
        $this->assertSameBox(
            SvgBox::createTypeAspectRatio(200, 200),
            SvgBox::createTypeAspectRatio(100, 100)->increase(100)
        );
        $this->assertSameBox(
            SvgBox::createTypeNone(),
            SvgBox::createTypeNone()->increase(100)
        );
    }

    public function testContains(): void
    {
        $box = new SvgBox(100, 100);

        $this->assertTrue($box->contains(new SvgBox(100, 100)));
        $this->assertTrue($box->contains(new SvgBox(100, 100), new Point(0, 0)));
        $this->assertTrue($box->contains(new SvgBox(50, 50), new Point(50, 50)));
        $this->assertFalse($box->contains(new SvgBox(100, 100), new Point(1, 1)));
        $this->assertFalse($box->contains(new SvgBox(99, 100), new Point(1, 1)));
        $this->assertFalse($box->contains(new SvgBox(100, 99), new Point(1, 1)));
        $this->assertFalse($box->contains(new SvgBox(51, 50), new Point(50, 50)));
    }

    public function testSquare(): void
    {
        $this->assertSame(10000, (new SvgBox(100, 100))->square());
        $this->assertSame(2500, (new SvgBox(50, 50))->square());
        $this->assertSame(45000, SvgBox::createTypeNone()->square());
    }

    public function testToString(): void
    {
        $this->assertSame('100x100 px', (string) SvgBox::createTypeAbsolute(100, 100));
        $this->assertSame('50x50 px', (string) SvgBox::createTypeAbsolute(50, 50));
        $this->assertSame('100x100', (string) SvgBox::createTypeAspectRatio(100, 100));
        $this->assertSame('50x50', (string) SvgBox::createTypeAspectRatio(50, 50));
        $this->assertSame('undefined', (string) SvgBox::createTypeNone());
    }

    public function testWiden(): void
    {
        $this->assertSameBox(
            new SvgBox(200, 200),
            (new SvgBox(100, 100))->widen(200)
        );
        $this->assertSameBox(
            SvgBox::createTypeAspectRatio(200, 200),
            SvgBox::createTypeAspectRatio(100, 100)->widen(200)
        );
        $this->assertSameBox(
            SvgBox::createTypeNone(),
            SvgBox::createTypeNone()->widen(200)
        );
    }

    public function testHeighten(): void
    {
        $this->assertSameBox(
            new SvgBox(200, 200),
            (new SvgBox(100, 100))->heighten(200)
        );
        $this->assertSameBox(
            SvgBox::createTypeAspectRatio(200, 200),
            SvgBox::createTypeAspectRatio(100, 100)->heighten(200)
        );
        $this->assertSameBox(
            SvgBox::createTypeNone(),
            SvgBox::createTypeNone()->heighten(200)
        );
    }

    private function assertSameBox(SvgBox $expected, SvgBox $actual, string $message = ''): void
    {
        $this->assertSame($expected->getWidth(), $actual->getWidth(), $message);
        $this->assertSame($expected->getHeight(), $actual->getHeight(), $message);
        $this->assertSame($expected->getType(), $actual->getType(), $message);
    }
}

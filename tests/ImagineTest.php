<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ImagineSvg\Tests;

use Contao\ImagineSvg\Imagine;
use Contao\ImagineSvg\UndefinedBox;
use Imagine\Exception\InvalidArgumentException;
use Imagine\Exception\NotSupportedException;
use Imagine\Exception\RuntimeException;
use Imagine\Image\Box;
use Imagine\Image\Palette\Color\ColorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class ImagineTest extends TestCase
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
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->imagine = new Imagine();
        $this->rootDir = __DIR__.'/tmp';
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        parent::tearDown();

        if (file_exists($this->rootDir)) {
            (new Filesystem())->remove($this->rootDir);
        }
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\ImagineSvg\Imagine', $this->imagine);
    }

    public function testCreate()
    {
        $image = $this->imagine->create(new Box(100, 100));
        $svg = $image->getDomDocument()->documentElement;

        $this->assertInstanceOf('Contao\ImagineSvg\Image', $image);
        $this->assertInstanceOf('Imagine\Image\ImageInterface', $image);

        $this->assertSame('svg', $svg->tagName);
        $this->assertSame('1.1', $svg->getAttribute('version'));
        $this->assertSame('http://www.w3.org/2000/svg', $svg->getAttribute('xmlns'));
        $this->assertSame('100', $svg->getAttribute('width'));
        $this->assertSame('100', $svg->getAttribute('height'));
        $this->assertSame('0 0 100 100', $svg->getAttribute('viewBox'));

        $image = $this->imagine->create(new UndefinedBox());
        $svg = $image->getDomDocument()->documentElement;

        $this->assertFalse($svg->hasAttribute('width'));
        $this->assertFalse($svg->hasAttribute('height'));
    }

    public function testCreateWithColor()
    {
        $color = $this->createMock(ColorInterface::class);

        $this->expectException(InvalidArgumentException::class);

        $this->imagine->create(new Box(100, 100), $color);
    }

    public function testOpen()
    {
        $path = $this->rootDir;

        if (!is_dir(\dirname($path))) {
            mkdir(\dirname($path), 0777, true);
        }

        $xml = '<?xml version="1.0"?>'
            .'<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="100" height="100"></svg>'
        ;

        file_put_contents($path.'.svg', $xml);

        $image = $this->imagine->open($path.'.svg');

        $this->assertInstanceOf('Contao\ImagineSvg\Image', $image);
        $this->assertInstanceOf('Imagine\Image\ImageInterface', $image);

        $this->assertSame(100, $image->getSize()->getWidth());
        $this->assertSame(100, $image->getSize()->getHeight());

        unlink($path.'.svg');
        $image->save();

        $this->assertFileExists($path.'.svg');

        file_put_contents($path.'.svgz', gzencode($xml));

        $image = $this->imagine->open($path.'.svgz');

        $this->assertInstanceOf('Contao\ImagineSvg\Image', $image);
        $this->assertInstanceOf('Imagine\Image\ImageInterface', $image);

        $this->assertSame(100, $image->getSize()->getWidth());
        $this->assertSame(100, $image->getSize()->getHeight());

        unlink($path.'.svgz');
        $image->save();

        $this->assertFileExists($path.'.svgz');

        file_put_contents($path, $xml);

        $image = $this->imagine->open($path);

        $this->assertInstanceOf('Contao\ImagineSvg\Image', $image);
        $this->assertInstanceOf('Imagine\Image\ImageInterface', $image);

        $this->assertSame(100, $image->getSize()->getWidth());
        $this->assertSame(100, $image->getSize()->getHeight());

        unlink($path);
        $image->save();

        $this->assertFileExists($path);

        unlink($path);
        unlink($path.'.svg');
        unlink($path.'.svgz');
    }

    public function testLoad()
    {
        $xml = '<?xml version="1.0"?>'
            .'<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="100" height="100"></svg>'
        ;

        $image = $this->imagine->load($xml);

        $this->assertInstanceOf('Contao\ImagineSvg\Image', $image);
        $this->assertInstanceOf('Imagine\Image\ImageInterface', $image);

        $this->assertSame(100, $image->getSize()->getWidth());
        $this->assertSame(100, $image->getSize()->getHeight());

        $image = $this->imagine->load(gzencode($xml));

        $this->assertInstanceOf('Contao\ImagineSvg\Image', $image);
        $this->assertInstanceOf('Imagine\Image\ImageInterface', $image);

        $this->assertSame(100, $image->getSize()->getWidth());
        $this->assertSame(100, $image->getSize()->getHeight());

        $this->expectException(RuntimeException::class);

        $image->save();
    }

    public function testLoadInvalidSvg()
    {
        $this->expectException(RuntimeException::class);

        $this->imagine->load('<?xml version="1.0"?><notasvg/>');
    }

    public function testLoadInvalidXml()
    {
        $this->expectException(RuntimeException::class);

        $this->imagine->load('<?xml version="1.0"?><svg><invalid>');
    }

    public function testRead()
    {
        $xml = '<?xml version="1.0"?>'
            .'<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="100" height="100"></svg>'
        ;

        $stream = fopen('php://temp', 'rb+');
        fwrite($stream, $xml);
        rewind($stream);

        $image = $this->imagine->read($stream);

        $this->assertInstanceOf('Contao\ImagineSvg\Image', $image);
        $this->assertInstanceOf('Imagine\Image\ImageInterface', $image);

        $this->assertSame(100, $image->getSize()->getWidth());
        $this->assertSame(100, $image->getSize()->getHeight());

        $stream = fopen('php://temp', 'rb+');
        fwrite($stream, gzencode($xml));
        rewind($stream);

        $image = $this->imagine->read($stream);

        $this->assertInstanceOf('Contao\ImagineSvg\Image', $image);
        $this->assertInstanceOf('Imagine\Image\ImageInterface', $image);

        $this->assertSame(100, $image->getSize()->getWidth());
        $this->assertSame(100, $image->getSize()->getHeight());

        $this->expectException(RuntimeException::class);

        $image->save();
    }

    public function testReadInvalidResource()
    {
        $this->expectException(InvalidArgumentException::class);

        /* @noinspection PhpParamsInspection */
        $this->imagine->read('not a resource');
    }

    public function testFont()
    {
        $color = $this->createMock(ColorInterface::class);

        $this->expectException(RuntimeException::class);

        if (class_exists(NotSupportedException::class)) {
            $this->expectException(NotSupportedException::class);
        }

        $this->imagine->font($this->rootDir, 10, $color);
    }
}

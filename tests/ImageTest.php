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

use Contao\ImagineSvg\Effects;
use Contao\ImagineSvg\Image;
use Contao\ImagineSvg\Imagine;
use Contao\ImagineSvg\SvgBox;
use Imagine\Effects\EffectsInterface;
use Imagine\Exception\InvalidArgumentException;
use Imagine\Exception\NotSupportedException;
use Imagine\Exception\OutOfBoundsException;
use Imagine\Image\Box;
use Imagine\Image\Fill\FillInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\Metadata\MetadataBag;
use Imagine\Image\Palette\PaletteInterface;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;
use Imagine\Image\ProfileInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class ImageTest extends TestCase
{
    /**
     * @var string
     */
    private $rootDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootDir = __DIR__.'/tmp';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (file_exists($this->rootDir)) {
            (new Filesystem())->remove($this->rootDir);
        }
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf(Image::class, new Image(new \DOMDocument(), new MetadataBag()));
    }

    public function testGetDomDocument(): void
    {
        $document = new \DOMDocument();
        $image = new Image($document, new MetadataBag());

        $this->assertSame($document, $image->getDomDocument());
    }

    public function testCopy(): void
    {
        $document = new \DOMDocument();
        $image1 = new Image($document, new MetadataBag());
        $image2 = $image1->copy();

        $this->assertSame($document, $image1->getDomDocument());
        $this->assertNotSame($document, $image2->getDomDocument());
    }

    public function testClone(): void
    {
        $document = new \DOMDocument();
        $image1 = new Image($document, new MetadataBag());
        $image2 = clone $image1;

        $this->assertSame($document, $image1->getDomDocument());
        $this->assertNotSame($document, $image2->getDomDocument());
    }

    public function testCrop(): void
    {
        $imagine = new Imagine();
        $image = $imagine->create(new Box(100, 100));

        $this->assertSame(100, $image->getSize()->getWidth());
        $this->assertSame(100, $image->getSize()->getHeight());
        $this->assertSame('0 0 100 100', $image->getDomDocument()->documentElement->getAttribute('viewBox'));

        $this->assertSame($image, $image->crop(new Point(25, 25), new Box(50, 50)));

        $this->assertSame(50, $image->getSize()->getWidth());
        $this->assertSame(50, $image->getSize()->getHeight());
        $this->assertSame('0 0 50 50', $image->getDomDocument()->documentElement->getAttribute('viewBox'));

        $imageBefore = $image->get('svg');
        $this->assertSame($image, $image->crop(new Point(0, 0), new Box(50, 50)));
        $this->assertSame($imageBefore, $image->get('svg'));

        $image->getDomDocument()->documentElement->removeAttribute('viewBox');

        $imageBefore = $image->get('svg');
        $image->crop(new Point(0, 0), new Box(50, 50));
        $this->assertSame($imageBefore, $image->get('svg'));

        $image->getDomDocument()->documentElement->setAttribute('viewBox', '0 0 50 50');
        $image->getDomDocument()->documentElement->removeAttribute('width');
        $image->getDomDocument()->documentElement->removeAttribute('height');

        $this->assertSame(SvgBox::TYPE_ASPECT_RATIO, $image->getSize()->getType());
        $image->crop(new Point(0, 0), new Box(50, 50));
        $this->assertSame(SvgBox::TYPE_ABSOLUTE, $image->getSize()->getType());

        $image->crop(new Point(10, 20), SvgBox::createTypeNone());
        $this->assertSame(SvgBox::TYPE_NONE, $image->getSize()->getType());
        $this->assertSame('-10', $image->getDomDocument()->documentElement->firstChild->getAttribute('x'));
        $this->assertSame('-20', $image->getDomDocument()->documentElement->firstChild->getAttribute('y'));

        $image->crop(new Point(0, 0), SvgBox::createTypeAspectRatio(1600, 900));
        $this->assertSame(SvgBox::TYPE_ASPECT_RATIO, $image->getSize()->getType());
        $this->assertSame(round(16 / 9, 4), round($image->getSize()->getWidth() / $image->getSize()->getHeight(), 4));

        $image->crop(new Point(0, 0), SvgBox::createTypeAspectRatio($image->getSize()->getHeight(), $image->getSize()->getHeight()));
        $this->assertSame(SvgBox::TYPE_ASPECT_RATIO, $image->getSize()->getType());
        $this->assertSame($image->getSize()->getWidth(), $image->getSize()->getHeight());

        $image = $imagine->create(new Box(100, 100));
        $image->crop(new Point(0, 0), SvgBox::createTypeAspectRatio(50, 50));
        $this->assertSame(SvgBox::TYPE_ASPECT_RATIO, $image->getSize()->getType());
        $this->assertSame('100', $image->getDomDocument()->documentElement->firstChild->getAttribute('width'));

        $image->crop(new Point(0, 0), new Box((int) round($image->getSize()->getWidth() / 2), (int) round($image->getSize()->getHeight() / 2)));
        $this->assertSame(SvgBox::TYPE_ABSOLUTE, $image->getSize()->getType());
        $this->assertSame($image->getSize()->getWidth(), (int) round($image->getDomDocument()->documentElement->firstChild->getAttribute('width') / 2));

        $image = $imagine->create(new Box(50, 50));

        $this->expectException(OutOfBoundsException::class);

        $image->crop(new Point(60, 60), new Box(50, 50));
    }

    public function testResize(): void
    {
        $imagine = new Imagine();
        $image = $imagine->create(new Box(100, 100));

        $this->assertSame(100, $image->getSize()->getWidth());
        $this->assertSame(100, $image->getSize()->getHeight());

        $resized = $image->resize(new Box(50, 50));

        $this->assertSame(50, $image->getSize()->getWidth());
        $this->assertSame(50, $image->getSize()->getHeight());
        $this->assertSame($image, $resized, 'Should return itself');

        $image->getDomDocument()->documentElement->removeAttribute('viewBox');
        $image->getDomDocument()->documentElement->setAttribute('width', '50px');
        $image->getDomDocument()->documentElement->setAttribute('height', '3.125em');
        $image->resize(new Box(100, 100));

        $this->assertSame(100, $image->getSize()->getWidth());
        $this->assertSame(100, $image->getSize()->getHeight());

        $this->assertSame(
            '0 0 50 50',
            $image->getDomDocument()->documentElement->getAttribute('viewBox'),
            'Viewbox should get fixed'
        );

        $image->getDomDocument()->documentElement->removeAttribute('width');
        $image->getDomDocument()->documentElement->removeAttribute('height');
        $image->getDomDocument()->documentElement->setAttribute('viewBox', '0 0 100 100');
        $image->resize(new Box(100, 100));

        $this->assertSame(100, $image->getSize()->getWidth());
        $this->assertSame(100, $image->getSize()->getHeight());

        $this->assertSame(
            '100',
            $image->getDomDocument()->documentElement->getAttribute('width'),
            'Relative dimensions should get absolute'
        );

        $this->assertSame(
            '100',
            $image->getDomDocument()->documentElement->getAttribute('height'),
            'Relative dimensions should get absolute'
        );

        $image->getDomDocument()->documentElement->removeAttribute('viewBox');
        $this->assertSame($image, $image->resize(new Box(100, 100)));

        $this->assertSame(100, $image->getSize()->getWidth());
        $this->assertSame(100, $image->getSize()->getHeight());

        $this->assertSame(
            '',
            $image->getDomDocument()->documentElement->getAttribute('viewBox'),
            'Viewbox should not get modified if no resize is necessary'
        );

        $image->getDomDocument()->documentElement->removeAttribute('height');
        $this->assertSame($image, $image->resize(new Box(200, 200)));

        $this->assertSame(200, $image->getSize()->getWidth());
        $this->assertSame(200, $image->getSize()->getHeight());

        $this->assertSame(
            '',
            $image->getDomDocument()->documentElement->getAttribute('viewBox'),
            'Viewbox should not get modified if only one dimension is set'
        );

        $image->resize(SvgBox::createTypeAspectRatio(1, 1));

        $this->assertSame(SvgBox::TYPE_ASPECT_RATIO, $image->getSize()->getType());
        $this->assertSame($image->getSize()->getWidth(), $image->getSize()->getHeight());

        $image->resize(SvgBox::createTypeAspectRatio(16, 9));

        $this->assertSame(SvgBox::TYPE_ASPECT_RATIO, $image->getSize()->getType());

        $this->assertSame(
            round(16 / 9, 4),
            round((float) $image->getDomDocument()->documentElement->firstChild->getAttribute('width') / (float) $image->getDomDocument()->documentElement->firstChild->getAttribute('height'), 4),
            'Aspect ratio should match the specified size'
        );

        $this->assertSame(
            round(16 / 9, 4),
            round($image->getSize()->getWidth() / $image->getSize()->getHeight(), 4),
            'Aspect ratio should match the specified size'
        );

        $this->assertSame(
            '',
            $image->getDomDocument()->documentElement->getAttribute('width'),
            'Width attribute should not be set for aspect ratio resizes'
        );

        $this->assertSame(
            '',
            $image->getDomDocument()->documentElement->getAttribute('height'),
            'Height attribute should not be set for aspect ratio resizes'
        );

        $image->resize(SvgBox::createTypeNone());

        $this->assertSame(SvgBox::TYPE_NONE, $image->getSize()->getType());

        // Unsupported filters should not throw, same behavior as GD
        $image->resize(new Box(25, 25), ImageInterface::FILTER_LANCZOS);
    }

    public function testThumbnail(): void
    {
        $imagine = new Imagine();
        $image = $imagine->create(new Box(100, 100));

        $this->assertSame(100, $image->getSize()->getWidth());
        $this->assertSame(100, $image->getSize()->getHeight());

        $resized = $image->thumbnail(new Box(100, 50));

        $this->assertSame(50, $resized->getSize()->getWidth());
        $this->assertSame(50, $resized->getSize()->getHeight());
        $this->assertNotSame($image, $resized, 'Should not return itself');

        $image->getDomDocument()->documentElement->removeAttribute('viewBox');
        $image->getDomDocument()->documentElement->setAttribute('width', '50px');
        $image->getDomDocument()->documentElement->setAttribute('height', '3.125em');
        $resized = $image->thumbnail(new Box(100, 100), ImageInterface::THUMBNAIL_FLAG_UPSCALE | ImageInterface::THUMBNAIL_FLAG_NOCLONE);

        $this->assertSame(100, $image->getSize()->getWidth());
        $this->assertSame(100, $image->getSize()->getHeight());
        $this->assertSame($image, $resized, 'Should not return itself');

        $this->assertSame(
            '0 0 50 50',
            $image->getDomDocument()->documentElement->getAttribute('viewBox'),
            'Viewbox should get fixed'
        );

        $image->getDomDocument()->documentElement->removeAttribute('width');
        $image->getDomDocument()->documentElement->removeAttribute('height');
        $image->getDomDocument()->documentElement->setAttribute('viewBox', '0 0 100 100');
        $image->thumbnail(new Box(100, 100), ImageInterface::THUMBNAIL_FLAG_NOCLONE);

        $this->assertSame(100, $image->getSize()->getWidth());
        $this->assertSame(100, $image->getSize()->getHeight());

        $this->assertSame(
            '100',
            $image->getDomDocument()->documentElement->getAttribute('width'),
            'Relative dimensions should get absolute'
        );

        $this->assertSame(
            '100',
            $image->getDomDocument()->documentElement->getAttribute('height'),
            'Relative dimensions should get absolute'
        );

        $image->getDomDocument()->documentElement->removeAttribute('viewBox');
        $this->assertSame($image, $image->thumbnail(new Box(100, 100), ImageInterface::THUMBNAIL_FLAG_NOCLONE));

        $this->assertSame(100, $image->getSize()->getWidth());
        $this->assertSame(100, $image->getSize()->getHeight());

        $this->assertSame(
            '',
            $image->getDomDocument()->documentElement->getAttribute('viewBox'),
            'Viewbox should not get modified if no resize is necessary'
        );

        $image->getDomDocument()->documentElement->removeAttribute('height');
        $this->assertSame($image, $image->thumbnail(new Box(200, 200), ImageInterface::THUMBNAIL_FLAG_NOCLONE));

        $this->assertSame(200, $image->getSize()->getWidth());
        $this->assertSame(200, $image->getSize()->getHeight());

        $this->assertSame(
            '',
            $image->getDomDocument()->documentElement->getAttribute('viewBox'),
            'Viewbox should not get modified if only one dimension is set'
        );

        $image->thumbnail(SvgBox::createTypeAspectRatio(1, 1), ImageInterface::THUMBNAIL_FLAG_NOCLONE);

        $this->assertSame(SvgBox::TYPE_ASPECT_RATIO, $image->getSize()->getType());
        $this->assertSame($image->getSize()->getWidth(), $image->getSize()->getHeight());

        $image->thumbnail(SvgBox::createTypeAspectRatio(16, 9), ImageInterface::THUMBNAIL_FLAG_NOCLONE | ImageInterface::THUMBNAIL_OUTBOUND);

        $this->assertSame(SvgBox::TYPE_ASPECT_RATIO, $image->getSize()->getType());

        $this->assertSame(
            round(1 / 1, 4),
            round((float) $image->getDomDocument()->documentElement->firstChild->getAttribute('width') / (float) $image->getDomDocument()->documentElement->firstChild->getAttribute('height'), 4),
            'Aspect ratio should match the specified size'
        );

        $this->assertSame(
            round(16 / 9, 4),
            round($image->getSize()->getWidth() / $image->getSize()->getHeight(), 4),
            'Aspect ratio should match the specified size'
        );

        $this->assertSame(
            '',
            $image->getDomDocument()->documentElement->getAttribute('width'),
            'Width attribute should not be set for aspect ratio resizes'
        );

        $this->assertSame(
            '',
            $image->getDomDocument()->documentElement->getAttribute('height'),
            'Height attribute should not be set for aspect ratio resizes'
        );

        $image->thumbnail(SvgBox::createTypeNone(), ImageInterface::THUMBNAIL_FLAG_NOCLONE);

        $this->assertSame(SvgBox::TYPE_NONE, $image->getSize()->getType());

        // Unsupported filters should not throw, same behavior as GD
        $image->thumbnail(new Box(25, 25), ImageInterface::THUMBNAIL_FLAG_NOCLONE, ImageInterface::FILTER_POINT);
    }

    public function testSave(): void
    {
        $path = $this->rootDir;

        if (!is_dir(\dirname($path))) {
            mkdir(\dirname($path), 0777, true);
        }

        $imagine = new Imagine();
        $image = $imagine->create(new Box(100, 100));
        $this->assertSame($image, $image->save($path));

        $contents = file_get_contents($path);

        $document = new \DOMDocument();
        $document->loadXML($contents);

        $this->assertSame('svg', $document->documentElement->tagName);
        $this->assertSame('100', $document->documentElement->getAttribute('width'));
        $this->assertSame('100', $document->documentElement->getAttribute('height'));

        $image->save($path.'.svg');

        $contents = file_get_contents($path.'.svg');

        $document = new \DOMDocument();
        $document->loadXML($contents);

        $this->assertSame('svg', $document->documentElement->tagName);
        $this->assertSame('100', $document->documentElement->getAttribute('width'));
        $this->assertSame('100', $document->documentElement->getAttribute('height'));

        $image->save($path.'.foo', ['format' => 'svg']);

        $contents = file_get_contents($path.'.foo');

        $document = new \DOMDocument();
        $document->loadXML($contents);

        $this->assertSame('svg', $document->documentElement->tagName);
        $this->assertSame('100', $document->documentElement->getAttribute('width'));
        $this->assertSame('100', $document->documentElement->getAttribute('height'));

        $image->save($path.'.svgz');

        $contents = gzdecode(file_get_contents($path.'.svgz'));

        $document = new \DOMDocument();
        $document->loadXML($contents);

        $this->assertSame('svg', $document->documentElement->tagName);
        $this->assertSame('100', $document->documentElement->getAttribute('width'));
        $this->assertSame('100', $document->documentElement->getAttribute('height'));

        unlink($path);
        unlink($path.'.svg');
        unlink($path.'.foo');
        unlink($path.'.svgz');
    }

    public function testShow(): void
    {
        $imagine = new Imagine();
        $image = $imagine->create(new Box(100, 100));

        ob_start();
        $this->assertSame(
            $image,
            @$image->show('svg') // suppress headers already sent warning
        );
        $contents = ob_get_clean();

        $document = new \DOMDocument();
        $document->loadXML($contents);

        $this->assertSame('svg', $document->documentElement->tagName);
        $this->assertSame('100', $document->documentElement->getAttribute('width'));
        $this->assertSame('100', $document->documentElement->getAttribute('height'));

        ob_start();
        @$image->show('svgz'); // suppress headers already sent warning
        $contents = ob_get_clean();

        $document = new \DOMDocument();
        $document->loadXML(gzdecode($contents));

        $this->assertSame('svg', $document->documentElement->tagName);
        $this->assertSame('100', $document->documentElement->getAttribute('width'));
        $this->assertSame('100', $document->documentElement->getAttribute('height'));
    }

    public function testGet(): void
    {
        $imagine = new Imagine();
        $image = $imagine->create(new Box(100, 100));

        $document = new \DOMDocument();
        $document->loadXML($image->get('svg'));

        $this->assertSame('svg', $document->documentElement->tagName);
        $this->assertSame('100', $document->documentElement->getAttribute('width'));
        $this->assertSame('100', $document->documentElement->getAttribute('height'));

        $document = new \DOMDocument();
        $document->loadXML(gzdecode($image->get('svgz')));

        $this->assertSame('svg', $document->documentElement->tagName);
        $this->assertSame('100', $document->documentElement->getAttribute('width'));
        $this->assertSame('100', $document->documentElement->getAttribute('height'));

        $this->expectException(InvalidArgumentException::class);

        $image->get('jpg');
    }

    public function testToString(): void
    {
        $imagine = new Imagine();
        $image = $imagine->create(new Box(100, 100));

        $document = new \DOMDocument();
        $document->loadXML((string) $image);

        $this->assertSame('svg', $document->documentElement->tagName);
        $this->assertSame('100', $document->documentElement->getAttribute('width'));
        $this->assertSame('100', $document->documentElement->getAttribute('height'));
    }

    public function testGetSize(): void
    {
        $imagine = new Imagine();
        $image = $imagine->create(new Box(100, 100));
        $svg = $image->getDomDocument()->documentElement;

        $this->assertSame(SvgBox::TYPE_ABSOLUTE, $image->getSize()->getType());
        $this->assertSame(100, $image->getSize()->getWidth());
        $this->assertSame(100, $image->getSize()->getHeight());

        $svg->setAttribute('height', '50');

        $this->assertSame(SvgBox::TYPE_ABSOLUTE, $image->getSize()->getType());
        $this->assertSame(100, $image->getSize()->getWidth());
        $this->assertSame(50, $image->getSize()->getHeight());

        $svg->setAttribute('viewBox', '0 0 200 100');
        $svg->removeAttribute('height');

        $this->assertSame(SvgBox::TYPE_ABSOLUTE, $image->getSize()->getType());
        $this->assertSame(100, $image->getSize()->getWidth());
        $this->assertSame(50, $image->getSize()->getHeight());

        $svg->setAttribute('height', '200');
        $svg->removeAttribute('width');

        $this->assertSame(SvgBox::TYPE_ABSOLUTE, $image->getSize()->getType());
        $this->assertSame(400, $image->getSize()->getWidth());
        $this->assertSame(200, $image->getSize()->getHeight());

        $svg->removeAttribute('height');

        $this->assertSame(SvgBox::TYPE_ASPECT_RATIO, $image->getSize()->getType());
        $this->assertSame(2.0, round($image->getSize()->getWidth() / $image->getSize()->getHeight(), 4));

        $svg->setAttribute('viewBox', '0 0 1 0.5');

        $this->assertSame(SvgBox::TYPE_ASPECT_RATIO, $image->getSize()->getType());
        $this->assertSame(2.0, round($image->getSize()->getWidth() / $image->getSize()->getHeight(), 4));

        $svg->removeAttribute('viewBox');

        $this->assertSame(SvgBox::TYPE_NONE, $image->getSize()->getType());
        $this->assertSame(300, $image->getSize()->getWidth());
        $this->assertSame(150, $image->getSize()->getHeight());

        $svg->setAttribute('width', '100');

        $this->assertSame(SvgBox::TYPE_NONE, $image->getSize()->getType());
        $this->assertSame(300, $image->getSize()->getWidth());
        $this->assertSame(150, $image->getSize()->getHeight());

        $svg->removeAttribute('width');
        $svg->setAttribute('height', '100');

        $this->assertSame(SvgBox::TYPE_NONE, $image->getSize()->getType());
        $this->assertSame(300, $image->getSize()->getWidth());
        $this->assertSame(150, $image->getSize()->getHeight());
    }

    /**
     * @dataProvider getGetSizeAspectRatio
     */
    public function testGetSizeAspectRatio(string $viewBox, float $ratio): void
    {
        $imagine = new Imagine();
        $image = $imagine->create(SvgBox::createTypeNone());
        $svg = $image->getDomDocument()->documentElement;

        $svg->setAttribute('viewBox', $viewBox);

        $this->assertSame(SvgBox::TYPE_ASPECT_RATIO, $image->getSize()->getType());
        $this->assertSame(round($ratio, 3), round($image->getSize()->getWidth() / $image->getSize()->getHeight(), 3));
    }

    /**
     * @return array<array<string|float>>
     */
    public function getGetSizeAspectRatio(): array
    {
        return [
            ['0 0 1024 768', 4 / 3],
            ['0 0 1920 1080', 16 / 9],
            ['0 0 13456 7569', 16 / 9],
            ['0 0 0.001 0.000333', 1 / 0.333],
            ['0 0 1.777777777777777777777777777777777777777777777 1', 16 / 9],
            ['0 0 .1777777777777777777777777777777777777777777777 .1', 16 / 9],
            ['0 0 .0000001777777777777777777777777777777777777777 .0000001', 16 / 9],
            ['0 0 1777777777777777777777777777777777777777 1000000000000000000000000000000000000000', 16 / 9],
            ['0 0 1.77777777777777777e50 1e50', 16 / 9],
            ['0 0 1.77777777777777777e-50 1e-50', 16 / 9],
            ['0 0 13e5 10e4', 13.0 / 1],
        ];
    }

    /**
     * @dataProvider getGetSizePixelValues
     */
    public function testGetSizePixelValues(string $value, ?int $expected): void
    {
        $imagine = new Imagine();
        $image = $imagine->create(new Box(100, 100));

        $document = $image->getDomDocument();
        $document->documentElement->setAttribute('width', $value);
        $document->documentElement->setAttribute('height', $value);
        $document->documentElement->removeAttribute('viewBox');

        if (null === $expected) {
            $this->assertSame(SvgBox::TYPE_NONE, $image->getSize()->getType());
        } else {
            $this->assertSame($expected, $image->getSize()->getWidth());
            $this->assertSame($expected, $image->getSize()->getHeight());
        }
    }

    /**
     * @return array<string,array<string|float|int|null>>
     */
    public function getGetSizePixelValues(): array
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
            'unknown' => ['100vw', null],
            'unknown mmm' => ['1mmm', null],
            'invalid' => ['abc', null],
            'invalid number' => ['12.34.5', null],
        ];
    }

    public function testPaste(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->expectException(NotSupportedException::class);

        $image->paste(new Image(new \DOMDocument(), new MetadataBag()), new Point(0, 0));
    }

    public function testRotate(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->expectException(NotSupportedException::class);

        $image->rotate(90);
    }

    public function testFlipHorizontally(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->expectException(NotSupportedException::class);

        $image->flipHorizontally();
    }

    public function testFlipVertically(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->expectException(NotSupportedException::class);

        $image->flipVertically();
    }

    /**
     * @dataProvider getStrip
     */
    public function testStrip(string $svg, string $expected): void
    {
        $image = (new Imagine())
            ->load($svg)
            ->strip()
        ;

        $this->assertSame($expected, $image->get('svg'));
    }

    /**
     * @return array<string,array<string>>
     */
    public function getStrip(): array
    {
        return [
            'Comment' => [
                "<?xml version=\"1.0\"?>\n<svg xmlns=\"http://www.w3.org/2000/svg\"><!-- comment --></svg>\n",
                "<?xml version=\"1.0\"?>\n<svg xmlns=\"http://www.w3.org/2000/svg\"/>\n",
            ],
            'Multiple comments' => [
                "<?xml version=\"1.0\"?><!-- comment -->\n<!-- comment --><svg xmlns=\"http://www.w3.org/2000/svg\"><!-- comment --></svg><!-- comment -->\n",
                "<?xml version=\"1.0\"?>\n<svg xmlns=\"http://www.w3.org/2000/svg\"/>\n",
            ],
            'Complex comments' => [
                "<?xml version=\"1.0\"?>\n<svg xmlns=\"http://www.w3.org/2000/svg\"><!-- <!- -> -> \n</svg>\n  --></svg>\n",
                "<?xml version=\"1.0\"?>\n<svg xmlns=\"http://www.w3.org/2000/svg\"/>\n",
            ],
        ];
    }

    public function testDraw(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->expectException(NotSupportedException::class);

        $image->draw();
    }

    public function testEffects(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->assertInstanceOf(EffectsInterface::class, $image->effects());
        $this->assertInstanceOf(Effects::class, $image->effects());
    }

    public function testApplyMask(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->expectException(NotSupportedException::class);

        $image->applyMask(new Image(new \DOMDocument(), new MetadataBag()));
    }

    public function testFill(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->expectException(NotSupportedException::class);

        $image->fill($this->createMock(FillInterface::class));
    }

    public function testMask(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->expectException(NotSupportedException::class);

        $image->mask();
    }

    public function testHistogram(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->expectException(NotSupportedException::class);

        $image->histogram();
    }

    public function testGetColorAt(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->expectException(NotSupportedException::class);

        $image->getColorAt(new Point(0, 0));
    }

    public function testLayers(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->expectException(NotSupportedException::class);

        $image->layers();
    }

    public function testInterlace(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->expectException(NotSupportedException::class);

        $image->interlace('');
    }

    public function testPalette(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->assertInstanceOf(RGB::class, $image->palette());
    }

    public function testProfile(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());

        $this->expectException(NotSupportedException::class);

        $image->profile($this->createMock(ProfileInterface::class));
    }

    public function testUsePalette(): void
    {
        $image = new Image(new \DOMDocument(), new MetadataBag());
        $paletteRgb = $this->createMock(RGB::class);

        $this->assertSame($image, $image->usePalette($paletteRgb));
        $this->assertSame($paletteRgb, $image->palette());

        $this->expectException(NotSupportedException::class);

        $image->usePalette($this->createMock(PaletteInterface::class));
    }
}

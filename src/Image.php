<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ImagineSvg;

use Imagine\Draw\DrawerInterface;
use Imagine\Exception\InvalidArgumentException;
use Imagine\Exception\NotSupportedException;
use Imagine\Exception\OutOfBoundsException;
use Imagine\Exception\RuntimeException;
use Imagine\Image\AbstractImage;
use Imagine\Image\BoxInterface;
use Imagine\Image\Fill\FillInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\LayersInterface;
use Imagine\Image\Metadata\MetadataBag;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\Palette\PaletteInterface;
use Imagine\Image\Palette\RGB;
use Imagine\Image\PointInterface;
use Imagine\Image\ProfileInterface;

class Image extends AbstractImage
{
    /**
     * @var \DOMDocument
     */
    private $document;

    /**
     * @var PaletteInterface
     */
    private $palette;

    public function __construct(\DOMDocument $document, MetadataBag $metadata)
    {
        $this->document = $document;
        $this->metadata = $metadata;
        $this->palette = new RGB();
    }

    public function __toString(): string
    {
        return $this->get('svg');
    }

    /**
     * Assures the DOM document instance will be cloned too.
     */
    public function __clone()
    {
        parent::__clone();
        $this->document = clone $this->document;
    }

    /**
     * Returns the DOM document.
     */
    public function getDomDocument(): \DOMDocument
    {
        return $this->document;
    }

    public function copy(): self
    {
        return new self(clone $this->document, clone $this->metadata);
    }

    public function crop(PointInterface $start, BoxInterface $size): self
    {
        $currentSize = $this->getSize();

        if (!$start->in($currentSize)) {
            throw new OutOfBoundsException('Crop coordinates must start at minimum 0, 0 position from top left corner, crop height and width '.'must be positive integers and must not exceed the current image borders');
        }

        if (
            SvgBox::TYPE_ASPECT_RATIO !== $currentSize->getType()
            && 0 === $start->getX()
            && 0 === $start->getY()
            && $size->getWidth() === $currentSize->getWidth()
            && $size->getHeight() === $currentSize->getHeight()
        ) {
            return $this; // skip crop if the size didn't change
        }

        $this->fixViewBox();

        $svg = $this->document->documentElement;
        $svg->setAttribute('x', (string) (-$start->getX()));
        $svg->setAttribute('y', (string) (-$start->getY()));

        $svgWrap = $this->document->createElementNS('http://www.w3.org/2000/svg', 'svg');
        $svgWrap->setAttribute('version', '1.1');
        $svgWrap->setAttribute('width', (string) $size->getWidth());
        $svgWrap->setAttribute('height', (string) $size->getHeight());
        $svgWrap->appendChild($svg);

        $this->document->appendChild($svgWrap);
        $this->fixViewBox();

        return $this;
    }

    public function paste(ImageInterface $image, PointInterface $start, $alpha = 100): self
    {
        throw new NotSupportedException('This method is not implemented');
    }

    public function resize(BoxInterface $size, $filter = ImageInterface::FILTER_UNDEFINED): self
    {
        if (ImageInterface::FILTER_UNDEFINED !== $filter) {
            throw new InvalidArgumentException('Unsupported filter type, SVG only supports ImageInterface::FILTER_UNDEFINED filter');
        }

        $currentSize = $this->getSize();

        if (
            SvgBox::TYPE_ASPECT_RATIO !== $currentSize->getType()
            && $size->getWidth() === $currentSize->getWidth()
            && $size->getHeight() === $currentSize->getHeight()
        ) {
            return $this; // skip resize if the size didn't change
        }

        $this->fixViewBox();

        $this->document->documentElement->setAttribute('width', (string) $size->getWidth());
        $this->document->documentElement->setAttribute('height', (string) $size->getHeight());

        return $this;
    }

    public function rotate($angle, ColorInterface $background = null): self
    {
        throw new NotSupportedException('This method is not implemented');
    }

    public function save($path = null, array $options = []): self
    {
        if (null === $path && isset($this->metadata['filepath'])) {
            $path = $this->metadata['filepath'];
        }

        if (null === $path) {
            throw new RuntimeException('You can omit save path only if image has been open from a file');
        }

        if (isset($options['format'])) {
            $format = $options['format'];
        } elseif ($extension = pathinfo($path, \PATHINFO_EXTENSION)) {
            $format = $extension;
        } else {
            $originalPath = $this->metadata['filepath'] ?? null;
            $format = isset($originalPath) ? pathinfo($originalPath, \PATHINFO_EXTENSION) : null;
        }

        if (!$format) {
            $format = 'svg';
        }

        $image = $this->get($format, $options);

        if (!file_put_contents($path, $image)) {
            throw new RuntimeException('Unable to save image to '.$path);
        }

        return $this;
    }

    public function show($format, array $options = []): self
    {
        $image = $this->get($format, $options);

        if ('svgz' === strtolower($format)) {
            header('Content-Encoding: gzip');
        } else {
            header('Content-Type: image/svg+xml');
        }

        echo $image;

        return $this;
    }

    public function get($format, array $options = []): string
    {
        $format = strtolower($format);

        $supported = ['svg', 'svgz'];

        if (!\in_array($format, $supported, true)) {
            throw new InvalidArgumentException(sprintf('Saving image in "%s" format is not supported, please use one of the following extensions: "%s"', $format, implode('", "', $supported)));
        }

        $xml = $this->document->saveXML();

        if ('svgz' === $format) {
            return gzencode($xml);
        }

        return $xml;
    }

    public function flipHorizontally(): self
    {
        throw new NotSupportedException('This method is not implemented');
    }

    public function flipVertically(): self
    {
        throw new NotSupportedException('This method is not implemented');
    }

    public function strip(): self
    {
        $xPath = new \DOMXPath($this->document);

        foreach ($xPath->query('//comment()') as $comment) {
            $comment->parentNode->removeChild($comment);
        }

        return $this;
    }

    public function draw(): DrawerInterface
    {
        throw new NotSupportedException('This method is not implemented');
    }

    public function effects(): Effects
    {
        return new Effects($this->document);
    }

    public function getSize(): SvgBox
    {
        $svg = $this->document->documentElement;

        $width = $this->getPixelValue($svg->getAttribute('width'));
        $height = $this->getPixelValue($svg->getAttribute('height'));

        // Absolute dimensions
        if ($width && $height) {
            return SvgBox::createTypeAbsolute($width, $height);
        }

        $viewBox = preg_split('/[\s,]+/', $svg->getAttribute('viewBox') ?: '');
        $viewBoxWidth = (float) ($viewBox[2] ?? 0);
        $viewBoxHeight = (float) ($viewBox[3] ?? 0);

        // Missing width/height and viewBox
        if ($viewBoxWidth <= 0 || $viewBoxHeight <= 0) {
            return SvgBox::createTypeNone();
        }

        // Fixed width and viewBox
        if ($width) {
            return SvgBox::createTypeAbsolute($width, (int) round($width / $viewBoxWidth * $viewBoxHeight));
        }

        // Fixed height and viewBox
        if ($height) {
            return SvgBox::createTypeAbsolute((int) round($height / $viewBoxHeight * $viewBoxWidth), $height);
        }

        // Normalize floating point values
        if (
            $viewBoxWidth < 1000
            && (round($viewBoxWidth) !== $viewBoxWidth || round($viewBoxHeight) !== $viewBoxHeight)
        ) {
            $viewBoxHeight = 1000 / $viewBoxWidth * $viewBoxHeight;
            $viewBoxWidth = 1000;
        }

        // Missing width/height, returning relative dimensions from viewBox
        return SvgBox::createTypeAspectRatio((int) round($viewBoxWidth), (int) round($viewBoxHeight));
    }

    public function applyMask(ImageInterface $mask): self
    {
        throw new NotSupportedException('This method is not implemented');
    }

    public function fill(FillInterface $fill): self
    {
        throw new NotSupportedException('This method is not implemented');
    }

    public function mask(): self
    {
        throw new NotSupportedException('This method is not implemented');
    }

    public function histogram(): array
    {
        throw new NotSupportedException('This method is not implemented');
    }

    public function getColorAt(PointInterface $point): ColorInterface
    {
        throw new NotSupportedException('This method is not implemented');
    }

    public function layers(): LayersInterface
    {
        throw new NotSupportedException('This method is not implemented');
    }

    public function interlace($scheme): self
    {
        throw new NotSupportedException('This method is not implemented');
    }

    public function palette(): PaletteInterface
    {
        return $this->palette;
    }

    public function profile(ProfileInterface $profile): self
    {
        throw new NotSupportedException('This method is not implemented');
    }

    public function usePalette(PaletteInterface $palette): self
    {
        if (!$palette instanceof RGB) {
            throw new NotSupportedException('SVG driver only supports RGB palette');
        }

        $this->palette = $palette;

        return $this;
    }

    /**
     * Sets the viewBox attribute from the original dimensions if it's not set.
     */
    private function fixViewBox(): void
    {
        $svg = $this->document->documentElement;

        if ($svg->hasAttribute('viewBox')) {
            return;
        }

        $width = $this->getPixelValue($svg->getAttribute('width'));
        $height = $this->getPixelValue($svg->getAttribute('height'));

        if ($width && $height) {
            $svg->setAttribute('viewBox', '0 0 '.$width.' '.$height);
        }
    }

    /**
     * Converts sizes like 2em, 10cm or 12pt to pixels.
     */
    private function getPixelValue(string $size): int
    {
        $map = [
            'px' => 1,
            'em' => 16,
            'ex' => 16 / 2,
            'pt' => 16 / 12,
            'pc' => 16,
            'in' => 16 * 6,
            'cm' => 16 / (2.54 / 6),
            'mm' => 16 / (25.4 / 6),
        ];

        $size = trim($size);

        $value = substr($size, 0, -2);
        $unit = substr($size, -2);

        if (is_numeric($value) && isset($map[$unit])) {
            $pixelValue = ((float) $value) * $map[$unit];
        } elseif (is_numeric($size)) {
            $pixelValue = (float) $size;
        } else {
            $pixelValue = 0;
        }

        return (int) round($pixelValue);
    }
}

<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ImagineSvg;

use Imagine\Image\AbstractImage;
use Imagine\Image\ImageInterface;
use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\Metadata\MetadataBag;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\Fill\FillInterface;
use Imagine\Image\PointInterface;
use Imagine\Image\Palette\PaletteInterface;
use Imagine\Image\ProfileInterface;
use Imagine\Exception\InvalidArgumentException;
use Imagine\Exception\OutOfBoundsException;
use Imagine\Exception\RuntimeException;

/**
 * Image implementation for SVG images.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class Image extends AbstractImage
{
    /**
     * @var \DOMDocument
     */
    private $document;

    /**
     * Constructor.
     *
     * @param \DOMDocument $document
     * @param MetadataBag  $metadata
     */
    public function __construct(\DOMDocument $document, MetadataBag $metadata)
    {
        $this->metadata = $metadata;
        $this->document = $document;
    }

    /**
     * Returns the DOM document.
     *
     * @return \DOMDocument
     */
    public function getDomDocument()
    {
        return $this->document;
    }

    /**
     * {@inheritdoc}
     */
    public function copy()
    {
        return new self(clone $this->document, clone $this->metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function crop(PointInterface $start, BoxInterface $size)
    {
        if (!$start->in($this->getSize())) {
            throw new OutOfBoundsException(
                'Crop coordinates must start at minimum 0, 0 position from top left corner, crop height and width '
                    .'must be positive integers and must not exceed the current image borders'
            );
        }

        $this->fixViewBox();

        $svg = $this->document->documentElement;
        $svg->setAttribute('x', -$start->getX());
        $svg->setAttribute('y', -$start->getY());

        $svgWrap = $this->document->createElementNS('http://www.w3.org/2000/svg', 'svg');
        $svgWrap->setAttribute('version', '1.1');
        $svgWrap->setAttribute('width', $size->getWidth());
        $svgWrap->setAttribute('height', $size->getHeight());
        $svgWrap->appendChild($svg);

        $this->document->appendChild($svgWrap);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function paste(ImageInterface $image, PointInterface $start)
    {
        throw new RuntimeException('This method is not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function resize(BoxInterface $size, $filter = ImageInterface::FILTER_UNDEFINED)
    {
        if (ImageInterface::FILTER_UNDEFINED !== $filter) {
            throw new InvalidArgumentException(
                'Unsupported filter type, SVG only supports ImageInterface::FILTER_UNDEFINED filter'
            );
        }

        $currentSize = $this->getSize();

        if (
            $size->getWidth() === $currentSize->getWidth() &&
            $size->getHeight() === $currentSize->getHeight() &&
            !($currentSize instanceof RelativeBox)
        ) {
            return $this; // skip resize if the size didn't change
        }

        $this->fixViewBox();

        $this->document->documentElement->setAttribute('width', $size->getWidth());
        $this->document->documentElement->setAttribute('height', $size->getHeight());

        return $this;
    }

    /**
     * Sets the viewBox attribute from the original dimensions if it's not set.
     */
    private function fixViewBox()
    {
        $svg = $this->document->documentElement;

        if ($svg->hasAttribute('viewBox')) {
            return;
        }

        $width = floatval($svg->getAttribute('width'));
        $height = floatval($svg->getAttribute('height'));

        if ($width && $height) {
            $svg->setAttribute('viewBox', '0 0 '.$width.' '.$height);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rotate($angle, ColorInterface $background = null)
    {
        throw new RuntimeException('This method is not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function save($path = null, array $options = [])
    {
        if (null === $path && isset($this->metadata['filepath'])) {
            $path = $this->metadata['filepath'];
        }

        if (null === $path) {
            throw new RuntimeException('You can omit save path only if image has been open from a file');
        }

        if (isset($options['format'])) {
            $format = $options['format'];
        } elseif ('' !== $extension = pathinfo($path, \PATHINFO_EXTENSION)) {
            $format = $extension;
        } else {
            $originalPath = isset($this->metadata['filepath']) ? $this->metadata['filepath'] : null;
            $format = pathinfo($originalPath, \PATHINFO_EXTENSION);
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

    /**
     * {@inheritdoc}
     */
    public function show($format, array $options = [])
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

    /**
     * {@inheritdoc}
     */
    public function get($format, array $options = [])
    {
        $format = strtolower($format);

        $supported = ['svg', 'svgz'];

        if (!in_array($format, $supported, true)) {
            throw new InvalidArgumentException(sprintf(
                'Saving image in "%s" format is not supported, please use one of the following extensions: "%s"',
                $format,
                implode('", "', $supported))
            );
        }

        $xml = $this->document->saveXML();

        if ('svgz' === $format) {
            return gzencode($xml);
        }

        return $xml;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->get('svg');
    }

    /**
     * {@inheritdoc}
     */
    public function flipHorizontally()
    {
        throw new RuntimeException('This method is not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function flipVertically()
    {
        throw new RuntimeException('This method is not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function strip()
    {
        throw new RuntimeException('This method is not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function draw()
    {
        throw new RuntimeException('This method is not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function effects()
    {
        throw new RuntimeException('This method is not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        $svg = $this->document->documentElement;

        $width = $this->getPixelValue($svg->getAttribute('width'));
        $height = $this->getPixelValue($svg->getAttribute('height'));

        // Absolute dimensions
        if ($width && $height) {
            return new Box($width, $height);
        }

        $viewBox = preg_split('/[\s,]+/', $svg->getAttribute('viewBox') ?: '');
        $viewBoxWidth = isset($viewBox[2]) ? floatval($viewBox[2]) : 0;
        $viewBoxHeight = isset($viewBox[3]) ? floatval($viewBox[3]) : 0;

        // Missing width/height and viewBox
        if ($viewBoxWidth <= 0 || $viewBoxHeight <= 0) {
            return new UndefinedBox();
        }

        // Fixed width and viewBox
        if ($width) {
            return new Box($width, $width / $viewBoxWidth * $viewBoxHeight);
        }

        // Fixed height and viewBox
        if ($height) {
            return new Box($height / $viewBoxHeight * $viewBoxWidth, $height);
        }

        // Normalize floating point values
        if ($viewBoxWidth < 1000
            && (round($viewBoxWidth) !== $viewBoxWidth || round($viewBoxHeight) !== $viewBoxHeight)
        ) {
            $viewBoxHeight = 1000 / $viewBoxWidth * $viewBoxHeight;
            $viewBoxWidth = 1000;
        }

        // Missing width/height, returning relative dimensions from viewBox
        return new RelativeBox(round($viewBoxWidth), round($viewBoxHeight));
    }

    /**
     * Converts sizes like 2em, 10cm or 12pt to pixels.
     *
     * @param string $size
     *
     * @return int
     */
    private function getPixelValue($size)
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

        if (isset($map[$unit]) && is_numeric($value)) {
            $size = $value * $map[$unit];
        }

        if (is_numeric($size)) {
            return (int) round($size);
        }

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function applyMask(ImageInterface $mask)
    {
        throw new RuntimeException('This method is not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function fill(FillInterface $fill)
    {
        throw new RuntimeException('This method is not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function mask()
    {
        throw new RuntimeException('This method is not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function histogram()
    {
        throw new RuntimeException('This method is not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getColorAt(PointInterface $point)
    {
        throw new RuntimeException('This method is not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function layers()
    {
        throw new RuntimeException('This method is not implemented');
    }

    /**
     * {@inheritdoc}
     **/
    public function interlace($scheme)
    {
        throw new RuntimeException('This method is not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function palette()
    {
        throw new RuntimeException('This method is not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function profile(ProfileInterface $profile)
    {
        throw new RuntimeException('This method is not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function usePalette(PaletteInterface $palette)
    {
        throw new RuntimeException('This method is not implemented');
    }

    /**
     * Assures the DOM document instance will be cloned too.
     */
    public function __clone()
    {
        $this->document = clone $this->document;
        $this->metadata = clone $this->metadata;
    }
}

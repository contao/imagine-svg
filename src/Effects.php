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

use Imagine\Effects\EffectsInterface;
use Imagine\Exception\InvalidArgumentException;
use Imagine\Exception\NotSupportedException;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\Palette\Color\RGB;
use Imagine\Utils\Matrix;

class Effects implements EffectsInterface
{
    private const SVG_FILTER_ID_PREFIX = 'svgImagineFilterV1_';

    /**
     * @var \DOMDocument
     */
    private $document;

    public function __construct(\DOMDocument $document)
    {
        $this->document = $document;
    }

    public function gamma($correction): self
    {
        $gamma = (float) $correction;

        if ($gamma <= 0) {
            throw new InvalidArgumentException(sprintf('Invalid gamma correction value %s, must be a positive float or integer', var_export($correction, true)));
        }

        $funcAttributes = [
            'type' => 'gamma',
            'exponent' => $this->numberToString(1 / $gamma),
        ];

        $this->addFilterElement('feComponentTransfer', [
            ['feFuncR', $funcAttributes],
            ['feFuncG', $funcAttributes],
            ['feFuncB', $funcAttributes],
        ]);

        return $this;
    }

    public function negative(): self
    {
        $this->addFilterElement('feColorMatrix', [
            'type' => 'matrix',
            'values' => implode(' ', [
                '-1 0 0 0 1',
                '0 -1 0 0 1',
                '0 0 -1 0 1',
                '0 0  0 1 0',
            ]),
        ]);

        return $this;
    }

    public function grayscale(): self
    {
        $this->addFilterElement('feColorMatrix', [
            'type' => 'saturate',
            'values' => '0',
        ]);

        return $this;
    }

    public function colorize(ColorInterface $color): self
    {
        if (!$color instanceof RGB) {
            throw new NotSupportedException('Colorize with non-rgb color is not supported');
        }

        $this->addFilterElement('feColorMatrix', [
            'type' => 'matrix',
            'values' => implode(' ', [
                '1 0 0 0 '.$this->numberToString($color->getRed() / 255),
                '0 1 0 0 '.$this->numberToString($color->getGreen() / 255),
                '0 0 1 0 '.$this->numberToString($color->getBlue() / 255),
                '0 0 0 1 0',
            ]),
        ]);

        return $this;
    }

    public function sharpen(): self
    {
        $this->addFilterElement('feConvolveMatrix', [
            'kernelUnitLength' => '1',
            'kernelMatrix' => implode(' ', [
                '-0.02 -0.12 -0.02',
                '-0.12  1.56 -0.12',
                '-0.02 -0.12 -0.02',
            ]),
        ]);

        return $this;
    }

    public function blur($sigma = 1): self
    {
        $deviation = (float) $sigma;

        if ($deviation <= 0) {
            throw new InvalidArgumentException(sprintf('Invalid sigma %s, must be a positive float or integer', var_export($sigma, true)));
        }

        $this->addFilterElement('feGaussianBlur', [
            'stdDeviation' => $this->numberToString($deviation),
        ]);

        return $this;
    }

    public function brightness($brightness): self
    {
        $intercept = ((int) $brightness) / 100;

        if ($intercept < -1 || $intercept > 1) {
            throw new InvalidArgumentException(sprintf('Invalid brightness value %s, must be between -100 and 100', var_export($brightness, true)));
        }

        $funcAttributes = [
            'type' => 'linear',
            'intercept' => $this->numberToString($intercept),
        ];

        $this->addFilterElement('feComponentTransfer', [
            ['feFuncR', $funcAttributes],
            ['feFuncG', $funcAttributes],
            ['feFuncB', $funcAttributes],
        ]);

        return $this;
    }

    public function convolve(Matrix $matrix): self
    {
        $attributes = [
            'kernelMatrix' => implode(' ', array_map(
                [$this, 'numberToString'],
                $matrix->getValueList()
            )),
            'kernelUnitLength' => '1',
        ];

        if (!\in_array((float) array_sum($matrix->getValueList()), [0.0, 1.0], true)) {
            $attributes['divisor'] = '1';
        }

        if (3 !== $matrix->getWidth() || 3 !== $matrix->getHeight()) {
            $attributes['order'] = $matrix->getWidth().' '.$matrix->getHeight();
        }

        $this->addFilterElement('feConvolveMatrix', $attributes);

        return $this;
    }

    /**
     * Create and add a new filter element.
     *
     * @param array<string|int,string|array<string|array<string>>> $attributes
     */
    private function addFilterElement(string $name, array $attributes): void
    {
        $attributes['color-interpolation-filters'] = 'sRGB';

        $this->getSvgFilter()->appendChild($this->createElement($name, $attributes));
    }

    /**
     * Get the main filter element or create it if none is present.
     */
    private function getSvgFilter(): \DOMElement
    {
        $svg = $this->document->documentElement;
        $filter = null;

        if (
            1 === $svg->childNodes->length
            && $svg->firstChild instanceof \DOMElement
            && 'g' === $svg->firstChild->nodeName
            && preg_match(
                '/^url\(#('.self::SVG_FILTER_ID_PREFIX.'[0-9a-f]{16})\)$/',
                $svg->firstChild->getAttribute('filter'),
                $matches
            )
        ) {
            $id = $matches[1];
        } else {
            $group = $this->wrapSvg();
            $id = self::SVG_FILTER_ID_PREFIX.bin2hex(substr(hash('sha256', $this->document->saveXML()), 0, 8));
            $group->setAttribute('filter', 'url(#'.$id.')');
        }

        /** @var \DOMElement $element */
        foreach ($this->document->getElementsByTagNameNS($svg->namespaceURI, 'filter') as $element) {
            if ($element->getAttribute('id') === $id) {
                return $element;
            }
        }

        $filter = $this->document->createElementNS($svg->namespaceURI, 'filter');
        $filter->setAttribute('id', $id);
        $svg->firstChild->insertBefore($filter, $svg->firstChild->firstChild);

        return $filter;
    }

    /**
     * Add a group element that wraps all contents.
     */
    private function wrapSvg(): \DOMElement
    {
        $svg = $this->document->documentElement;
        $group = $this->document->createElementNS($svg->namespaceURI, 'g');

        while ($svg->firstChild) {
            $group->appendChild($svg->firstChild);
        }

        $svg->appendChild($group);

        return $group;
    }

    /**
     * Create element with the specified attributes.
     *
     * @param array<string|int,string|array<string|array<string>>> $attributes
     */
    private function createElement(string $name, array $attributes): \DOMElement
    {
        $filter = $this->document->createElementNS($this->document->documentElement->namespaceURI, $name);

        foreach ($attributes as $key => $value) {
            if (\is_string($key)) {
                $filter->setAttribute($key, $value);
            } else {
                $filter->appendChild($this->createElement($value[0], $value[1]));
            }
        }

        return $filter;
    }

    /**
     * @param int|float $number
     */
    private function numberToString($number): string
    {
        return rtrim(rtrim(sprintf('%.7F', $number), '0'), '.');
    }
}

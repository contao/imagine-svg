<?php

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

class Effects implements EffectsInterface
{
    const SVG_FILTER_ID_PREFIX = 'svgImagineFilterV1_';

    /**
     * @var \DOMDocument
     */
    private $document;

    /**
     * @param \DOMDocument $document
     */
    public function __construct(\DOMDocument $document)
    {
        $this->document = $document;
    }

    /**
     * {@inheritdoc}
     */
    public function gamma($correction)
    {
        $gamma = (float) $correction;

        if ($gamma <= 0) {
            throw new InvalidArgumentException(sprintf(
                'Invalid gamma correction value %s, must be a positive float or integer',
                var_export($correction, true)
            ));
        }

        $exponent = 1 / $gamma;

        $this->addFilterElement('feComponentTransfer', [
            ['feFuncR', [
                'type' => 'gamma',
                'exponent' => $exponent,
            ]],
            ['feFuncG', [
                'type' => 'gamma',
                'exponent' => $exponent,
            ]],
            ['feFuncB', [
                'type' => 'gamma',
                'exponent' => $exponent,
            ]],
        ]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function negative()
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

    /**
     * {@inheritdoc}
     */
    public function grayscale()
    {
        $this->addFilterElement('feColorMatrix', [
            'type' => 'saturate',
            'values' => '0',
        ]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function colorize(ColorInterface $color)
    {
        if (!$color instanceof RGB) {
            throw new NotSupportedException('Colorize with non-rgb color is not supported');
        }

        $this->addFilterElement('feColorMatrix', [
            'type' => 'matrix',
            'values' => implode(' ', [
                '1 0 0 0 '.json_encode($color->getRed() / 255),
                '0 1 0 0 '.json_encode($color->getGreen() / 255),
                '0 0 1 0 '.json_encode($color->getBlue() / 255),
                '0 0 0 1 0',
            ]),
        ]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function sharpen()
    {
        $this->addFilterElement('feConvolveMatrix', [
            'kernelMatrix' => implode(' ', [
                '-0.02 -0.12 -0.02',
                '-0.12  1.56 -0.12',
                '-0.02 -0.12 -0.02',
            ]),
        ]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function blur($sigma = 1)
    {
        $deviation = (float) $sigma;

        if ($deviation <= 0) {
            throw new InvalidArgumentException(sprintf(
                'Invalid sigma %s, must be a positive float or integer',
                var_export($sigma, true)
            ));
        }

        $this->addFilterElement('feGaussianBlur', [
            'stdDeviation' => json_encode($deviation),
        ]);

        return $this;
    }

    /**
     * Create and add a new filter element.
     *
     * @param string $name
     * @param array  $attributes
     */
    private function addFilterElement($name, array $attributes)
    {
        $attributes['color-interpolation-filters'] = 'sRGB';

        $this->getSvgFilter()->appendChild($this->createElement($name, $attributes));
    }

    /**
     * Get the main filter element or create it if none is present.
     *
     * @return \DOMElement
     */
    private function getSvgFilter()
    {
        $svg = $this->document->documentElement;
        $filter = null;

        if (
            1 === $svg->childNodes->length
            && 'g' === $svg->firstChild->nodeName
            && preg_match(
                '/^url\(#('.self::SVG_FILTER_ID_PREFIX.'[0-9a-f]{16})\)$/',
                (string) $svg->firstChild->getAttribute('filter'),
                $matches
            )
        ) {
            $id = $matches[1];
        } else {
            $this->wrapSvg();
            $id = self::SVG_FILTER_ID_PREFIX.bin2hex(substr(hash('sha256', $this->document->saveXML()), 0, 8));
            $svg->firstChild->setAttribute('filter', 'url(#'.$id.')');
        }

        /** @var \DOMElement $element */
        foreach ($this->document->getElementsByTagName('filter') as $element) {
            if ($element->getAttribute('id') === $id) {
                return $element;
            }
        }

        $filter = $this->document->createElement('filter');
        $filter->setAttribute('id', $id);
        $svg->firstChild->insertBefore($filter, $svg->firstChild->firstChild);

        return $filter;
    }

    /**
     * Add a group element that wraps all contents.
     */
    private function wrapSvg()
    {
        $svg = $this->document->documentElement;
        $group = $this->document->createElement('g');

        while ($svg->firstChild) {
            $group->appendChild($svg->firstChild);
        }

        $svg->appendChild($group);
    }

    /**
     * Create element with the specified attributes.
     *
     * @param string $name
     * @param array  $attributes
     *
     * @return \DOMElement
     */
    private function createElement($name, array $attributes)
    {
        $filter = $this->document->createElement($name);

        foreach ($attributes as $key => $value) {
            if (\is_string($key)) {
                $filter->setAttribute($key, $value);
            } else {
                $filter->appendChild($this->createElement($value[0], $value[1]));
            }
        }

        return $filter;
    }
}

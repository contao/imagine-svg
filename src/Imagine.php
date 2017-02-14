<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ImagineSvg;

use Imagine\Image\AbstractImagine;
use Imagine\Image\Metadata\MetadataBag;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\BoxInterface;
use Imagine\Exception\InvalidArgumentException;
use Imagine\Exception\RuntimeException;

/**
 * Imagine implementation for SVG images.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class Imagine extends AbstractImagine
{
    /**
     * {@inheritdoc}
     */
    public function create(BoxInterface $size, ColorInterface $color = null)
    {
        if (null !== $color) {
            throw new InvalidArgumentException('Imagine SVG does not support colors');
        }

        $document = new \DOMDocument();
        $svg = $document->createElementNS('http://www.w3.org/2000/svg', 'svg');
        $svg->setAttribute('version', '1.1');

        if ($size->getWidth()) {
            $svg->setAttribute('width', $size->getWidth());
        }

        if ($size->getHeight()) {
            $svg->setAttribute('height', $size->getHeight());
        }

        $document->appendChild($svg);

        return new Image($document, new MetadataBag());
    }

    /**
     * {@inheritdoc}
     */
    public function open($path)
    {
        $path = $this->checkPath($path);
        $data = @file_get_contents($path);

        if (false === $data) {
            throw new RuntimeException(sprintf('Failed to open file "%s"', $path));
        }

        return $this->doLoad($data, new MetadataBag(['filepath' => $path]));
    }

    /**
     * {@inheritdoc}
     */
    public function load($string)
    {
        return $this->doLoad($string, new MetadataBag());
    }

    /**
     * Loads SVG and returns an Image instance.
     *
     * @param string      $data
     * @param MetadataBag $metadata
     *
     * @return Image
     */
    private function doLoad($data, MetadataBag $metadata)
    {
        if (substr($data, 0, 2) === hex2bin('1F8B')) {
            $data = gzdecode($data);
        }

        $internalErrors = libxml_use_internal_errors(true);
        $disableEntities = libxml_disable_entity_loader(true);
        libxml_clear_errors();

        $document = new \DOMDocument();
        $document->loadXML($data, LIBXML_NONET);

        libxml_use_internal_errors($internalErrors);
        libxml_disable_entity_loader($disableEntities);

        if ($error = libxml_get_last_error()) {
            libxml_clear_errors();

            throw new RuntimeException($error->message);
        }

        if ('svg' !== strtolower($document->documentElement->tagName)) {
            throw new RuntimeException('An image could not be created from the given input');
        }

        return new Image($document, $metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function read($resource)
    {
        if (!is_resource($resource)) {
            throw new InvalidArgumentException('Variable does not contain a stream resource');
        }

        $content = stream_get_contents($resource);

        if (false === $content) {
            throw new InvalidArgumentException('Cannot read resource content');
        }

        return $this->load($content);
    }

    /**
     * {@inheritdoc}
     */
    public function font($file, $size, ColorInterface $color)
    {
        throw new RuntimeException('This method is not implemented');
    }
}

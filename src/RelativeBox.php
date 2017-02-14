<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ImagineSvg;

use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\PointInterface;

/**
 * Relative box.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class RelativeBox implements RelativeBoxInterface
{
    /**
     * @var Box
     */
    private $box;

    /**
     * Constructor.
     *
     * @param int $width
     * @param int $height
     */
    public function __construct($width, $height)
    {
        $this->box = new Box($width, $height);
    }

    /**
     * {@inheritdoc}
     */
    public function getWidth()
    {
        return $this->box->getWidth();
    }

    /**
     * {@inheritdoc}
     */
    public function getHeight()
    {
        return $this->box->getHeight();
    }

    /**
     * {@inheritdoc}
     */
    public function scale($ratio)
    {
        $box = $this->box->scale($ratio);

        return new self($box->getWidth(), $box->getHeight());
    }

    /**
     * {@inheritdoc}
     */
    public function increase($size)
    {
        $box = $this->box->increase($size);

        return new self($box->getWidth(), $box->getHeight());
    }

    /**
     * {@inheritdoc}
     */
    public function contains(BoxInterface $box, PointInterface $start = null)
    {
        return $this->box->contains($box, $start);
    }

    /**
     * {@inheritdoc}
     */
    public function square()
    {
        return $this->box->square();
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return sprintf('%dx%d', $this->box->getWidth(), $this->box->getHeight());
    }

    /**
     * {@inheritdoc}
     */
    public function widen($width)
    {
        $box = $this->box->widen($width);

        return new self($box->getWidth(), $box->getHeight());
    }

    /**
     * {@inheritdoc}
     */
    public function heighten($height)
    {
        $box = $this->box->heighten($height);

        return new self($box->getWidth(), $box->getHeight());
    }
}

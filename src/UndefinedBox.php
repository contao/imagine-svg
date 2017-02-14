<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ImagineSvg;

use Imagine\Image\BoxInterface;
use Imagine\Image\PointInterface;

/**
 * Undefined box.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class UndefinedBox implements UndefinedBoxInterface
{
    /**
     * {@inheritdoc}
     */
    public function getWidth()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeight()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function scale($ratio)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function increase($size)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function contains(BoxInterface $box, PointInterface $start = null)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function square()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return 'undefined';
    }

    /**
     * {@inheritdoc}
     */
    public function widen($width)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function heighten($height)
    {
        return $this;
    }
}

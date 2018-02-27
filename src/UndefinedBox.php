<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ImagineSvg;

use Imagine\Image\BoxInterface;
use Imagine\Image\PointInterface;

class UndefinedBox implements UndefinedBoxInterface
{
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

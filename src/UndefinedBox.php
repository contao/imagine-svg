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

/**
 * @deprecated use SvgBox::createTypeNone() instead
 */
class UndefinedBox extends SvgBox implements UndefinedBoxInterface
{
    public function __construct()
    {
        parent::__construct(0, 0, self::TYPE_NONE);
    }

    /**
     * {@inheritdoc}
     */
    public function getWidth()
    {
        @trigger_error(
            'Relying on UndefinedBox::getWidth() being 0 is deprecated and will be changed in Version 1.0.0.',
            E_USER_DEPRECATED
        );

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeight()
    {
        @trigger_error(
            'Relying on UndefinedBox::getHeight() being 0 is deprecated and will be changed in Version 1.0.0.',
            E_USER_DEPRECATED
        );

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
        @trigger_error(
            'Relying on UndefinedBox::contains() being false is deprecated and will be changed in Version 1.0.0.',
            E_USER_DEPRECATED
        );

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function square()
    {
        @trigger_error(
            'Relying on UndefinedBox::square() being 0 is deprecated and will be changed in Version 1.0.0.',
            E_USER_DEPRECATED
        );

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

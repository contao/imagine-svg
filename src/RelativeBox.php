<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ImagineSvg;

/**
 * @deprecated use SvgBox::createTypeAspectRatio() instead
 */
class RelativeBox extends SvgBox implements RelativeBoxInterface
{
    /**
     * @param int $width
     * @param int $height
     */
    public function __construct($width, $height)
    {
        parent::__construct($width, $height, self::TYPE_ASPECT_RATIO);
    }

    /**
     * {@inheritdoc}
     */
    public function scale($ratio)
    {
        $box = parent::scale($ratio);

        return new self($box->getWidth(), $box->getHeight());
    }

    /**
     * {@inheritdoc}
     */
    public function increase($size)
    {
        $box = parent::increase($size);

        return new self($box->getWidth(), $box->getHeight());
    }

    /**
     * {@inheritdoc}
     */
    public function widen($width)
    {
        $box = parent::widen($width);

        return new self($box->getWidth(), $box->getHeight());
    }

    /**
     * {@inheritdoc}
     */
    public function heighten($height)
    {
        $box = parent::heighten($height);

        return new self($box->getWidth(), $box->getHeight());
    }
}

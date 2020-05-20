<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ImagineSvg;

use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\PointInterface;

class SvgBox implements BoxInterface
{
    const TYPE_ABSOLUTE = 0;
    const TYPE_ASPECT_RATIO = 1;
    const TYPE_NONE = 2;

    /**
     * @var int
     */
    private $type;

    /**
     * @var Box
     */
    private $box;

    /**
     * @param int $width
     * @param int $height
     * @param int $type
     */
    public function __construct($width, $height, $type = self::TYPE_ABSOLUTE)
    {
        if (!\in_array($type, [self::TYPE_ABSOLUTE, self::TYPE_ASPECT_RATIO, self::TYPE_NONE], true)) {
            throw new \InvalidArgumentException(sprintf('Invalid SvgBox type "%s", must be one of the %s::TYPE_* constants.', $type, __CLASS__));
        }

        $this->type = $type;

        if (self::TYPE_NONE === $type) {
            $this->box = new Box(300, 150);
        } else {
            $this->box = new Box($width, $height);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        if (self::TYPE_NONE === $this->type) {
            return 'undefined';
        }
        if (self::TYPE_ASPECT_RATIO === $this->type) {
            return sprintf('%dx%d', $this->box->getWidth(), $this->box->getHeight());
        }

        return (string) $this->box;
    }

    /**
     * @param int $width
     * @param int $height
     *
     * @return SvgBox
     */
    public static function createTypeAbsolute($width, $height)
    {
        return new self($width, $height);
    }

    /**
     * @param int $width
     * @param int $height
     *
     * @return SvgBox
     */
    public static function createTypeAspectRatio($width, $height)
    {
        return new RelativeBox($width, $height);
    }

    /**
     * @return SvgBox
     */
    public static function createTypeNone()
    {
        return new UndefinedBox();
    }

    /**
     * @return int one of the SvgBox::TYPE_* constants
     */
    public function getType()
    {
        return $this->type;
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

        return new self($box->getWidth(), $box->getHeight(), $this->type);
    }

    /**
     * {@inheritdoc}
     */
    public function increase($size)
    {
        $box = $this->box->increase($size);

        return new self($box->getWidth(), $box->getHeight(), $this->type);
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
    public function widen($width)
    {
        $box = $this->box->widen($width);

        return new self($box->getWidth(), $box->getHeight(), $this->type);
    }

    /**
     * {@inheritdoc}
     */
    public function heighten($height)
    {
        $box = $this->box->heighten($height);

        return new self($box->getWidth(), $box->getHeight(), $this->type);
    }
}

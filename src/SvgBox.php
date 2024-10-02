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

use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\PointInterface;

/**
 * @final
 */
class SvgBox implements BoxInterface
{
    public const TYPE_ABSOLUTE = 0;
    public const TYPE_ASPECT_RATIO = 1;
    public const TYPE_NONE = 2;

    /**
     * @var int
     */
    private $type;

    /**
     * @var Box
     */
    private $box;

    public function __construct(int $width, int $height, int $type = self::TYPE_ABSOLUTE)
    {
        if (!\in_array($type, [self::TYPE_ABSOLUTE, self::TYPE_ASPECT_RATIO, self::TYPE_NONE], true)) {
            throw new \InvalidArgumentException(sprintf('Invalid SvgBox type "%s", must be one of the %s::TYPE_* constants.', $type, self::class));
        }

        $this->type = $type;

        if (self::TYPE_NONE === $type) {
            $this->box = new Box(300, 150);
        } else {
            $this->box = new Box($width, $height);
        }
    }

    public function __toString(): string
    {
        if (self::TYPE_NONE === $this->type) {
            return 'undefined';
        }

        if (self::TYPE_ASPECT_RATIO === $this->type) {
            return sprintf('%dx%d', $this->box->getWidth(), $this->box->getHeight());
        }

        return (string) $this->box;
    }

    public static function createTypeAbsolute(int $width, int $height): self
    {
        return new self($width, $height);
    }

    public static function createTypeAspectRatio(int $width, int $height): self
    {
        return new self($width, $height, self::TYPE_ASPECT_RATIO);
    }

    public static function createTypeNone(): self
    {
        return new self(0, 0, self::TYPE_NONE);
    }

    /**
     * @return int one of the SvgBox::TYPE_* constants
     */
    public function getType(): int
    {
        return $this->type;
    }

    public function getWidth(): int
    {
        return $this->box->getWidth();
    }

    public function getHeight(): int
    {
        return $this->box->getHeight();
    }

    public function scale($ratio): self
    {
        $box = $this->box->scale($ratio);

        return new self($box->getWidth(), $box->getHeight(), $this->type);
    }

    public function increase($size): self
    {
        $box = $this->box->increase($size);

        return new self($box->getWidth(), $box->getHeight(), $this->type);
    }

    public function contains(BoxInterface $box, ?PointInterface $start = null): bool
    {
        return $this->box->contains($box, $start);
    }

    public function square(): int
    {
        return $this->box->square();
    }

    public function widen($width): self
    {
        $box = $this->box->widen($width);

        return new self($box->getWidth(), $box->getHeight(), $this->type);
    }

    public function heighten($height): self
    {
        $box = $this->box->heighten($height);

        return new self($box->getWidth(), $box->getHeight(), $this->type);
    }
}

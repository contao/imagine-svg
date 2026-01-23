<?php

declare(strict_types=1);

namespace Contao\ImagineSvg;

use Imagine\Image\Format as BaseFormat;

final class Format extends BaseFormat
{
    public const ID_SVG = 'svg';

    public const ID_SVGZ = 'svgz';

    private string $id;

    private function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getID()
    {
        return $this->id;
    }

    public function getMimeType()
    {
        return 'image/svg+xml';
    }

    public function getCanonicalFileExtension()
    {
        return $this->id;
    }

    public function getAlternativeIDs()
    {
        return [];
    }

    /**
     * @return Format|BaseFormat
     */
    protected static function create($formatID)
    {
        if (\in_array($formatID, [self::ID_SVG, self::ID_SVGZ], true)) {
            return new self($formatID);
        }

        return BaseFormat::create($formatID);
    }
}

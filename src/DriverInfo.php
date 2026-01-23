<?php

declare(strict_types=1);

namespace Contao\ImagineSvg;

use Imagine\Driver\AbstractInfo;
use Imagine\Exception\NotSupportedException;
use Imagine\Image\FormatList;
use Imagine\Image\Palette\PaletteInterface;
use Imagine\Image\Palette\RGB;

final class DriverInfo extends AbstractInfo
{
    /**
     * @var static|NotSupportedException|null
     */
    private static $instance;

    protected function __construct()
    {
        if (!class_exists(\DOMDocument::class)) {
            throw new NotSupportedException('DOM extension not installed');
        }
        $driverRawVersion = PHP_VERSION;
        $driverSemverVersion = \defined('PHP_MAJOR_VERSION') ? implode('.', [PHP_MAJOR_VERSION, PHP_MINOR_VERSION, PHP_RELEASE_VERSION]) : '';
        $engineRawVersion = \defined('LIBXML_DOTTED_VERSION') ? LIBXML_DOTTED_VERSION : '';
        $engineSemverVersion = preg_match('/^.*?(\d+\.\d+\.\d+)/', $engineRawVersion, $m) ? $m[1] : '';
        parent::__construct($driverRawVersion, $driverSemverVersion, $engineRawVersion, $engineSemverVersion);
    }

    public static function get($required = true): static|null
    {
        if (null === self::$instance) {
            try {
                self::$instance = new self();
            } catch (NotSupportedException $x) {
                self::$instance = $x;
            }
        }
        if (self::$instance instanceof self) {
            return self::$instance;
        }

        if ($required) {
            throw self::$instance;
        }

        return null;
    }

    public function requirePaletteSupport(PaletteInterface $palette): void
    {
        if (!($palette instanceof RGB)) {
            throw new NotSupportedException('SVG driver only supports RGB colors');
        }
    }

    protected function checkFeature($feature): void
    {
        switch ($feature) {
            case self::FEATURE_COLORPROFILES:
                throw new NotSupportedException('SVG driver does not support color profiles');
            case self::FEATURE_COLORSPACECONVERSION:
                throw new NotSupportedException('SVG driver does not support color space conversion');
            case self::FEATURE_COALESCELAYERS:
                throw new NotSupportedException('SVG driver does not support layers');
            case self::FEATURE_TEXTFUNCTIONS:
                throw new NotSupportedException('SVG driver does not support text functions');
            case self::FEATURE_MULTIPLELAYERS:
                throw new NotSupportedException('SVG driver does not support layer sets');
            case self::FEATURE_CUSTOMRESOLUTION:
                throw new NotSupportedException('SVG driver does not support setting custom resolutions');
            case self::FEATURE_EXPORTWITHCUSTOMRESOLUTION:
                throw new NotSupportedException('SVG driver does not support exporting images with custom resolutions');
            case self::FEATURE_DRAWFILLEDCHORDSCORRECTLY:
                throw new NotSupportedException('SVG driver does not support drawing');
            case self::FEATURE_DRAWUNFILLEDCIRCLESWITHTICHKESSCORRECTLY:
                throw new NotSupportedException('SVG driver does not support drawing');
            case self::FEATURE_DRAWUNFILLEDELLIPSESWITHTICHKESSCORRECTLY:
                throw new NotSupportedException('SVG driver does not support drawing');
            case self::FEATURE_GETCMYKCOLORSCORRECTLY:
                throw new NotSupportedException('SVG driver does not support CMYK');
            case self::FEATURE_ROTATEIMAGEWITHCORRECTSIZE:
                throw new NotSupportedException('SVG driver does not support rotation');
            case self::FEATURE_EXPORTWITHCUSTOMJPEGSAMPLINGFACTORS:
                throw new NotSupportedException('SVG driver does not support JPEG sampling factors');
            case self::FEATURE_ADDLAYERSTOEMPTYIMAGE:
                throw new NotSupportedException('SVG driver does not support layers');
        }
    }

    protected function buildSupportedFormats(): FormatList
    {
        $supportedFormats = [Format::get('svg')];

        if (\function_exists('gzdecode')) {
            $supportedFormats[] = Format::get('svgz');
        }

        return new FormatList($supportedFormats);
    }
}

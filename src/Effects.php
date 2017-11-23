<?php


namespace Contao\ImagineSvg;


use Imagine\Effects\EffectsInterface;
use Imagine\Exception\RuntimeException;
use Imagine\Image\Palette\Color\ColorInterface;

class Effects implements EffectsInterface
{

    /** @var Image */
    private $image;

    public function __construct(Image $image)
    {
        $this->image = $image;
    }

    public function gamma($correction)
    {
        throw new RuntimeException('This method is not implemented');

        // TODO: Implement gamma() method.
    }

    public function negative()
    {
        throw new RuntimeException('This method is not implemented');
        // TODO: Implement negative() method.
    }

    public function grayscale()
    {
        throw new RuntimeException('This method is not implemented');
        // TODO: Implement grayscale() method.
    }

    public function colorize(ColorInterface $color)
    {
        throw new RuntimeException('This method is not implemented');
        // TODO: Implement colorize() method.
    }

    public function sharpen()
    {
        throw new RuntimeException('This method is not implemented');
        // TODO: Implement sharpen() method.
    }

    public function blur($sigma)
    {

        $dom = $this->image->getDomDocument();
        $dom->documentElement->setAttribute("filter", "url(#rokkaBlur)");
        $filter = $dom->createDocumentFragment();
        $filter->appendXML('<filter id="rokkaBlur">
                <feGaussianBlur in="SourceGraphic" stdDeviation="'.$sigma.'" />
        </filter>');
        $dom->documentElement->insertBefore($filter, $dom->documentElement->firstChild);
    }

}

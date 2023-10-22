<?php declare(strict_types=1);

namespace uuf6429\RuneExamples\ShopExample\Context;

use Stringable;
use uuf6429\RuneExamples\ShopExample\Model\Product;

class ProductContext extends AbstractContext implements Stringable
{
    public function __construct(public readonly ?Product $product = null)
    {
        parent::__construct();
    }

    public function __toString(): string
    {
        return $this->product
            ? ucwords(trim($this->product->colour . ' ' . $this->product->name))
            : 'empty context';
    }
}

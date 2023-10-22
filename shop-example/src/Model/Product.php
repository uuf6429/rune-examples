<?php declare(strict_types=1);

namespace uuf6429\RuneExamples\ShopExample\Model;

use Closure;
use uuf6429\Rune\Util\LazyProperties;

/**
 * @property Category $category
 */
class Product
{
    use LazyProperties;

    public function __construct(
        public readonly int     $id,
        public readonly string  $name,
        public readonly string  $colour,
        public readonly int     $categoryId,
        public readonly Closure $categoryProvider
    ) {
    }

    protected function getCategory(): Category
    {
        return ($this->categoryProvider)($this->categoryId);
    }
}

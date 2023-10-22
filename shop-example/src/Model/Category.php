<?php declare(strict_types=1);

namespace uuf6429\RuneExamples\ShopExample\Model;

use Closure;
use uuf6429\Rune\Util\LazyProperties;

/**
 * @property null|Category $parent
 */
class Category
{
    use LazyProperties;

    public function __construct(
        public readonly int     $id,
        public readonly string  $name,
        public readonly ?int    $parentId,
        public readonly Closure $categoryProvider
    ) {
    }

    protected function getParent(): ?Category
    {
        return ($this->categoryProvider)($this->parentId);
    }

    /**
     * Returns true if category name or any of its parents are identical to `$name`.
     */
    public function in(string $name): bool
    {
        if (strtolower($this->name) === strtolower($name)) {
            return true;
        }

        if ($this->parent !== null) {
            return $this->parent->in($name);
        }

        return false;
    }
}

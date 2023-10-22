<?php declare(strict_types=1);

namespace uuf6429\RuneExamples\ShopExample\Context;

use uuf6429\Rune\Context\ClassContext;
use uuf6429\RuneExamples\ShopExample\Model\StringUtils;

abstract class AbstractContext extends ClassContext
{
    public function __construct(
        public readonly StringUtils $String = new StringUtils()
    ) {
    }
}

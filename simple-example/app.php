<?php declare(strict_types=1);

namespace uuf6429\RuneExamples\SimpleExample;

use uuf6429\Rune\Action\CallbackAction;
use uuf6429\Rune\Context\ClassContext;
use uuf6429\Rune\Engine;
use uuf6429\Rune\Rule\GenericRule;

require_once __DIR__ . '/../vendor/autoload.php';

// A class whose instances will be available inside rule engine.
class Product
{
    public function __construct(
        public readonly string $name,
        public readonly string $colour,
    ) {
    }
}

// A class that represents the rule engine execution context.
// Note that public properties will be available in the rule expressions,
// in this case rules will have access to "product" as a variable (and all of product's public properties).
class ProductContext extends ClassContext
{
    public function __construct(
        public readonly Product $product
    ) {
    }
}

// Declare an action to be triggered when a rule matches against a product.
$action = new CallbackAction(
    function ($eval, ProductContext $context, $rule) {
        printf(
            "‣ Rule %s triggered for %s %s\n",
            $rule->getId(),
            ucwords($context->product->colour),
            $context->product->name
        );
    }
);

// Declare some sample rules.
$rules = [
    new GenericRule('1', 'Red Products', 'product.colour == "red"', $action),
    new GenericRule('2', 'Red Socks', 'product.colour == "red" and product.name matches "/socks/i"', $action),
    new GenericRule('3', 'Green Socks', 'product.colour == "green" and product.name matches "/socks/i"', $action),
    new GenericRule('4', 'Socks', 'product.name matches "/socks/" > 0', $action),
];

// Declare available products (to run rules against).
$products = [
    new Product('Bricks', 'red'),
    new Product('Soft Socks', 'green'),
    new Product('Sporty Socks', 'yellow'),
];

// Create rule engine.
$engine = new Engine();

// Run rules for each product. Note that each product exists in a separate context.
foreach ($products as $product) {
    $engine->execute(new ProductContext($product), $rules);
}

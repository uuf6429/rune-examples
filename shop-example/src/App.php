<?php declare(strict_types=1);

namespace uuf6429\RuneExamples\ShopExample;

use JetBrains\PhpStorm\ArrayShape;
use Throwable;
use uuf6429\Rune\Context\ContextDescriptorInterface;
use uuf6429\Rune\Engine;
use uuf6429\Rune\Engine\ExceptionHandler\CollectExceptions;
use uuf6429\Rune\Rule\GenericRule;
use uuf6429\Rune\Rule\RuleInterface;
use uuf6429\Rune\Util\SymfonyEvaluator;
use uuf6429\RuneExamples\ShopExample\Action\PrintAction;
use uuf6429\RuneExamples\ShopExample\Context\ProductContext;
use uuf6429\RuneExamples\ShopExample\Model\Category;
use uuf6429\RuneExamples\ShopExample\Model\Product;

class App
{
    public static function create(): static
    {
        return new static();
    }

    public function run(): void
    {
        ['rules' => $rules, 'categories' => $categories, 'products' => $products] = $this->getData($_POST);
        ['descriptor' => $descriptor, 'result' => $result, 'resultOut' => $resultOut, 'resultErr' => $resultErr] = $this->execute($rules, $products);
        (new View)->render(
            $this->getTokens($descriptor),
            $categories,
            $products,
            $rules,
            $result,
            $resultOut,
            $resultErr,
        );
    }

    #[ArrayShape(['descriptor' => ContextDescriptorInterface::class, 'result' => 'string', 'resultOut' => 'string', 'resultErr' => 'string'])]
    public function execute($rules, $products): array
    {
        $exceptionHandler = new CollectExceptions();
        $engine = new Engine(null, null, $exceptionHandler);
        $context = new ProductContext(new Product(0, '', '', 0, fn() => null));
        $descriptor = $context->getContextDescriptor();

        // Provide code compiled from rule conditions
        $result = '';
        $eval = new SymfonyEvaluator();
        $maxLength = 0;
        foreach ($rules as $rule) {
            $maxLength = max($maxLength, strlen($rule->getName()));
        }
        foreach ($rules as $rule) {
            try {
                $eval->setFunctions($descriptor->getFunctions());
                $eval->setVariables($descriptor->getVariables());
                $code = $eval->compile($rule->getCondition());
            } catch (Throwable $ex) {
                $code = 'Compile Error (' . get_class($ex) . '): ' . $ex->getMessage();
            }
            $result .= str_pad($rule->getName(), $maxLength)
                . ' => ' . $code . PHP_EOL;
        }

        // Provide triggered rules and any generated errors
        ob_start();
        foreach ($products as $product) {
            $context = new ProductContext($product);
            $engine->execute($context, $rules);
        }
        $resultOut = htmlspecialchars((string)ob_get_clean(), ENT_QUOTES);
        $resultErr = implode(PHP_EOL, $exceptionHandler->getExceptions());

        return compact('descriptor', 'result', 'resultOut', 'resultErr');
    }

    #[ArrayShape(['rules' => RuleInterface::class . '[]', 'categories' => Category::class . '[]', 'products' => Product::class . '[]'])]
    private function getData(array $requestData): array
    {
        static $default = [
            'rules' => [
                ['Red Products', 'product.colour == String.lower("Red")'],
                ['Red Socks', 'product.colour == "red" and product.category.in("Socks")'],
                ['Green Socks', 'product.colour == "green" and (product.name matches "/socks/i") > 0'],
                ['Socks', 'product.category.in("Socks")'],
                ['Toys', 'product.category.in("Toys")'],
            ],
            'categories' => [
                ['Root', null],
                ['Clothes', 1],
                ['Toys', 1],
                ['Underwear', 2],
                ['Jackets', 2],
                ['Socks', 4],
            ],
            'products' => [
                ['Bricks', 'red', 3],
                ['Soft Socks', 'green', 6],
                ['Sporty Socks', 'yellow', 6],
                ['Lego Blocks', '', 3],
                ['Adidas Jacket', 'black', 5],
            ],
        ];

        /** @var array{rules:array[],categories:array[],products:array[]} $data */
        $data = array_merge(
            $default,
            array_map(
                static fn($group) => !is_array($group) ? $group : array_filter(array_values($group), array_filter(...)),
                $requestData
            )
        );

        $action = new PrintAction();

        $rules = array_map(
            static fn($index, $data) => new GenericRule((string)($index + 1), $data[0], $data[1], $action),
            array_keys($data['rules']),
            $data['rules']
        );

        /** @var Category[] $categories */
        $categories = [];

        $categoryProvider = static function (?int $id) use (&$categories) {
            return $categories[$id - 1] ?? null;
        };

        $categories = array_map(
            static fn($index, $data) => new Category($index + 1, $data[0], isset($data[1]) ? (int)$data[1] : null, $categoryProvider),
            array_keys($data['categories']),
            $data['categories']
        );

        $products = array_map(
            static fn($index, $data) => new Product($index + 1, $data[0], $data[1], (int)$data[2], $categoryProvider),
            array_keys($data['products']),
            $data['products']
        );

        return compact('rules', 'categories', 'products');
    }

    #[ArrayShape(['constants' => 'array', 'operators' => 'array', 'variables' => 'array', 'functions' => 'array', 'typeinfo' => 'array'])]
    private function getTokens(ContextDescriptorInterface $descriptor): array
    {
        $arrayify = static fn($items) => array_map(static fn($item) => $item->toArray(), $items);

        return [
            'constants' => [
                [
                    'name' => 'true',
                    'type' => 'boolean',
                ],
                [
                    'name' => 'false',
                    'type' => 'boolean',
                ],
                [
                    'name' => 'null',
                    'type' => 'null',
                ],
            ],
            'operators' => [
                '+', '-', '*', '/', '%', '**',                              // arithmetic
                '&', '|', '^',                                              // bitwise
                '==', '===', '!=', '!==', '<', '>', '<=', '>=', 'matches',  // comparison
                'not', '!', 'and', '&&', 'or', '||',                        // logical
                '~',                                                        // concatenation
                'in', 'not in',                                             // array
                '..',                                                       // range
                '?', '?:', ':',                                             // ternary
            ],
            'variables' => $arrayify(array_values($descriptor->getVariableTypeInfo())),
            'functions' => $arrayify(array_values($descriptor->getFunctionTypeInfo())),
            'typeinfo' => $arrayify($descriptor->getDetailedTypeInfo()),
        ];
    }
}

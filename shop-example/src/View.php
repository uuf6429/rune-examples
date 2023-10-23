<?php

namespace uuf6429\RuneExamples\ShopExample;

use uuf6429\Rune\Rule\RuleInterface;use uuf6429\RuneExamples\ShopExample\Model\Category;use uuf6429\RuneExamples\ShopExample\Model\Product;class View
{
public function render(
    string $appRoot,
    array  $tokens,
    array  $categories,
    array  $products,
    array  $rules,
    string $resultGen,
    string $resultOut,
    string $resultErr): void
{
$asHtml = static fn(string $text) => htmlspecialchars($text, ENT_QUOTES);
$asJson = static fn(array $items) => json_encode($items, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
?><!DOCTYPE html>
<html lang="en">
    <head>
        <title>Rule Engine Example</title>
        <!-- jQuery -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.2.2/jquery.min.js"></script>
        <!-- Twitter Bootstrap -->
        <link rel="stylesheet"
              href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.6/css/bootstrap.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.6/js/bootstrap.min.js"></script>
        <!-- CodeMirror -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.13.2/codemirror.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.13.2/codemirror.min.js"></script>
        <!-- CodeMirror Simple Mode -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.13.2/addon/mode/simple.js"></script>
        <!-- CodeMirror Hints -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.13.2/addon/hint/show-hint.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.13.2/addon/hint/show-hint.js"></script>
        <!-- Rune CodeMirror Support -->
        <link rel="stylesheet" href="<?= "./rune.css" ?>">
        <script src="<?= "./rune.js" ?>"></script>
        <!-- Some custom CSS -->
        <style type="text/css">
            .cm-hint-icon-uuf6429-Rune-example-Model-Product:before {
                content: "\1F455";
                font-size: 8px;
            }

            .cm-hint-icon-uuf6429-Rune-example-Model-Category:before {
                content: "\2731";
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Rule Engine Shop Example</h1>

            <form action="#results" method="post">
                &nbsp;
                <div class="row">
                    <fieldset class="col-md-4">
                        <legend>Categories</legend>
                        <table class="table table-hover table-condensed" id="categories">
                            <thead>
                                <tr>
                                    <th width="32px">ID</th>
                                    <th>Name</th>
                                    <th width="80px">Parent</th>
                                </tr>
                            </thead>
                        </table>
                    </fieldset>

                    <fieldset class="col-md-8">
                        <legend>Products</legend>
                        <table class="table table-hover table-condensed" id="products">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Colour</th>
                                    <th width="80px">Category</th>
                                </tr>
                            </thead>
                        </table>
                    </fieldset>
                </div>
                &nbsp;
                <div class="row">
                    <fieldset class="col-md-12">
                        <legend>Rules</legend>
                        <table class="table table-hover table-condensed" id="rules">
                            <thead>
                                <tr>
                                    <th width="32px">ID</th>
                                    <th width="30%">Name</th>
                                    <th>Condition</th>
                                </tr>
                            </thead>
                        </table>
                    </fieldset>
                </div>
                &nbsp;
                <div class="row">
                    <div class="text-center">
                        <input type="submit" class="btn btn-primary btn-lg" value="Execute"/>
                        <a class="btn btn-link" href="">Reset Changes</a>
                    </div>
                </div>
                &nbsp;
            </form>

            <fieldset id="results">
                <legend>Rule Engine Result</legend>
                <pre><?=
                    implode(
                        PHP_EOL,
                        [
                            '<b>Result:</b>',
                            $asHtml($resultOut),
                            '<b>Compiled:</b>',
                            $asHtml($resultGen),
                            '<b>Errors:</b>',
                            $resultErr ? $asHtml($resultErr) : '<i>None</i>',
                        ]
                    )
                    ?></pre>
            </fieldset>
        </div>

        <script>
            $(document).ready(function () {
                let globalRowCount = 0,
                    // default rune editor settings
                    runeEditorOptions = {
                        tokens: <?= $asJson($tokens) ?>
                    },
                    // a simple data table populator
                    setupTable = function (table, data, rowGenerator) {
                        var $table = $(table),
                            $tbody = $table.find('tbody:last'),
                            updateEmptyRows = function () {
                                $tbody
                                    .find('tr')
                                    .filter(function () {
                                        let empty = true;
                                        $(this).find('input, textarea, select').each(function () {
                                            if ($(this).val()) {
                                                empty = false;
                                                return false;
                                            }
                                        });
                                        return empty;
                                    })
                                    .remove();
                                addRow();
                            },
                            addRow = function (rowData) {
                                let $tr = rowGenerator($tbody, rowData || {});
                                $tr.find('input, textarea, select').on('change, blur', updateEmptyRows);
                                $tbody.find('.row-num-autogen').each(function (num, el) {
                                    el.innerHTML = (num + 1).toString();
                                });
                            };
                        if (!$tbody.length) {
                            $tbody = $('<tbody/>');
                            $table.append($tbody);
                        }
                        $table.width('100%');
                        $.each(data, function (i, rowData) {
                            addRow(rowData);
                        });
                        addRow();
                    };

                // category table
                setupTable(
                    '#categories',
                    <?= $asJson(array_map(
                        static fn(Category $category) => [
                            $category->name,
                            $category->parentId,
                        ],
                        $categories
                    )) ?>,
                    function ($tbody, data) {
                        let rowIndex = ++globalRowCount,
                            $tr = $('<tr/>');

                        $tbody.append(
                            $tr.append(
                                $('<td/>').append($('<div style="padding: 5px 0;" class="row-num-autogen"/>')),
                                $('<td/>').append($('<input type="text" name="categories[' + rowIndex + '][]"'
                                    + ' class="form-control input-sm" placeholder="Category Name"/>').val(data[0] || '')),
                                $('<td/>').append($('<input type="text" name="categories[' + rowIndex + '][]"'
                                    + ' class="form-control input-sm" placeholder="ID"/>').val(data[1] || ''))
                            )
                        );

                        return $tr;
                    }
                );

                // products table
                setupTable(
                    '#products',
                    <?= $asJson(array_map(
                        static fn(Product $product) => [
                            $product->name,
                            $product->colour,
                            $product->categoryId,
                        ],
                        $products
                    )) ?>,
                    function ($tbody, data) {
                        let rowIndex = ++globalRowCount,
                            $tr = $('<tr/>');

                        $tbody.append(
                            $tr.append(
                                $('<td/>').append($('<input type="text" name="products[' + rowIndex + '][]"'
                                    + ' class="form-control input-sm" placeholder="Product Name"/>').val(data[0] || '')),
                                $('<td/>').append($('<input type="text" name="products[' + rowIndex + '][]"'
                                    + ' class="form-control input-sm" placeholder="Product Colour"/>').val(data[1] || '')),
                                $('<td/>').append($('<input type="text" name="products[' + rowIndex + '][]"'
                                    + ' class="form-control input-sm" placeholder="ID"/>').val(data[2] || ''))
                            )
                        );

                        return $tr;
                    }
                );

                // rules table
                setupTable(
                    '#rules',
                    <?= $asJson(array_map(
                        static fn(RuleInterface $rule) => [
                            $rule->getName(),
                            $rule->getCondition(),
                        ],
                        $rules
                    )) ?>,
                    function ($tbody, data) {
                        let rowIndex = ++globalRowCount,
                            $tr = $('<tr/>'),
                            $numCell = $('<td/>').append($('<div style="padding: 7px 0;" class="row-num-autogen"/>')),
                            $nameCell = $('<td/>').append($('<input type="text" name="rules[' + rowIndex + '][]"'
                                + ' class="form-control" placeholder="Rule Name"/>').val(data[0] || '')),
                            $condCell = $('<td/>').append($('<input type="text" name="rules[' + rowIndex + '][]"'
                                + ' class="form-control" data-lines="1" data-addclass="form-control" placeholder="Condition"/>').val(data[1] || ''));

                        $tbody.append($tr);
                        $tr.append($numCell, $nameCell, $condCell);
                        $condCell.find('input').RuneEditor(runeEditorOptions);

                        return $tr;
                    }
                );
            });
        </script>
    </body>
</html><?php
}
}

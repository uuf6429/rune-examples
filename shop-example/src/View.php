<?php

namespace uuf6429\RuneExamples\ShopExample;

use uuf6429\Rune\Rule\RuleInterface;use uuf6429\RuneExamples\ShopExample\Model\Category;use uuf6429\RuneExamples\ShopExample\Model\Product;class View
{
public function render(
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
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"
                integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g=="
                crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <!-- Bootstrap -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
              integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
              crossorigin="anonymous">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
                integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
                crossorigin="anonymous"></script>
        <!-- CodeMirror -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/codemirror.min.css"
              integrity="sha512-uf06llspW44/LZpHzHT6qBOIVODjWtv4MxCricRxkzvopAlSWnTf6hpZTFxuuZcuNE9CBQhqE0Seu1CoRk84nQ=="
              crossorigin="anonymous" referrerpolicy="no-referrer"/>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/codemirror.min.js"
                integrity="sha512-2359y3bpxFfJ9xZw1r2IHM0WlZjZLI8gjLuhTGOVtRPzro3dOFy4AyEEl9ECwVbQ/riLXMeCNy0h6HMt2WUtYw=="
                crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <!-- CodeMirror Simple Mode -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/addon/mode/simple.min.js"
                integrity="sha512-CGM6DWPHs250F/m90YZ9NEiEUhd9a4+u8wAzeKC6uHzZbYyt9/e2dLC5BGGB6Y0HtEdZQdSDYjDsoTyNGdMrMA=="
                crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <!-- CodeMirror Hints -->
        <link rel="stylesheet"
              href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/addon/hint/show-hint.min.css"
              integrity="sha512-W/cvA9Wiaq79wGy/VOkgMpOILyqxqIMU+rkneDUW2uqiUT53I6DKmrF4lmCbRG+/YrW0J69ecvanKCCyb+sIWA=="
              crossorigin="anonymous" referrerpolicy="no-referrer"/>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/addon/hint/show-hint.min.js"
                integrity="sha512-4+hfJ/4qrBFEm8Wdz+mXpoTr/weIrB6XjJZAcc4pE2Yg5B06aKS/YLMN5iIAMXFTe0f1eneuLE5sRmnSHQqFNg=="
                crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <!-- Load style according to color scheme -->
        <script>
            document.documentElement.setAttribute(
                'data-bs-theme',
                window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
            );
        </script>
        <!-- Rune CodeMirror Support -->
        <link rel="stylesheet" href="<?= "./rune.css" ?>">
        <script src="<?= "./rune.js" ?>"></script>
        <!-- Some custom CSS -->
        <style>
            :root {
                color-scheme: light;
            }

            @media (prefers-color-scheme: dark) {
                :root {
                    color-scheme: dark;
                }

                .CodeMirror.cm-s-explang,
                .CodeMirror.cm-s-explang .cm-operator {
                    color: #A9B7C6;
                }

                .CodeMirror.cm-s-explang .CodeMirror-cursor {
                    border-left-color: #FFF;
                }

                .CodeMirror.cm-s-explang .CodeMirror-selected {
                    background: #214283;
                }

                .CodeMirror.cm-s-explang .cm-string {
                    color: #6A8759;
                }

                .CodeMirror.cm-s-explang .cm-number {
                    color: #6897BB;
                }

                .CodeMirror.cm-s-explang .cm-property {
                    color: #FFC66D;
                }

                .CodeMirror.cm-s-explang .cm-atom {
                    color: #CC7832;
                }

                .CodeMirror-hints.explang[role="listbox"] {
                    background: #212529;
                    border-color: #FFFFFF26;
                }

                .CodeMirror-hints.explang[role="listbox"] li {
                    color: #DEE2E6;
                }

                .CodeMirror-hints.cm-hint-hint {
                    color: #DEE2E6;
                    background: #212529;
                    border-color: #FFFFFF26;
                }

                .CodeMirror-hints.cm-hint-hint .cm-signature .name {
                    color: #DEE2E6;
                }
            }

            .CodeMirror.cm-s-explang {
                background: transparent;
            }

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
                                    <th style="width: 32px">ID</th>
                                    <th>Name</th>
                                    <th style="width: 80px">Parent</th>
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
                                    <th style="width: 80px">Category</th>
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
                                    <th style="width: 32px">ID</th>
                                    <th style="width: 30%">Name</th>
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
            $(function () {
                let globalRowCount = 0,
                    // default rune editor settings
                    runeEditorOptions = {
                        tokens: <?= $asJson($tokens) ?>
                    },
                    // a simple data table filler
                    setupTable = function (table, data, rowGenerator) {
                        const $table = $(table);
                        let $tbody = $table.find('tbody:last');
                        const updateEmptyRows = function () {
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
                        };
                        const addRow = function (rowData) {
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

<?php

namespace SB\OneCExchange;

class Goods extends Connector
{
    protected $entityCode = 'goods';
    protected $measuresList = [];
    protected $propsCodes = [];

    protected $products = [];

    private static $instance = false;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    protected function __construct()
    {
        \CModule::IncludeModule('iblock');
        \CModule::IncludeModule('catalog');
    }

    public function import()
    {
        $dataFromOneC = $this->getData();
        $this->processData($dataFromOneC);
    }

    protected function processData($data)
    {
        // создаем и обновляем свойства
        $this->importProps(is_array($data["Properties"]) ? $data["Properties"] : []);

        // парсим массив товаров
        $this->parseProducts(is_array($data["Goods"]) ? $data["Goods"] : []);

        // создаем и обновляем товары из инфы о товарах и ТП (торговые предложения так же сохраняем как товары)
        $this->importCharacteristics(is_array($data["Characteristics"]) ? $data["Characteristics"] : []);

        // сохраняем свойства
        $this->importPropsValues(is_array($data["PropertiesValues"]) ? $data["PropertiesValues"] : []);

        // Устанавливаем свойство сортировки после импорта всех свойств товаров
        \SB\Product::setProductsSortAndStandardSizeFlag();
    }

    protected function importProps($arProps)
    {
        $sort = 0;
        $obProperty = new \CIBlockProperty();

        // собираем свойства из 1С
        $rsProperty = \CIBlockProperty::GetList(
            array('ID' => 'ASC'),
            [
                'IBLOCK_ID' => CATALOG_IBLOCK_ID,
            ]
        );
        $existingProps = [];
        while ($existingProp = $rsProperty->fetch()) {
            if (strlen(trim($existingProp["XML_ID"])) > 20) {
                $existingProps[$existingProp["XML_ID"]] = $existingProp["ID"];
            }
        }

        foreach ($arProps as $prop) {
            $prop['ID'] = trim($prop['ID']);
            $prop['Description'] = trim($prop['Description']);
            $name = $prop['Description'];
            if (preg_match('/(\([\s\S]+\))$/ui', $name, $matches)) {
                if ($matches[1]) {
                    $name = trim(str_replace($matches[1], '', $name));
                }
            }
            if ($prop['ID']) {
                $rsProperty = \CIBlockProperty::GetList(
                    array('ID' => 'ASC'),
                    [
                        'IBLOCK_ID' => CATALOG_IBLOCK_ID,
                        'XML_ID' => $prop['ID'],
                    ]
                );
                if ($existingProp = $rsProperty->fetch()) {
                    $obProperty->Update($existingProp['ID'], [
                        'NAME' => $name,
                    ]);
                    $this->propsCodes[$existingProp['XML_ID']] = $existingProp['CODE'];
                    unset($existingProps[$existingProp['XML_ID']]);
                } else {
                    $propCode = $this->generatePropCode($prop['Description']);
                    $obProperty->Add([
                        'NAME' => $name,
                        'SORT' => $sort,
                        'CODE' => $propCode,
                        'PROPERTY_TYPE' => 'S',
                        'IBLOCK_ID' => CATALOG_IBLOCK_ID,
                        'XML_ID' => $prop['ID'],

                        'SMART_FILTER' => 'Y',
                        'FEATURES' => [
                            'n0' => [
                                'IS_ENABLED' => 'Y',
                                'MODULE_ID' => 'iblock',
                                'FEATURE_ID' => 'DETAIL_PAGE_SHOW',
                            ],
                        ],
                    ]);
                    $this->propsCodes[$prop['ID']] = $propCode;
                }
            }
            $sort += 10;
        }

        foreach ($existingProps as $existingPropId) {
            if (intval($existingPropId)) {
                \CIBlockProperty::Delete($existingPropId);
            }
        }
    }

    protected function parseProducts($arProducts)
    {
        $groups = Groups::getInstance();
        foreach ($arProducts as $product) {
            $product['ID'] = trim($product['ID']);
            $product['Description'] = trim($product['Description']);
            $product['Description2'] = trim($product['Description2']);
            $product['Description3'] = trim($product['Description3']);
            $product['Parent'] = trim($product['Parent']);
            if (!$product['Parent']) {
                $product['Parent'] = 'komplektuyushchie';
            }
            $product['Category'] = trim($product['Category']);
            $product['Unit'] = trim($product['Unit']);
            $product['UnitsPerDay'] = trim($product['UnitsPerDay']);

            if ($product['ID']) {
                $measureId = $this->getMeasureIdByName($product['Unit']);
                $section = false;
                if ($product['Parent']) {
                    $section = $groups->getSectionByXmlId($product['Parent']);
                }
                $diametr = '';
                $length = '';
                if (preg_match('/(\d+\х\d+)/ui', $product['Description2'], $matches)) {
                    $sizes = explode('х', $matches[1]);
                    $diametr = 'М' . $sizes[0];
                    $length = $sizes[1];
                }
                if ($section['DEPTH_LEVEL'] >= 1) {
                    $this->products[$product['ID']] = [
                        'IBLOCK_ID' => CATALOG_IBLOCK_ID,
                        'XML_ID' => $product['ID'],
                        'NAME' => $product['Description2'],
                        'IBLOCK_SECTION_ID' => $section ? $section['ID'] : false,
                        'SORT' => 500,
                        'DETAIL_TEXT' => $product['Description3'],
                        "MEASURE" => $measureId,
                        'PROPERTY_VALUES' => [
                            'FULL_NAME' => $product['Description'],
                            'CATEGORY' => $product['Category'],
                            'IS_SET' => $product['IsSet'] ? 1 : 0,
                            'DIAMETR' => $diametr,
                            'LENGTH' => $length,
                            'UNITS_PER_DAY' => $product['UnitsPerDay'] > 1 ? $product['UnitsPerDay'] : '',
                            'REQ_CALCULATION' => $product['RequiredCalculation'] ? REQ_CALCULATION_PROP_VALUE_ID : '',
                        ],
                    ];
                }
            }
        }
    }

    protected function importProducts()
    {
        $obElement = new \CIBlockElement();
        foreach ($this->products as $product) {
            if ($product['HAS_SKU']) {
                continue;
            }

            $rsElement = \CIBlockElement::GetList(
                array('ID' => 'ASC'),
                [
                    'IBLOCK_ID' => CATALOG_IBLOCK_ID,
                    '=XML_ID' => $product['XML_ID'],
                ]
            );
            if ($existingProduct = $rsElement->fetch()) {
                $obElement->Update($existingProduct['ID'], [
                    'ACTIVE' => 'Y',
                    'NAME' => $product['NAME'],
                    //'SORT' => \SB\Product::getProductSort($product['NAME']),
                    'DETAIL_TEXT' => $product['DETAIL_TEXT'],
                    'IBLOCK_SECTION_ID' => $product['IBLOCK_SECTION_ID'],
                ]);
                \CIBlockElement::SetPropertyValuesEx(
                    $existingProduct['ID'],
                    CATALOG_IBLOCK_ID,
                    [
                        'FULL_NAME' => $product['PROPERTY_VALUES']['FULL_NAME'],
                        'CATEGORY' => $product['PROPERTY_VALUES']['CATEGORY'],
                        'IS_SET' => $product['PROPERTY_VALUES']['IS_SET'],
                        'LENGTH' => $product['PROPERTY_VALUES']['LENGTH'],
                        'DIAMETR' => $product['PROPERTY_VALUES']['DIAMETR'],
                        'UNITS_PER_DAY' => $product['PROPERTY_VALUES']['UNITS_PER_DAY'],
                        'REQ_CALCULATION' => $product['PROPERTY_VALUES']['REQ_CALCULATION'],
                    ]
                );

                \CCatalogProduct::Update($existingProduct['ID'], [
                    "MEASURE" => $product['MEASURE'],
                    "VAT_ID" => 1,
                    "VAT_INCLUDED" => "Y",
                    "CAN_BUY_ZERO" => "Y",
                ]);
            } else {
                // составить массив полей
                $elementId = $obElement->Add([
                    'ACTIVE' => 'Y',
                    'IBLOCK_ID' => CATALOG_IBLOCK_ID,
                    'XML_ID' => $product['XML_ID'],
                    'NAME' => $product['NAME'],
                    'CODE' => $this->getUniqueProductCodeByName($product['NAME']),
                    'IBLOCK_SECTION_ID' => $product['IBLOCK_SECTION_ID'],
                    'DETAIL_TEXT' => $product['DETAIL_TEXT'],
                    'PROPERTY_VALUES' => [
                        'SORT' => \SB\Product::getProductSort($product['NAME']),
                        'FULL_NAME' => $product['PROPERTY_VALUES']['FULL_NAME'],
                        'CATEGORY' => $product['PROPERTY_VALUES']['CATEGORY'],
                        'LENGTH' => $product['PROPERTY_VALUES']['LENGTH'],
                        'DIAMETR' => $product['PROPERTY_VALUES']['DIAMETR'],
                        'IS_SET' => $product['PROPERTY_VALUES']['IS_SET'] ? 1 : 0,
                        'UNITS_PER_DAY' => $product['PROPERTY_VALUES']['UNITS_PER_DAY'],
                        'REQ_CALCULATION' => $product['PROPERTY_VALUES']['REQ_CALCULATION'],
                    ],
                ]);

                if ($elementId) {
                    \CCatalogProduct::Add([
                        "ID" => $elementId,
                        "MEASURE" => $product['MEASURE'],
                        "VAT_ID" => 1,
                        "VAT_INCLUDED" => "Y",
                        "CAN_BUY_ZERO" => "Y",
                    ]);
                }
            }
        }
    }

    protected function importCharacteristics($arSku)
    {
        $obElement = new \CIBlockElement();
        $timestampStart = time();

        if (!$arSku) {
            return;
        }

        foreach ($arSku as $sku) {
            $sku['ID'] = trim($sku['ID']);
            $sku['Good'] = trim($sku['Good']);
            $sku['Description'] = trim($sku['Description']);

            if ($sku['ID'] && $sku['Good']) {
                $product = $this->products[$sku['Good']];
                $existingSku = $this->getProductByXmlId($sku['Good'] . '#' . $sku['ID']);
                $name = $product['NAME'] . ', ' . $sku['Description'];
                if ($product['XML_ID']) {
                    $this->products[$product['XML_ID']]['HAS_SKU'] = true;
                    $measureId = $product['MEASURE'];
                    if ($existingSku['ID']) {
                        $obElement->Update($existingSku['ID'], [
                            'ACTIVE' => 'Y',
                            'NAME' => $name,
                            //'SORT' => \SB\Product::getProductSort($name),
                            'IBLOCK_SECTION_ID' => $product['IBLOCK_SECTION_ID'],
                            'DETAIL_TEXT' => $product['DETAIL_TEXT'],
                        ]);
                        \CIBlockElement::SetPropertyValuesEx(
                            $existingSku['ID'],
                            CATALOG_IBLOCK_ID,
                            [
                                'MODEL' => $sku['Description'],
                                'FULL_NAME' => $product['PROPERTY_VALUES']['FULL_NAME'] . ', ' . $sku['Description'],
                                'CATEGORY' => $product['PROPERTY_VALUES']['CATEGORY'],
                                'IS_SET' => $product['PROPERTY_VALUES']['IS_SET'],
                                'LENGTH' => $product['PROPERTY_VALUES']['LENGTH'],
                                'DIAMETR' => $product['PROPERTY_VALUES']['DIAMETR'],
                                'UNITS_PER_DAY' => $product['PROPERTY_VALUES']['UNITS_PER_DAY'],
                                'REQ_CALCULATION' => $product['PROPERTY_VALUES']['REQ_CALCULATION'],
                            ]
                        );

                        \CCatalogProduct::Update($existingSku['ID'], [
                            "MEASURE" => $measureId,
                            "VAT_ID" => 1,
                            "VAT_INCLUDED" => "Y",
                            "CAN_BUY_ZERO" => "Y",
                        ]);
                    } else {
                        // составить массив полей
                        $elementId = $obElement->Add([
                            'ACTIVE' => 'Y',
                            'IBLOCK_ID' => CATALOG_IBLOCK_ID,
                            'XML_ID' => $sku['Good'] . '#' . $sku['ID'],
                            'NAME' => $name,
                            'IBLOCK_SECTION_ID' => $product['IBLOCK_SECTION_ID'],
                            'CODE' => $this->getUniqueProductCodeByName($name),
                            'DETAIL_TEXT' => $product['DETAIL_TEXT'],
                            'PROPERTY_VALUES' => [
                                'MODEL' => $sku['Description'],
                                'SORT' => \SB\Product::getProductSort($name),
                                'FULL_NAME' => $product['PROPERTY_VALUES']['FULL_NAME'] . ', ' . $sku['Description'],
                                'CATEGORY' => $product['PROPERTY_VALUES']['CATEGORY'],
                                'IS_SET' => $product['PROPERTY_VALUES']['IS_SET'],
                                'LENGTH' => $product['PROPERTY_VALUES']['LENGTH'],
                                'DIAMETR' => $product['PROPERTY_VALUES']['DIAMETR'],
                                'UNITS_PER_DAY' => $product['PROPERTY_VALUES']['UNITS_PER_DAY'],
                                'REQ_CALCULATION' => $product['PROPERTY_VALUES']['REQ_CALCULATION'],
                            ],
                        ]);

                        if ($elementId) {
                            \CCatalogProduct::Add([
                                "ID" => $elementId,
                                "MEASURE" => $measureId,
                                "VAT_ID" => 1,
                                "VAT_INCLUDED" => "Y",
                                "CAN_BUY_ZERO" => "Y",
                            ]);
                        }
                    }
                }
            }
        }

        // создаем и обновляем товары для которых не нашлись характеристики
        $this->importProducts();

        $obSets = new \SB\Sets();
        $setsProductsIds = $obSets->getProductsIdsList();
        $deactivateFilter = [
            'IBLOCK_ID' => CATALOG_IBLOCK_ID,
            '<TIMESTAMP_X' => date('d.m.Y H:i:s', $timestampStart - 1)
        ];
        if ($setsProductsIds) {
            $deactivateFilter['!ID'] = $setsProductsIds;
        }
        // деактивируем товары, которых не было в выгрузке
        $rsElement = \CIBlockElement::GetList(
            [],
            $deactivateFilter,
            false,
            false,
            ['ID']
        );
        while ($arElement = $rsElement->Fetch()) {
            $obElement->Update($arElement['ID'], ['ACTIVE' => 'N']);
        }
    }

    protected function importPropsValues($propsValues)
    {
        $propsValuesBySku = [];
        foreach ($propsValues as $propsValue) {
            if ($propsValue['Characteristic'] && $propsValue['Characteristic'] != '') {
                $propsValuesBySku[$propsValue['Characteristic']][] = $propsValue;
            }
        }

        foreach ($propsValuesBySku as $skuXmlId => $skuPropsValues) {
            $newSkuPropsValues = [];
            foreach ($skuPropsValues as $skuPropValue) {
                if ($this->propsCodes[$skuPropValue['Property']]) {
                    $newSkuPropsValues[$this->propsCodes[$skuPropValue['Property']]] = $skuPropValue['Value'];
                }
            }

            if ($newSkuPropsValues) {
                foreach ($this->propsCodes as $propCode) {
                    if (!array_key_exists($propCode, $newSkuPropsValues)) {
                        $newSkuPropsValues[$propCode] = '';
                    }
                }

                $rsElement = \CIBlockElement::GetList(
                    array('ID' => 'ASC'),
                    [
                        'IBLOCK_ID' => CATALOG_IBLOCK_ID,
                        '%XML_ID' => $skuXmlId,
                    ],
                    false,
                    false,
                    [
                        'ID',
                        'NAME',
                        'XML_ID',
                    ]
                );
                while ($sku = $rsElement->fetch()) {
                    \CIBlockElement::SetPropertyValuesEx(
                        $sku['ID'],
                        CATALOG_IBLOCK_ID,
                        $newSkuPropsValues
                    );
                }
            }
        }
    }

    protected function getMeasureIdByName($measureName)
    {
        $measureName = mb_strtolower(trim($measureName, ' .'));
        if (!$this->measuresList[$measureName]) {
            $rsMeasures = \CCatalogMeasure::getList(
                [],
                [
                    'SYMBOL_RUS' => $measureName,
                ]
            );
            if ($arMeasure = $rsMeasures->fetch()) {
                $this->measuresList[$measureName] = $arMeasure['ID'];
            }
        }

        return $this->measuresList[$measureName];
    }

    protected function generatePropCode($name)
    {
        $obProperty = new \CIBlockProperty();
        $code = $this->safeTranslit($name);
        if (preg_match('/^[0-9]/', $code))
            $code = '_' . $code;

        $rsProperty = $obProperty->GetList(array(), array("IBLOCK_ID" => CATALOG_IBLOCK_ID, "CODE" => $code));
        if ($arDBProperty = $rsProperty->Fetch()) {
            $suffix = 0;
            do {
                $suffix++;
                $rsProperty = $obProperty->GetList(array(), array("IBLOCK_ID" => CATALOG_IBLOCK_ID, "CODE" => $code . "_" . $suffix));
            } while ($rsProperty->Fetch());
            $code .= '_' . $suffix;
        }

        return rtrim($code, '_');
    }

    protected function safeTranslit($str)
    {
        $params = array(
            "max_len" => 50,
            "change_case" => 'U', // 'L' - toLower, 'U' - toUpper, false - do not change
            "replace_space" => '_',
            "replace_other" => '_',
            "delete_repeat_replace" => true,
        );

        $result = \CUtil::translit($str, "ru", $params);
        $result = preg_replace("/[^a-zA-Z0-9_]/", $params["replace_other"], $result);
        if ($params["delete_repeat_replace"])
            $result = preg_replace("/" . preg_quote($params["replace_other"], "/") . "+/", $params["replace_other"], $result);

        return $result;
    }

    public function getUniqueProductCodeByName($name): string
    {
        $uniqueCode = '';
        $iteration = 0;
        while (!$uniqueCode) {
            $code = \SB\Tools\ForString::transliterate($name) . ($iteration ? '-' . $iteration : '');
            if (!$this->existByCode($code)) {
                $uniqueCode = $code;
                break;
            }

            $iteration++;
        }

        return $uniqueCode;
    }

    protected function existByCode($code): bool
    {
        $existSection = \CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => CATALOG_IBLOCK_ID, 'CODE' => $code],
            false,
            ['ID']
        )->Fetch();
        if ($existSection) {
            return true;
        }

        return false;
    }

    protected function getProductByXmlId($xmlID)
    {
        $arElement = \CIBlockElement::GetList(
            array('ID' => 'ASC'),
            [
                'IBLOCK_ID' => CATALOG_IBLOCK_ID,
                '=XML_ID' => $xmlID,
            ],
            false,
            false,
            [
                'ID',
                'NAME',
                'XML_ID',
            ]
        )->fetch();
        return $arElement;
    }
}

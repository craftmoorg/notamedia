<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
Loader::IncludeModule('iblock');

class AdminUsersReport extends \CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable
{
    public $filter = array();
    public $order = array();

    public function executeComponent()
    {
        if (!empty(intval($this->arParams['IBLOCK_ID']))){
            $this->prepareResultData();
            try {
                $this->setHeaders();
                $this->initFilter();
                $this->prepareFilter();
                $this->getData();
                $this->includeComponentTemplate();
            }
            catch (\Exception $e){
                ShowError($e->getMessage());
            }
        }
    }

    protected function prepareResultData()
    {
        $this->arResult['GRID_ID'] = __CLASS__ .'_grid';
        $this->arResult['FILTER_ID'] = __CLASS__ . '_filter';
        $this->arResult['DATA'] = array();
    }

    protected function setHeaders()
    {
        $data = [];
        $data[] = array(
            'id' => 'NAME',
            'name' => 'Наименование компании',
            'sort' => 'NAME',
            'default' => true,
        );
        $data[] = array(
            'id' => 'COMPANY_NAME',
            'name' => 'ФИО представителя',
            'sort' => 'COMPANY_NAME',
            'default' => true,
        );
        $data[] = array(
            'id' => 'PHONE',
            'name' => 'Номер телефона',
            'sort' => 'PHONE',
            'default' => true,
        );
        $data[] = array(
            'id' => 'EMAIL',
            'name' => 'E-mail',
            'sort' => 'EMAIL',
            'default' => true,
        );
        $data[] = array(
            'id' => 'POST',
            'name' => 'Должность',
            'sort' => 'POST',
            'default' => true,
        );
        $data[] = array(
            'id' => 'DETAIL_TEXT',
            'name' => 'Описание',
            'sort' => false,
            'default' => true,
        );
        $this->arResult['HEADERS'] = $data;
    }

    protected function initFilter()
    {
        $this->arResult['FILTER_ID'] = __CLASS__ . '_filter';
        $this->arResult['FILTER_FIELDS'] = [];

        $this->arResult['FILTER_FIELDS']['NAME'] = array(
            'name' => 'Наименование компании',
            'id' => '%NAME',
            'default' => 'Y',
            'type' => 'text',
        );

        $this->arResult['FILTER_FIELDS']['COMPANY_NAME'] = [
            'name' => 'ФИО представителя',
            'id' => 'PROPERTY_COMPANY_NAME',
            'default' => 'Y',
            'type' => 'text',
        ];

        $this->arResult['FILTER_FIELDS']['PHONE'] = array(
            'name' => 'Номер телефона',
            'id' => 'PROPERTY_PHONE',
            'default' => 'Y',
            'type' => 'number',
        );

        $this->arResult['FILTER_FIELDS']['EMAIL'] = array(
            'name' => 'E-mail',
            'id' => 'PROPERTY_EMAIL',
            'default' => 'Y',
            'type' => 'text',
        );

        $this->arResult['FILTER_FIELDS']['POST'] = array(
            'name' => 'Номер телефона',
            'id' => 'PROPERTY_POST',
            'default' => 'Y',
            'type' => 'text',
        );
    }

    protected function prepareFilter()
    {
        $arFilter = array();
        if($this->arResult['FILTER_ID']) {
            $filterOptions = new \Bitrix\Main\UI\Filter\Options($this->arResult['FILTER_ID']);
            $filterFields = $filterOptions->getFilter();
            $filterFields = $filterOptions->getFilter($this->arResult['FILTER_FIELDS']);
            $dateFilter = self::getDateLogicFilter($filterFields);
            $preparedFilterFields = $filterOptions->getFilterLogic($this->arResult['FILTER_FIELDS']);
            foreach ($preparedFilterFields as $key => $value) {
                if (!empty($value) || (is_string($value) && strlen($value) > 0)) {
                    $arFilter[$key] = $value;
                }
            }
            if (is_array($dateFilter) && !empty($dateFilter)) {
                $arFilter = array_merge($arFilter, $dateFilter);
            }

            $arFilter['IBLOCK_ID'] = $this->arParams['IBLOCK_ID'];
            $this->filter = $arFilter;
        }
    }

    protected function getData()
    {
        $this->setOrders();
        $arData = array();
        $rsElements = CIBlockElement::GetList(
            $arOrder = $this->order,
            $arFilter = $this->filter,
            $arGroupBy = false,
            $arNavStartParams = false,
            $arSelectFields = array(
                'ID',
                'NAME',
                'DETAIL_TEXT',
                'DETAIL_PAGE_URL',
                'PROPERTY_COMPANY_NAME',
                'PROPERTY_PHONE',
                'PROPERTY_EMAIL',
                'PROPERTY_POST',
            )
        );
        while($element = $rsElements->GetNext())
        {
            $arData[$element['ID']]['HEADERS'] = array(
                'NAME' => '<a href="'.$element['DETAIL_PAGE_URL'].'">'.$element['NAME'].'</a>',
                'COMPANY_NAME' => $element['PROPERTY_COMPANY_NAME_VALUE'],
                'PHONE' => '<a href="mailto:'.$element['PROPERTY_PHONE_VALUE'].'">'.$element['PROPERTY_PHONE_VALUE'].'</a>',
                'EMAIL' => '<a href="mailto:'.$element['PROPERTY_EMAIL_VALUE'].'">'.$element['PROPERTY_EMAIL_VALUE'].'</a>',
                'POST' => $element['PROPERTY_POST_VALUE'],
                'DETAIL_TEXT' => $element['DETAIL_TEXT'],
            );
        }

        foreach ($arData as $data) {
            $items[] = array(
                'data' => $data['HEADERS'],
                'actions' => [],
            );
        }
        $this->arResult['DATA'] = $items;
    }

    protected function setOrders(){
        if(isset($_GET['by']) && isset($_GET['order'])){
            $this->order = [$_GET['by'] => $_GET['order']];
        }
        else{
            $this->order = array('ID' => 'ASC');
        }
    }

    public function configureActions()
    {
        return array();
    }

    public static function getDateLogicFilter(array $data)
    {
        $filter = array();
        $keys = array_filter($data, function($key) { return (mb_substr($key, 0 - mb_strlen(\Bitrix\Main\UI\Filter\DateType::getPostfix())) == \Bitrix\Main\UI\Filter\DateType::getPostfix()); }, ARRAY_FILTER_USE_KEY);
        foreach ($keys as $key => $val)
        {
            $id = mb_substr($key, 0, 0 - mb_strlen(\Bitrix\Main\UI\Filter\DateType::getPostfix()));
            if (array_key_exists($id."_from", $data))
                $filter[">=".$id] = $data[$id."_from"];
            if (array_key_exists($id."_to", $data))
                $filter["<=".$id] = $data[$id."_to"];
        }
        return $filter;
    }
}

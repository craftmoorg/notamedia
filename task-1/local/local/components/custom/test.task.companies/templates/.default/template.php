<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die(); ?>

<?
$APPLICATION->IncludeComponent(
    'bitrix:main.ui.filter',
    '',
    [
        'FILTER_ID' => $arResult['FILTER_ID'],
        'GRID_ID' => $arResult['GRID_ID'],
        'FILTER' => isset($arResult['FILTER_FIELDS']) ? $arResult['FILTER_FIELDS'] : array(),
        'ENABLE_LIVE_SEARCH' => false,
        'ENABLE_LABEL' => true,
    ]
);
?>

<?
$APPLICATION->IncludeComponent(
    'bitrix:main.ui.grid',
    '',
    [
        'GRID_ID' => $arResult['GRID_ID'],
        'COLUMNS' => $arResult['HEADERS'],
        'ROWS' => $arResult['DATA'],
        'NAV_OBJECT' => $arResult['NAV'],
        'AJAX_MODE' => 'Y',
        'AJAX_OPTION_JUMP'          => 'N',
        'AJAX_OPTION_HISTORY'       => 'N',
        'SHOW_CHECK_ALL_CHECKBOXES' => false,
        'SHOW_ROW_CHECKBOXES'       => false,
        'SHOW_ROW_ACTIONS_MENU'     => false,
        'SHOW_GRID_SETTINGS_MENU'   => false,
        'SHOW_NAVIGATION_PANEL'     => false,
        'SHOW_PAGINATION'           => false,
        'SHOW_SELECTED_COUNTER'     => false,
        'SHOW_TOTAL_COUNTER'        => false,
        'SHOW_PAGESIZE'             => false,
        'TOTAL_ROWS_COUNT'          => false,
        'SHOW_ACTION_PANEL'         => false,
        'ACTION_PANEL'              => array(),
        'ALLOW_COLUMNS_SORT'        => true,
        'ALLOW_COLUMNS_RESIZE'      => true,
        'ALLOW_HORIZONTAL_SCROLL'   => true,
        'ALLOW_SORT'                => true,
        'ALLOW_PIN_HEADER'          => false,
        'ALLOW_VALIDATE'            => false,
        'HANDLE_RESPONSE_ERRORS'    => false,
        'PAGE_SIZES' => false
    ]
);
?>

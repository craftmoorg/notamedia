<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$arResult['HIDE_TEXT'] = false;
if (mb_strlen($arResult['DETAIL_TEXT']) > 150){
    $arResult['HIDE_TEXT'] = true;
}
<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(true);

include_once (__DIR__.'/addInfoBlock.php');

$APPLICATION->IncludeComponent(
    'custom:test.task.companies',
    '.default',
    array(
        'IBLOCK_ID' => $arParams["IBLOCK_ID"]
    )
);
?>

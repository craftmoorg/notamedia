<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(true);
?>

<div class="container">
    <div class="company-details">
        <a class="toggle-button" href="<?=$arResult['LIST_PAGE_URL']?>">Назад</a>
        <h2><?=$arResult['NAME']?></h2>
        <p>ФИО представителя: <?=$arResult['PROPERTIES']['COMPANY_NAME']['VALUE']?></p>
        <p>Номер телефона: <?=$arResult['PROPERTIES']['PHONE']['VALUE']?></p>
        <p>E-mail: <?=$arResult['PROPERTIES']['EMAIL']['VALUE']?></p>
        <p>Должность: <?=$arResult['PROPERTIES']['EMAIL']['VALUE']?></p>
        <?if($arResult['HIDE_TEXT']):?>
        <div class="description hide">
            <p>Описание:</p>
            <p><?=$arResult['DETAIL_TEXT']?></p>
        </div>
        <button id="toggle-hide" class="toggle-button"">Раскрыть описание</button>
        <?else:?>
        <div class="description">
            <p>Описание:</p>
            <p><?=$arResult['DETAIL_TEXT']?></p>
        </div>
        <?endif;?>
    </div>
</div>

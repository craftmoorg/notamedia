<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
use Bitrix\Main\Loader;
Loader::IncludeModule('iblock');
$APPLICATION->SetTitle("Компании");
?>

<div class="container">
        <?
        $APPLICATION->IncludeComponent(
            "bitrix:news",
            "test.task.complex",
            array(
                "IBLOCK_ID" => \Bitrix\Iblock\IblockTable::getList(['filter' => ['CODE' => 'companies']])->Fetch()['ID'],
                "NEWS_COUNT" => "10",
                "USE_SEARCH" => "N",
                "USE_RSS" => "N",
                "USE_RATING" => "N",
                "USE_CATEGORIES" => "N",
                "USE_FILTER" => "N",
                "SORT_BY1" => "ACTIVE_FROM",
                "SORT_ORDER1" => "DESC",
                "SORT_BY2" => "SORT",
                "SORT_ORDER2" => "ASC",
                "CHECK_DATES" => "Y",
                "AJAX_MODE" => "N",
                "AJAX_OPTION_JUMP" => "N",
                "AJAX_OPTION_STYLE" => "Y",
                "AJAX_OPTION_HISTORY" => "N",
                "CACHE_TYPE" => "A",
                "CACHE_TIME" => "3600",
                "CACHE_FILTER" => "Y",
                "CACHE_GROUPS" => "N",
                "SET_STATUS_404" => "Y",
                "SET_TITLE" => "Y",
                "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
                "ADD_SECTIONS_CHAIN" => "N",
                "ADD_ELEMENT_CHAIN" => "Y",
                "USE_PERMISSIONS" => "N",
                "PREVIEW_TRUNCATE_LEN" => "250",
                "LIST_ACTIVE_DATE_FORMAT" => "j%F",
                "LIST_FIELD_CODE" => array(
                ),
                "LIST_PROPERTY_CODE" => array(
                ),
                "HIDE_LINK_WHEN_NO_DETAIL" => "Y",
                "DISPLAY_NAME" => "Y",
                "META_KEYWORDS" => "-",
                "META_DESCRIPTION" => "-",
                "BROWSER_TITLE" => "-",
                "DETAIL_ACTIVE_DATE_FORMAT" => "j%F",
                "DETAIL_FIELD_CODE" => array(
                ),
                "DETAIL_PROPERTY_CODE" => array(
                    "COMPANY_NAME",
                    "EMAIL",
                    "PHONE",
                    "POST",
                ),
                "DETAIL_DISPLAY_TOP_PAGER" => "N",
                "DETAIL_DISPLAY_BOTTOM_PAGER" => "Y",
                "DETAIL_PAGER_TITLE" => "",
                "DETAIL_PAGER_TEMPLATE" => "",
                "DETAIL_PAGER_SHOW_ALL" => "N",
                "PAGER_TEMPLATE" => "",
                "DISPLAY_TOP_PAGER" => "N",
                "DISPLAY_BOTTOM_PAGER" => "Y",
                "PAGER_TITLE" => "",
                "PAGER_SHOW_ALWAYS" => "N",
                "PAGER_DESC_NUMBERING" => "N",
                "PAGER_DESC_NUMBERING_CACHE_TIME" => "0",
                "PAGER_SHOW_ALL" => "N",
                "DISPLAY_DATE" => "N",
                "DISPLAY_PICTURE" => "N",
                "DISPLAY_PREVIEW_TEXT" => "N",
                "USE_SHARE" => "N",
                "FILTER_NAME" => "arrFilter",
                "AJAX_OPTION_ADDITIONAL" => "",
                "COMPONENT_TEMPLATE" => "news",
                "SET_LAST_MODIFIED" => "N",
                "DETAIL_SET_CANONICAL_URL" => "N",
                "PAGER_BASE_LINK_ENABLE" => "N",
                "SHOW_404" => "Y",
                "MESSAGE_404" => "",
                "FILE_404" => "",
                "COMPOSITE_FRAME_MODE" => "A",
                "COMPOSITE_FRAME_TYPE" => "AUTO",
                "SEF_MODE" => "Y",
                "SEF_FOLDER" => "/companies/",
                "SEF_URL_TEMPLATES" => array(
                    "news" => "",
                    "section" => "",
                    "detail" => "#ELEMENT_CODE#/",
                )
            ),
            false
        );
        ?>
</div>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
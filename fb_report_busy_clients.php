<?php
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
$_SERVER["DOCUMENT_ROOT"] = '/home/bitrix/www';
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
set_time_limit(0);
ini_set('memory_limit', '1G');
global $DB;

while (ob_get_level())
{
    ob_end_clean();
}

\Bitrix\Main\Loader::IncludeModule('crm');
$arTopicInterestValues = getEnumValues($GLOBALS['USER_FIELD_MANAGER']->GetUserFields('CRM_DEAL')['UF_CRM_5C90BE0456665']['ID']);
$arCompaniesData = [];
$dbResCompanies = \CCrmCompany::GetListEx(
    $arOrder = ['DATE_CREATE' => 'DESC'],
    $arFilter = [
        'CHECK_PERMISSIONS' => 'N',
        'LOGIC' => 'OR',
        [
            'LOGIC' => 'AND',
            [
                '!UF_CRM_1574229631' => '01.01.1900 00:00:00',
            ],
            [
                '!UF_CRM_1574229631' => false,
            ]
        ],
        [
            'LOGIC' => 'AND',
            [
                '!UF_CRM_1574229663' => '01.01.1900 00:00:00',
            ],
            [
                '!UF_CRM_1574229663' => false,
            ]
        ],
    ],
    $arGroupBy = false,
    $arNavStartParams = false,
    $arSelect = [
        'ID', // Название компании
        'TITLE', // Название компании
        'UF_CRM_5CE6A47ABFDA1', // УНП
        'UF_BSCS', // #ID клиента (регномер)
        'UF_CRM_1574229631', // Дата последней сделки
        'UF_CRM_1574229663', // Дата последней активности
    ]
);
echo 'Получаем компании...'.PHP_EOL;
$i = 1;
while ($company = $dbResCompanies->Fetch()) {
    echo $i.PHP_EOL;
    if ($company['ID']){
        $arTopicInteres = [];
        $dbResDeals = \CCrmDeal::GetListEx(
            $arOrder = ['ID' => 'DESC'],
            $arFilter = [
                'COMPANY_ID' => $company['ID'],
                'CHECK_PERMISSIONS' => 'N',
            ],
            $arGroupBy = false,
            $arNavStartParams = false,
            $arSelect = [
                'UF_CRM_5C90BE0456665', // Интерес к
            ]
        );
        if ($deal = $dbResDeals->Fetch()) {
            if (!empty($deal['UF_CRM_5C90BE0456665'])){
                foreach ($deal['UF_CRM_5C90BE0456665'] as $id){
                    $arTopicInteres[] = $arTopicInterestValues[$id];
                }
            }
        }
    }

    $title = (empty($company['TITLE']) ? '' : $company['TITLE']);
    $UNP = (empty($company['UF_CRM_5CE6A47ABFDA1']) ? '' : $company['UF_CRM_5CE6A47ABFDA1']);
    $BSCS = (empty($company['UF_BSCS']) ? '' : $company['UF_BSCS']);
    $dateLastDeal = (empty($company['UF_CRM_1574229631']) ? '' : date('Y-m-d', strtotime($company['UF_CRM_1574229631'])));
    $dateLastActivity = (empty($company['UF_CRM_1574229631']) ? '' : date('Y-m-d', strtotime($company['UF_CRM_1574229663'])));
    $interes = (empty($arTopicInteres) ? '' : implode(', ', $arTopicInteres));
    $arCompaniesData[] = [$title, $UNP, $BSCS, $dateLastDeal, $dateLastActivity, $interes];
    $i++;
}

if (!empty($arCompaniesData)){
    echo 'Создаем отчет...'.PHP_EOL;
    $i = 1;

    $filePath = $_SERVER['DOCUMENT_ROOT'].'/upload/ftp_export/managed_base.csv';
    $fp = fopen($filePath, 'w+');
    fclose($fp);
    $csvFile = new \CCSVData($fieldsType = 'R', false);
    $csvFile->SetDelimiter(';');
    $csvFile->SetFirstHeader(true);
    $csvHeader = ['Название компании', 'УНП', 'ID клиента (регномер)', 'Дата последней сделки', 'Дата последней активности', 'Интерес к'];
    $csvFile->SaveFile($filePath, $csvHeader);
    foreach ($arCompaniesData as $row){
        echo $i.PHP_EOL;
        $csvFile->SaveFile($filePath, $row);
        $i++;

    }
}


function getEnumValues($enumId)
{
    $arTopicInterest = [];
    if (!empty($enumId)){
        $obEnum = new \CUserFieldEnum;
        $rsEnum = $obEnum->GetList([], ['USER_FIELD_ID' => $enumId]);
        while($arEnum = $rsEnum->Fetch()){
            $arTopicInterest[$arEnum['ID']] = $arEnum['VALUE'];
        }
    }

    return $arTopicInterest;
}

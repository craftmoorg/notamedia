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
$filePath = $_SERVER['DOCUMENT_ROOT'].'/upload/updateCompanies.csv';
$i = 1;
if (($handle = fopen($filePath, 'r')) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        var_dump($i);
        file_put_contents($_SERVER['DOCUMENT_ROOT'].'/upload/updateCompanies.log', $i);
        $assignedId = getAssignedId(trim($data[0]));
        $companyId = trim($data[1]);
        $openedFlag = (trim(mb_strtoupper($data[2])) === 'ДА' ? 'Y' : 'N');
        if (!empty($assignedId) && !empty($companyId) && !empty($openedFlag)) {
            $DB->Query('UPDATE b_crm_company SET ASSIGNED_BY_ID=' . intval($assignedId) . ', OPENED="' . $openedFlag . '" WHERE ID=' . intval($companyId));
        }
        $i++;
    }
    fclose($handle);
}

function getAssignedId($nameString)
{
    $assignedId = '';
    if (!empty($nameString)){
        $arFullName = explode(' ', $nameString);
        $userFilter = ['NAME' => $arFullName[0]];
        if(count($arFullName) > 1){
            $userFilter = ['NAME' => $arFullName[1], 'LAST_NAME' => $arFullName[0], '<>SECOND_NAME' => (!empty($arFullName[2]) ? $arFullName[2] : '')];
        }
        $rsUsers = \CUser::GetList([],[], $userFilter, ['SELECT' => ['ID']]);
        if($users = $rsUsers->GetNext()) {
            $assignedId = $users['ID'];
        }
    }

    return $assignedId;
}

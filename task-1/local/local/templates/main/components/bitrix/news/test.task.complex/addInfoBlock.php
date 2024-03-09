<?php
use Bitrix\Main\Loader;
Loader::IncludeModule('iblock');

class AddCompanyInfoblock
{
    public static function up()
    {
        $iblockId = Bitrix\Iblock\IblockTable::getList(['filter' => ['CODE' => 'companies']])->Fetch()['ID'];
        $csvFilePath = __DIR__.'/companies.csv';
        if (empty($iblockId) && file_exists($csvFilePath)){
            $iblockId = self::createInfoblock();
            self::createProperties($iblockId);
            self::fillInfoBlock($iblockId, $csvFilePath);
        }
    }

    protected static function createInfoblock()
    {
        $arFields = Array(
            "ACTIVE" => "Y",
            "NAME" => "Компании",
            "CODE" => "companies",
            "IBLOCK_TYPE_ID" => 'news',
            "SITE_ID" => 's1',
            "SORT" => "5",
            "GROUP_ID" => array(
                "1" => "X",
                "2" => "X",
                "3" => "X",
                "4" => "X",
            ),
            "FIELDS" => array(
                "CODE" => array(
                    "IS_REQUIRED" => "N", // Обязательное
                    "DEFAULT_VALUE" => array(
                        "UNIQUE" => "Y", // Проверять на уникальность
                        "TRANSLITERATION" => "Y", // Транслитерировать
                        "TRANS_LEN" => "30", // Максмальная длина транслитерации
                        "TRANS_CASE" => "L", // Приводить к нижнему регистру
                        "TRANS_SPACE" => "-", // Символы для замены
                        "TRANS_OTHER" => "-",
                        "TRANS_EAT" => "Y",
                        "USE_GOOGLE" => "N",
                    ),
                ),
                "DETAIL_TEXT_TYPE" => array(
                    "DEFAULT_VALUE" => "text",
                ),
                "SECTION_DESCRIPTION_TYPE" => array(
                    "DEFAULT_VALUE" => "text",
                ),
                "IBLOCK_SECTION" => array(
                    "IS_REQUIRED" => "N",
                ),
            ),
            "LIST_PAGE_URL" => "/companies/",
            "SECTION_PAGE_URL" => "",
            "DETAIL_PAGE_URL" => "/companies/#ELEMENT_CODE#/",
            "INDEX_ELEMENT" => "Y",
            "VERSION" => 1,

            "ELEMENT_NAME" => "Компания",
            "ELEMENTS_NAME" => "Компании",
            "ELEMENT_ADD" => "Добавить Компанию",
            "ELEMENT_EDIT" => "Изменить Компанию",
            "ELEMENT_DELETE" => "Удалить Компанию",
        );

        $iblock = new CIBlock;
        $iblockId = $iblock->Add($arFields);

        return $iblockId;
    }

    protected static function createProperties($iblockId)
    {
        $properties = array(
            array(
                'NAME' => 'ФИО представителя',
                'ACTIVE' => 'Y',
                'SORT' => '100',
                'CODE' => 'COMPANY_NAME',
                'PROPERTY_TYPE' => 'S',
                'IBLOCK_ID' => $iblockId,
            ),
            array(
                'NAME' => 'Номер телефона',
                'ACTIVE' => 'Y',
                'SORT' => '200',
                'CODE' => 'PHONE',
                'PROPERTY_TYPE' => 'S',
                'IBLOCK_ID' => $iblockId,
            ),
            array(
                'NAME' => 'E-mail',
                'ACTIVE' => 'Y',
                'SORT' => '300',
                'CODE' => 'EMAIL',
                'PROPERTY_TYPE' => 'S',
                'IBLOCK_ID' => $iblockId,
            ),
            array(
                'NAME' => 'Должность',
                'ACTIVE' => 'Y',
                'SORT' => '400',
                'CODE' => 'POST',
                'PROPERTY_TYPE' => 'S',
                'IBLOCK_ID' => $iblockId,
            ),
        );

        foreach ($properties as $property) {
            $ibp = new CIBlockProperty;
            $ibp->Add($property);
        }
    }

    protected static function fillInfoBlock($iblockId, $csvFilePath)
    {
        if (($handle = fopen($csvFilePath, "r")) !== false) {
            $i = 0;
            while (($data = fgetcsv($handle, 20000, ";")) !== false) {
                if ($i > 0){
                    $fields = [
                        'IBLOCK_ID' => $iblockId,
                        'NAME' => $data[0],
                        'CODE' => CUtil::translit($data[0], 'ru'),
                        'ACTIVE' => 'Y',
                        'DETAIL_TEXT' => $data[5],
                        'PROPERTY_VALUES' => [
                            'COMPANY_NAME' => $data[1],
                            'PHONE' => $data[2],
                            'EMAIL' => $data[3],
                            'POST' => $data[4],
                        ],
                    ];

                    $element = new CIBlockElement;
                    $element->Add($fields);
                }
                $i++;
            }
            fclose($handle);
        }
    }
}

AddCompanyInfoblock::up();
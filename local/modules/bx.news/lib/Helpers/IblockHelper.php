<?php

namespace BX\News\Helpers;

use BX\News\Helper;
use CIBlock;
use CIBlockProperty;
use CIBlockPropertyEnum;
use Exception;

class IblockHelper extends Helper
{
    /**
     * Сохраняет свойство инфоблока
     * Создаст если не было, обновит если существует и отличается
     * @param $iblockId
     * @param $fields , обязательные параметры - код свойства
     * @throws Exception
     * @return bool|mixed
     */
    public function saveProperty($iblockId, $fields)
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['CODE']);

        $exists = $this->getProperty($iblockId, $fields['CODE']);
        $exportExists = $this->prepareExportProperty($exists);
        $fields = $this->prepareExportProperty($fields);

        if (empty($exists)) {
            return $this->addProperty($iblockId, $fields);
        }

        if ($this->hasDiff($exportExists, $fields)) {
            return $this->updatePropertyById($exists['ID'], $fields);
        }

        return $exists['ID'];
    }

    /**
     * Получает свойство инфоблока
     * @param $iblockId
     * @param $code int|array - код или фильтр
     * @return array|bool
     */
    public function getProperty($iblockId, $code)
    {
        /** @compatibility filter or code */
        $filter = is_array($code) ? $code : [
            'CODE' => $code,
        ];

        $filter['IBLOCK_ID'] = $iblockId;
        $filter['CHECK_PERMISSIONS'] = 'N';
        /* do not use =CODE in filter */
        $property = CIBlockProperty::GetList(['SORT' => 'ASC'], $filter)->Fetch();
        return $this->prepareProperty($property);
    }

    /**
     * Получает значения списков для свойств инфоблоков
     * @param array $filter
     * @return array
     */
    public function getPropertyEnums($filter = [])
    {
        $result = [];
        $dbres = CIBlockPropertyEnum::GetList([
            'SORT' => 'ASC',
            'VALUE' => 'ASC',
        ], $filter);
        while ($item = $dbres->Fetch()) {
            $result[] = $item;
        }
        return $result;
    }

    /**
     * Получает значения списков для свойства инфоблока
     * @param $iblockId
     * @param $propertyId
     * @return array
     */
    public function getPropertyEnumValues($iblockId, $propertyId)
    {
        return $this->getPropertyEnums([
            'IBLOCK_ID' => $iblockId,
            'PROPERTY_ID' => $propertyId,
        ]);
    }

    /**
     * Получает свойство инфоблока
     * @param $iblockId
     * @param $code int|array - код или фильтр
     * @return int
     */
    public function getPropertyId($iblockId, $code)
    {
        $item = $this->getProperty($iblockId, $code);
        return ($item && isset($item['ID'])) ? $item['ID'] : 0;
    }

    /**
     * Получает свойства инфоблока
     * @param $iblockId
     * @param array $filter
     * @return array
     */
    public function getProperties($iblockId, $filter = [])
    {
        $filter['IBLOCK_ID'] = $iblockId;
        $filter['CHECK_PERMISSIONS'] = 'N';

        $filterIds = false;
        if (isset($filter['ID']) && is_array($filter['ID'])) {
            $filterIds = $filter['ID'];
            unset($filter['ID']);
        }

        $dbres = CIBlockProperty::GetList(['SORT' => 'ASC'], $filter);

        $result = [];

        while ($property = $dbres->Fetch()) {
            if ($filterIds) {
                if (in_array($property['ID'], $filterIds)) {
                    $result[] = $this->prepareProperty($property);
                }
            } else {
                $result[] = $this->prepareProperty($property);
            }
        }
        return $result;
    }

    /**
     * Добавляет свойство инфоблока если его не существует
     * @param $iblockId
     * @param $fields , обязательные параметры - код свойства
     * @throws Exception
     * @return bool
     */
    public function addPropertyIfNotExists($iblockId, $fields)
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['CODE']);

        $property = $this->getProperty($iblockId, $fields['CODE']);
        if ($property) {
            return $property['ID'];
        }

        return $this->addProperty($iblockId, $fields);

    }

    /**
     * Добавляет свойство инфоблока
     * @param $iblockId
     * @param $fields
     * @throws Exception
     * @return int|void
     */
    public function addProperty($iblockId, $fields)
    {

        $default = [
            'NAME' => '',
            'ACTIVE' => 'Y',
            'SORT' => '500',
            'CODE' => '',
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE' => '',
            'ROW_COUNT' => '1',
            'COL_COUNT' => '30',
            'LIST_TYPE' => 'L',
            'MULTIPLE' => 'N',
            'IS_REQUIRED' => 'N',
            'FILTRABLE' => 'Y',
            'LINK_IBLOCK_ID' => 0,
        ];

        if (!empty($fields['VALUES'])) {
            $default['PROPERTY_TYPE'] = 'L';
        }

        if (!empty($fields['LINK_IBLOCK_ID'])) {
            $default['PROPERTY_TYPE'] = 'E';
        }

        $fields = array_replace_recursive($default, $fields);

        if (false !== strpos($fields['PROPERTY_TYPE'], ':')) {
            list($ptype, $utype) = explode(':', $fields['PROPERTY_TYPE']);
            $fields['PROPERTY_TYPE'] = $ptype;
            $fields['USER_TYPE'] = $utype;
        }

        if (false !== strpos($fields['LINK_IBLOCK_ID'], ':')) {
            $fields['LINK_IBLOCK_ID'] = $this->getIblockIdByUid($fields['LINK_IBLOCK_ID']);
        }

        $fields['IBLOCK_ID'] = $iblockId;

        $ib = new CIBlockProperty;
        $propertyId = $ib->Add($fields);

        if ($propertyId) {
            return $propertyId;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    /**
     * Удаляет свойство инфоблока если оно существует
     * @param $iblockId
     * @param $code
     * @throws Exception
     * @return bool|void
     */
    public function deletePropertyIfExists($iblockId, $code)
    {
        $property = $this->getProperty($iblockId, $code);
        if (!$property) {
            return false;
        }

        return $this->deletePropertyById($property['ID']);

    }

    /**
     * Удаляет свойство инфоблока
     * @param $propertyId
     * @throws Exception
     * @return bool|void
     */
    public function deletePropertyById($propertyId)
    {
        $ib = new CIBlockProperty;
        if ($ib->Delete($propertyId)) {
            return true;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    /**
     * Обновляет свойство инфоблока если оно существует
     * @param $iblockId
     * @param $code
     * @param $fields
     * @throws Exception
     * @return bool|int|void
     */
    public function updatePropertyIfExists($iblockId, $code, $fields)
    {
        $property = $this->getProperty($iblockId, $code);
        if (!$property) {
            return false;
        }
        return $this->updatePropertyById($property['ID'], $fields);
    }

    /**
     * Обновляет свойство инфоблока
     * @param $propertyId
     * @param $fields
     * @throws Exception
     * @return int|void
     */
    public function updatePropertyById($propertyId, $fields)
    {
        if (!empty($fields['VALUES']) && !isset($fields['PROPERTY_TYPE'])) {
            $fields['PROPERTY_TYPE'] = 'L';
        }

        if (!empty($fields['LINK_IBLOCK_ID']) && !isset($fields['PROPERTY_TYPE'])) {
            $fields['PROPERTY_TYPE'] = 'E';
        }

        if (false !== strpos($fields['PROPERTY_TYPE'], ':')) {
            list($ptype, $utype) = explode(':', $fields['PROPERTY_TYPE']);
            $fields['PROPERTY_TYPE'] = $ptype;
            $fields['USER_TYPE'] = $utype;
        }

        if (false !== strpos($fields['LINK_IBLOCK_ID'], ':')) {
            $fields['LINK_IBLOCK_ID'] = $this->getIblockIdByUid($fields['LINK_IBLOCK_ID']);
        }

        if (isset($fields['VALUES']) && is_array($fields['VALUES'])) {
            $existsEnums = $this->getPropertyEnums([
                'PROPERTY_ID' => $propertyId,
            ]);

            $newValues = [];
            foreach ($fields['VALUES'] as $index => $item) {
                foreach ($existsEnums as $existsEnum) {
                    if ($existsEnum['XML_ID'] == $item['XML_ID']) {
                        $item['ID'] = $existsEnum['ID'];
                        break;
                    }
                }

                if (!empty($item['ID'])) {
                    $newValues[$item['ID']] = $item;
                } else {
                    $newValues['n' . $index] = $item;
                }

            }

            $fields['VALUES'] = $newValues;
        }


        $ib = new CIBlockProperty();
        if ($ib->Update($propertyId, $fields)) {
            return $propertyId;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    /**
     * Получает свойство инфоблока
     * Данные подготовлены для экспорта в миграцию или схему
     * @param $iblockId
     * @param bool $code
     * @throws Exception
     * @return array|void
     */
    public function exportProperty($iblockId, $code = false)
    {
        $export = $this->prepareExportProperty(
            $this->getProperty($iblockId, $code)
        );

        if (!empty($export['CODE'])) {
            return $export;
        }

        $this->throwException(
            __METHOD__,
            ''
            /*Locale::getMessage(
                'ERR_IB_PROPERTY_CODE_NOT_FOUND'
            )*/
        );
    }

    /**
     * Получает свойства инфоблока
     * Данные подготовлены для экспорта в миграцию или схему
     * @param $iblockId
     * @param array $filter
     * @return array
     */
    public function exportProperties($iblockId, $filter = [])
    {
        $exports = [];
        $items = $this->getProperties($iblockId, $filter);
        foreach ($items as $item) {
            if (!empty($item['CODE'])) {
                $exports[] = $this->prepareExportProperty($item);
            }
        }
        return $exports;
    }

    /**
     * @param $iblockId
     * @param $code
     * @throws Exception
     * @return bool
     * @deprecated
     */
    public function deleteProperty($iblockId, $code)
    {
        return $this->deletePropertyIfExists($iblockId, $code);
    }

    /**
     * @param $iblockId
     * @param $code
     * @param $fields
     * @throws Exception
     * @return bool|mixed
     * @deprecated
     */
    public function updateProperty($iblockId, $code, $fields)
    {
        return $this->updatePropertyIfExists($iblockId, $code, $fields);
    }

    protected function prepareProperty($property)
    {
        if ($property && $property['PROPERTY_TYPE'] == 'L' && $property['IBLOCK_ID'] && $property['ID']) {
            $property['VALUES'] = $this->getPropertyEnums([
                'IBLOCK_ID' => $property['IBLOCK_ID'],
                'PROPERTY_ID' => $property['ID'],
            ]);
        }
        return $property;
    }

    protected function prepareExportProperty($prop)
    {
        if (empty($prop)) {
            return $prop;
        }

        if (!empty($prop['VALUES']) && is_array($prop['VALUES'])) {
            $exportValues = [];

            foreach ($prop['VALUES'] as $item) {
                $exportValues[] = [
                    'VALUE' => $item['VALUE'],
                    'DEF' => $item['DEF'],
                    'SORT' => $item['SORT'],
                    'XML_ID' => $item['XML_ID'],
                ];
            }

            $prop['VALUES'] = $exportValues;
        }

        if (!empty($prop['LINK_IBLOCK_ID'])) {
            $prop['LINK_IBLOCK_ID'] = $this->getIblockUid($prop['LINK_IBLOCK_ID']);
        }

        unset($prop['ID']);
        unset($prop['IBLOCK_ID']);
        unset($prop['TIMESTAMP_X']);
        unset($prop['TMP_ID']);

        return $prop;
    }

    /**
     * Получает инфоблок, бросает исключение если его не существует
     * @param $code string|array - код или фильтр
     * @param string $typeId
     * @throws Exception
     * @return array|void
     */
    public function getIblockIfExists($code, $typeId = '')
    {
        $item = $this->getIblock($code, $typeId);
        if ($item && isset($item['ID'])) {
            return $item;
        }
        $this->throwException(
            __METHOD__,
            ''
            /*Locale::getMessage(
                'ERR_IB_NOT_FOUND'
            )*/
        );
    }

    /**
     * Получает id инфоблока, бросает исключение если его не существует
     * @param $code string|array - код или фильтр
     * @param string $typeId
     * @throws Exception
     * @return int|void
     */
    public function getIblockIdIfExists($code, $typeId = '')
    {
        $item = $this->getIblock($code, $typeId);
        if ($item && isset($item['ID'])) {
            return $item['ID'];
        }
        $this->throwException(
            __METHOD__, ''
            /*Locale::getMessage(
                'ERR_IB_NOT_FOUND'
            ) */
        );
    }

    /**
     * Получает инфоблок
     * @param $code int|string|array - id, код или фильтр
     * @param string $typeId
     * @return array|false
     */
    public function getIblock($code, $typeId = '')
    {
        if (is_array($code)) {
            $filter = $code;
        } elseif (is_numeric($code)) {
            $filter = ['ID' => $code];
        } else {
            $filter = ['=CODE' => $code];
        }

        if (!empty($typeId)) {
            $filter['=TYPE'] = $typeId;
        }

        $filter['CHECK_PERMISSIONS'] = 'N';

        $item = CIBlock::GetList(['SORT' => 'ASC'], $filter)->Fetch();
        return $this->prepareIblock($item);
    }

    /**
     * Получает список сайтов для инфоблока
     * @param $iblockId
     * @return array
     */
    public function getIblockSites($iblockId)
    {
        $dbres = CIBlock::GetSite($iblockId);
        return $this->fetchAll($dbres, false, 'LID');
    }

    /**
     * Получает id инфоблока
     * @param $code string|array - код или фильтр
     * @param string $typeId
     * @return int
     */
    public function getIblockId($code, $typeId = '')
    {
        $iblock = $this->getIblock($code, $typeId);
        return ($iblock && isset($iblock['ID'])) ? $iblock['ID'] : 0;
    }

    /**
     * Получает список инфоблоков
     * @param array $filter
     * @return array
     */
    public function getIblocks($filter = [])
    {
        $filter['CHECK_PERMISSIONS'] = 'N';

        $dbres = CIBlock::GetList(['SORT' => 'ASC'], $filter);
        $list = [];
        while ($item = $dbres->Fetch()) {
            $list[] = $this->prepareIblock($item);
        }
        return $list;
    }

    /**
     * Добавляет инфоблок если его не существует
     * @param array $fields , обязательные параметры - код, тип инфоблока, id сайта
     * @throws Exception
     * @return int|void
     */
    public function addIblockIfNotExists($fields = [])
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['CODE', 'IBLOCK_TYPE_ID', 'LID']);

        $typeId = false;
        if (!empty($fields['IBLOCK_TYPE_ID'])) {
            $typeId = $fields['IBLOCK_TYPE_ID'];
        }

        $iblock = $this->getIblock($fields['CODE'], $typeId);
        if ($iblock) {
            return $iblock['ID'];
        }

        return $this->addIblock($fields);
    }

    /**
     * Добавляет инфоблок
     * @param $fields , обязательные параметры - код, тип инфоблока, id сайта
     * @throws Exception
     * @return int|void
     */
    public function addIblock($fields)
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['CODE', 'IBLOCK_TYPE_ID', 'LID']);

        $default = [
            'ACTIVE' => 'Y',
            'NAME' => '',
            'CODE' => '',
            'LIST_PAGE_URL' => '',
            'DETAIL_PAGE_URL' => '',
            'SECTION_PAGE_URL' => '',
            'IBLOCK_TYPE_ID' => 'main',
            'LID' => ['s1'],
            'SORT' => 500,
            'GROUP_ID' => ['2' => 'R'],
            'VERSION' => 2,
            'BIZPROC' => 'N',
            'WORKFLOW' => 'N',
            'INDEX_ELEMENT' => 'N',
            'INDEX_SECTION' => 'N',
        ];

        $fields = array_replace_recursive($default, $fields);

        $ib = new CIBlock;
        $iblockId = $ib->Add($fields);

        if ($iblockId) {
            return $iblockId;
        }
        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    /**
     * Обновляет инфоблок
     * @param $iblockId
     * @param array $fields
     * @throws Exception
     * @return int|void
     */
    public function updateIblock($iblockId, $fields = [])
    {
        $ib = new CIBlock;
        if ($ib->Update($iblockId, $fields)) {
            return $iblockId;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);

    }

    /**
     * Обновляет инфоблок если он существует
     * @param $code
     * @param array $fields
     * @throws Exception
     * @return bool|int|void
     */
    public function updateIblockIfExists($code, $fields = [])
    {
        $iblock = $this->getIblock($code);
        if (!$iblock) {
            return false;
        }
        return $this->updateIblock($iblock['ID'], $fields);
    }

    /**
     * Удаляет инфоблок если он существует
     * @param $code
     * @param string $typeId
     * @throws Exception
     * @return bool|void
     */
    public function deleteIblockIfExists($code, $typeId = '')
    {
        $iblock = $this->getIblock($code, $typeId);
        if (!$iblock) {
            return false;
        }
        return $this->deleteIblock($iblock['ID']);
    }

    /**
     * Удаляет инфоблок
     * @param $iblockId
     * @throws Exception
     * @return bool|void
     */
    public function deleteIblock($iblockId)
    {
        if (CIBlock::Delete($iblockId)) {
            return true;
        }

        $this->throwException(
            __METHOD__, ''
            /*Locale::getMessage(
                'ERR_CANT_DELETE_IBLOCK', [
                    '#NAME#' => $iblockId,
                ]
            ) */
        );
    }

    /**
     * Получает список полей инфоблока
     * @param $iblockId
     * @return array|bool
     */
    public function getIblockFields($iblockId)
    {
        return CIBlock::GetFields($iblockId);
    }

    /**
     * Сохраняет инфоблок
     * Создаст если не было, обновит если существует и отличается
     * @param array $fields , обязательные параметры - код, тип инфоблока, id сайта
     * @throws Exception
     * @return bool|mixed
     */
    public function saveIblock($fields = [])
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['CODE', 'IBLOCK_TYPE_ID', 'LID']);

        $item = $this->getIblock($fields['CODE'], $fields['IBLOCK_TYPE_ID']);
        $exists = $this->prepareExportIblock($item);
        $fields = $this->prepareExportIblock($fields);

        if (empty($item)) {
            return $this->addIblock($fields);
        }

        if ($this->hasDiff($exists, $fields)) {
            return $this->updateIblock($item['ID'], $fields);
        }

        return $item['ID'];
    }

    /**
     * Сохраняет поля инфоблока
     * @param $iblockId
     * @param array $fields
     * @return bool
     */
    public function saveIblockFields($iblockId, $fields = [])
    {
        $exists = CIBlock::GetFields($iblockId);

        $exportExists = $this->prepareExportIblockFields($exists);
        $fields = $this->prepareExportIblockFields($fields);

        $fields = array_replace_recursive($exportExists, $fields);

        if (empty($exists)) {
            return $this->updateIblockFields($iblockId, $fields);
        }

        if ($this->hasDiff($exportExists, $fields)) {
            return $this->updateIblockFields($iblockId, $fields);
        }

        return true;
    }

    /**
     * Получает инфоблок
     * Данные подготовлены для экспорта в миграцию или схему
     * @param $iblockId
     * @throws Exception
     * @return array|void
     */
    public function exportIblock($iblockId)
    {
        $export = $this->prepareExportIblock(
            $this->getIblock($iblockId)
        );

        if (!empty($export['CODE'])) {
            return $export;
        }

        $this->throwException(
            __METHOD__, ''
            /*Locale::getMessage(
                'ERR_IB_CODE_NOT_FOUND'
            ) */
        );
    }

    /**
     * Получает список инфоблоков
     * Данные подготовлены для экспорта в миграцию или схему
     * @param array $filter
     * @return array
     */
    public function exportIblocks($filter = [])
    {
        $exports = [];
        $items = $this->getIblocks($filter);
        foreach ($items as $item) {
            if (!empty($item['CODE'])) {
                $exports[] = $this->prepareExportIblock($item);
            }
        }
        return $exports;
    }

    /**
     * Получает список полей инфоблока
     * Данные подготовлены для экспорта в миграцию или схему
     * @param $iblockId
     * @return array
     */
    public function exportIblockFields($iblockId)
    {
        return $this->prepareExportIblockFields(
            $this->getIblockFields($iblockId)
        );
    }

    /**
     * Обновляет поля инфоблока
     * @param $iblockId
     * @param $fields
     * @return bool
     */
    public function updateIblockFields($iblockId, $fields)
    {
        if ($iblockId && !empty($fields)) {
            CIBlock::SetFields($iblockId, $fields);
            return true;
        }
        return false;
    }

    /**
     * Получает права доступа к инфоблоку для групп
     * возвращает массив вида [$groupCode => $letter]
     *
     * @param $iblockId
     * @return array
     */
    public function getGroupPermissions($iblockId)
    {
        return CIBlock::GetGroupPermissions($iblockId);
    }

    /**
     * Устанавливает права доступа к инфоблоку для групп
     * предыдущие права сбрасываются
     * принимает массив вида [$groupCode => $letter]
     *
     * @param $iblockId
     * @param array $permissions
     */
    public function setGroupPermissions($iblockId, $permissions = [])
    {
        CIBlock::SetPermission($iblockId, $permissions);
    }

    /**
     * @param $iblockId
     * @param $fields
     * @deprecated
     */
    public function mergeIblockFields($iblockId, $fields)
    {
        $this->saveIblockFields($iblockId, $fields);
    }

    /**
     * @param $code
     * @param string $typeId
     * @throws HelperException
     * @return mixed
     * @deprecated
     */
    public function findIblockId($code, $typeId = '')
    {
        return $this->getIblockIdIfExists($code, $typeId);
    }

    /**
     * @param $code
     * @param string $typeId
     * @throws HelperException
     * @return mixed
     * @deprecated
     */
    public function findIblock($code, $typeId = '')
    {
        return $this->getIblockIfExists($code, $typeId);
    }

    /**
     * @param $iblock int|array
     * @param string $default
     * @return string
     */
    public function getIblockUid($iblock, $default = '')
    {
        if (!is_array($iblock)) {
            //на вход уже пришел uid
            if (false !== strpos($iblock, ':')) {
                return $iblock;
            }

            //на вход пришел id или код инфоблока
            $iblock = $this->getIblock($iblock);
        }

        if (!empty($iblock['IBLOCK_TYPE_ID']) && !empty($iblock['CODE'])) {
            return $iblock['IBLOCK_TYPE_ID'] . ':' . $iblock['CODE'];
        }

        return $default;
    }

    /**
     * @param $iblockUid
     * @return int
     */
    public function getIblockIdByUid($iblockUid)
    {
        $iblockId = 0;

        if (empty($iblockUid)) {
            return $iblockId;
        }

        list($type, $code) = explode(':', $iblockUid);
        if (!empty($type) && !empty($code)) {
            $iblockId = $this->getIblockId($code, $type);
        }

        return $iblockId;
    }

    /**
     * @param $item
     * @return mixed
     */
    protected function prepareIblock($item)
    {
        if (empty($item['ID'])) {
            return $item;
        }
        $item['LID'] = $this->getIblockSites($item['ID']);

        $messages = CIBlock::GetMessages($item['ID']);
        $item = array_merge($item, $messages);
        return $item;
    }

    protected function prepareExportIblockFields($fields)
    {
        if (empty($fields)) {
            return $fields;
        }

        $exportFields = [];
        foreach ($fields as $code => $field) {
            if ($field['VISIBLE'] == 'N' || preg_match('/^(LOG_)/', $code)) {
                continue;
            }
            $exportFields[$code] = $field;
        }

        return $exportFields;
    }

    protected function prepareExportIblock($iblock)
    {
        if (empty($iblock)) {
            return $iblock;
        }

        unset($iblock['ID']);
        unset($iblock['TIMESTAMP_X']);
        unset($iblock['TMP_ID']);

        return $iblock;
    }



    /**
     * IblockHelper constructor.
     */
    public function isEnabled()
    {
        return $this->checkModules(['iblock']);
    }

}
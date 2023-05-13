<?php

namespace BX\News\Helpers;

use BX\News\Helper;
use CAgent;
use Exception;


class AgentHelper extends Helper
{

    /**
     * Получает список агентов по фильтру
     * @param array $filter
     * @return array
     */
    public function getList($filter = [])
    {
        $res = [];
        $dbres = CAgent::GetList(["MODULE_ID" => "ASC"], $filter);
        while ($item = $dbres->Fetch()) {
            $res[] = $item;
        }
        return $res;
    }
    
    /**
     * Получает агента
     * @param $moduleId
     * @param string $name
     * @return array
     */
    public function getAgent($moduleId, $name = '')
    {
        $filter = is_array($moduleId) ? $moduleId : [
            'MODULE_ID' => $moduleId,
        ];

        if (!empty($name)) {
            $filter['NAME'] = $name;
        }

        return CAgent::GetList([
            "MODULE_ID" => "ASC",
        ], $filter)->Fetch();
    }

    /**
     * Удаляет агента
     * @param $moduleId
     * @param $name
     * @return bool
     */
    public function deleteAgent($moduleId, $name)
    {
        CAgent::RemoveAgent($name, $moduleId);
        return true;
    }

    /**
     * Удаляет агента если существует
     * @param $moduleId
     * @param $name
     * @return bool
     */
    public function deleteAgentIfExists($moduleId, $name)
    {
        $item = $this->getAgent($moduleId, $name);
        if (empty($item)) {
            return false;
        }

        return $this->deleteAgent($moduleId, $name);
    }

    /**
     * Сохраняет агента
     * Создаст если не было, обновит если существует и отличается
     * @param array $fields , обязательные параметры - id модуля, функция агента
     * @throws Exception
     * @return bool|mixed
     */
    public function saveAgent($fields = [])
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['MODULE_ID', 'NAME']);

        $exists = $this->getAgent([
            'MODULE_ID' => $fields['MODULE_ID'],
            'NAME' => $fields['NAME'],
        ]);

        $exportExists = $this->prepareExportAgent($exists);
        $fields = $this->prepareExportAgent($fields);

        if (empty($exists)) {
            return $this->addAgent($fields);
        }

        if (strtotime($fields['NEXT_EXEC']) <= strtotime($exportExists['NEXT_EXEC'])) {
            unset($fields['NEXT_EXEC']);
            unset($exportExists['NEXT_EXEC']);
        }

        if ($this->hasDiff($exportExists, $fields)) {
            return $this->updateAgent($fields);
        }
        
        return $exists['ID'];
    }


    /**
     * Обновление агента, бросает исключение в случае неудачи
     * @param $fields , обязательные параметры - id модуля, функция агента
     * @throws HelperException
     * @return bool
     */
    public function updateAgent($fields)
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['MODULE_ID', 'NAME']);
        $this->deleteAgent($fields['MODULE_ID'], $fields['NAME']);
        return $this->addAgent($fields);
    }

    /**
     * Создание агента, бросает исключение в случае неудачи
     * @param $fields , обязательные параметры - id модуля, функция агента
     * @throws HelperException
     * @return bool
     */
    public function addAgent($fields)
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['MODULE_ID', 'NAME']);

        global $DB;

        $fields = array_merge([
            'AGENT_INTERVAL' => 86400,
            'ACTIVE' => 'Y',
            'IS_PERIOD' => 'N',
            'NEXT_EXEC' => $DB->GetNowDate(),
        ], $fields);

        $agentId = CAgent::AddAgent(
            $fields['NAME'],
            $fields['MODULE_ID'],
            $fields['IS_PERIOD'],
            $fields['AGENT_INTERVAL'],
            '',
            $fields['ACTIVE'],
            $fields['NEXT_EXEC']
        );

        if ($agentId) {
            return $agentId;
        }

        $this->throwApplicationExceptionIfExists(__METHOD__);
        $this->throwException(
            __METHOD__,
            ''
            /*Locale::getMessage(
                'ERR_AGENT_NOT_ADDED',
                [
                    '#NAME#' => $fields['NAME'],
                ]
            )*/
        );
        return false;
    }

    /**
     * @param $moduleId
     * @param $name
     * @param $interval
     * @param $nextExec
     * @throws HelperException
     * @return bool|mixed
     * @deprecated
     */
    public function replaceAgent($moduleId, $name, $interval, $nextExec)
    {
        return $this->saveAgent([
            'MODULE_ID' => $moduleId,
            'NAME' => $name,
            'AGENT_INTERVAL' => $interval,
            'NEXT_EXEC' => $nextExec,
        ]);
    }

    /**
     * @param $moduleId
     * @param $name
     * @param $interval
     * @param $nextExec
     * @throws Exception
     * @return bool|mixed
     * @deprecated
     */
    public function addAgentIfNotExists($moduleId, $name, $interval, $nextExec)
    {
        return $this->saveAgent([
            'MODULE_ID' => $moduleId,
            'NAME' => $name,
            'AGENT_INTERVAL' => $interval,
            'NEXT_EXEC' => $nextExec,
        ]);
    }

    protected function prepareExportAgent($item)
    {
        if (empty($item)) {
            return $item;
        }

        unset($item['ID']);
        unset($item['LOGIN']);
        unset($item['USER_NAME']);
        unset($item['LAST_NAME']);
        unset($item['RUNNING']);
        unset($item['DATE_CHECK']);
        unset($item['LAST_EXEC']);

        return $item;
    }
}

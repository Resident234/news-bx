<?php

namespace BX\News\Integration;

use Exception;
use WpOrg\Requests\Requests;

class TasksService
{
    protected const SERVICE_ADDRESS = 'http://dev.docker.otr-soft.ru:92/api/clients/';
    const STATUS_WAIT = 'wait';// работа пока в очереди
    const STATUS_PROCESS = 'process';// в работе
    const STATUS_SUCCESS = 'success';// работа выполнена

    /**
     * Запрос на создание новой работы
     *
     * При обращении на Сервис ставится задача на обработку новости.
     * 
     *
     * @param string $workName наименование
     * @param string $workId и идентификатор работы
     * @return mixed идентификатор запроса
     * @throws Exception
     */
    public static function createWork(string $workName, string $workId)
    {
        $headers = array('Content-Type' => 'application/json');
        $workName = mb_convert_encoding($workName, "UTF-8", "Windows-1251");
        $data = array('workName' => $workName, 'workId' => $workId);
        $response = Requests::post(self::SERVICE_ADDRESS . 'create-work', $headers, json_encode($data, JSON_UNESCAPED_UNICODE));
        $body = json_decode($response->body, true);
        if ($body['success'] === true) {
            if (!$body['data']['requestId']) {
                throw new Exception('Некорректный requestId');
            }
            return $body['data']['requestId'];
        } else {
            throw new Exception("{$body['code']} {$body['error']}");
        }
    }

    /**
     * Далее нужно регулярно проверять статус каждой незакрытой задачи.
     *
     * @param string $requestId идентификатор запроса
     * @return mixed Информация о статусе обработки данного запроса.
     * @throws Exception
     */
    public static function checkWork(string $requestId)
    {
        $response = Requests::get(self::SERVICE_ADDRESS . 'check-work?requestId=' . $requestId);
        $body = json_decode($response->body, true);
        if ($body['success'] === true) {
            return $body['data']['status'];
        } else {
            throw new Exception("{$body['code']} {$body['error']}");
        }
    }
}
<?php

namespace BX\News\Integration;

use Exception;
use WpOrg\Requests\Requests;

class TasksService
{
    protected const SERVICE_ADDRESS = 'http://dev.docker.otr-soft.ru:92/api/clients/';


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
}
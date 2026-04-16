<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Context;
use Bitrix\Main\Config\Option;
use Mlk\Tgbotapi\Helper;

$request = Context::getCurrent()->getRequest();
$apiKey = $request->getQuery('key');
$code = $request->getQuery('code');

$moduleId = 'mlk.tgbotapi';

if (!\Bitrix\Main\Loader::includeModule($moduleId)) {
    http_response_code(500);
    echo json_encode(['error' => 'Module not available']);
    die();
}

$validKey = Option::get($moduleId, 'api_key', '');
if (empty($apiKey) || $apiKey !== $validKey) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid API key']);
    die();
}

if (empty($code)) {
    http_response_code(400);
    echo json_encode(['error' => 'Code parameter is required']);
    die();
}

try {
    $helper = new Helper();
    $result = $helper->getBannersByCode($code);
    header('Content-Type: application/json');
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
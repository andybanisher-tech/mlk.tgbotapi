<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Context;
use Bitrix\Main\Config\Option;
use Mlk\Tgbotapi\Helper;

$request = Context::getCurrent()->getRequest();
$apiKey = $request->getQuery('key');
$promoId = $request->getQuery('promoid');       // одиночный параметр
$promoIds = $request->getQuery('promoids');    // может быть массивом или строкой

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

// Подготавливаем список ID для поиска
$searchIds = [];
if (!empty($promoIds)) {
    if (is_array($promoIds)) {
        $searchIds = $promoIds;
    } elseif (is_string($promoIds)) {
        // поддержка строки с разделителями (запятая, пробел)
        $searchIds = preg_split('/[\s,]+/', $promoIds, -1, PREG_SPLIT_NO_EMPTY);
    }
} elseif (!empty($promoId)) {
    $searchIds = [$promoId];
}

if (empty($searchIds)) {
    http_response_code(400);
    echo json_encode(['error' => 'No promoid(s) provided']);
    die();
}

try {
    $helper = new Helper();
    $result = $helper->getPromosByCodes($searchIds);
    header('Content-Type: application/json');
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
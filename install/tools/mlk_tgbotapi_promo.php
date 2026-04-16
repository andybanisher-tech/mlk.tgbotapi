<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Context;
use Bitrix\Main\Config\Option;
use Mlk\Tgbotapi\Helper;

$request = Context::getCurrent()->getRequest();
$apiKey = $request->getQuery('key');
$promoId = $request->getQuery('promoid');

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

if (empty($promoId)) {
    http_response_code(400);
    echo json_encode(['error' => 'promoid parameter is required']);
    die();
}

try {
    $helper = new Helper();
    $promoData = $helper->getPromoByCode($promoId);
    if ($promoData === null) {
        http_response_code(404);
        echo json_encode(['error' => 'Promo not found']);
    } else {
        header('Content-Type: application/json');
        echo json_encode($promoData);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

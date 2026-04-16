<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arModuleVersion = array();
include __DIR__ . '/install/version.php';

$arModuleDescription = array(
    'NAME' => 'Модуль интеграции Telegram Bot API (контрагенты из HL)',
    'DESCRIPTION' => 'Модуль предоставляет REST API для получения баннеров из нескольких инфоблоков на основе групп контрагента из Highload-блока',
    'PARTNER_NAME' => 'mlk',
    'PARTNER_URI' => 'https://www.mirlk.ru',
    'VERSION' => $arModuleVersion['VERSION'],
    'VERSION_DATE' => $arModuleVersion['VERSION_DATE']
);
?>
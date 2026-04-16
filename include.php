<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

\Bitrix\Main\Loader::registerAutoLoadClasses('mlk.tgbotapi', [
    'Mlk\\Tgbotapi\\Helper' => 'lib/Helper.php',
]);
<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Application;

Loc::loadMessages(__FILE__);

class mlk_tgbotapi extends CModule
{
    public $MODULE_ID = 'mlk.tgbotapi';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    public function __construct()
    {
        $arModuleVersion = array();
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('MLK_TGBOTAPI_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('MLK_TGBOTAPI_MODULE_DESC');
        $this->PARTNER_NAME = 'mlk';
        $this->PARTNER_URI = 'https://www.mirlk.ru';
    }

    public function DoInstall()
    {
        global $APPLICATION;
        $this->InstallDB();
        $this->InstallFiles();
        ModuleManager::registerModule($this->MODULE_ID);
        LocalRedirect('/bitrix/admin/partner_modules.php?lang=' . LANGUAGE_ID);
    }

    public function DoUninstall()
    {
        global $APPLICATION;
        $this->UnInstallDB();
        $this->UnInstallFiles();
        ModuleManager::unRegisterModule($this->MODULE_ID);
        LocalRedirect('/bitrix/admin/partner_modules.php?lang=' . LANGUAGE_ID);
    }

    public function InstallDB()
    {
        Option::set($this->MODULE_ID, 'api_key', $this->generateApiKey());
        $defaultSettings = [
            'hl' => [
                'id' => 0,
                'code_field' => '',
                'name_field' => '',      // новое поле
                'group_field' => '',
                'group_separator' => ','
            ],
            'iblocks' => []
        ];
        Option::set($this->MODULE_ID, 'settings', json_encode($defaultSettings));
        return true;
    }

    public function UnInstallDB()
    {
        Option::delete($this->MODULE_ID);
        return true;
    }

    public function InstallFiles()
    {
        $docRoot = Application::getDocumentRoot();
        $sources = [
            __DIR__ . '/tools/mlk_tgbotapi_banner.php',
            __DIR__ . '/tools/mlk_tgbotapi_promo.php', // новый файл для промо
        ];
        foreach ($sources as $source) {
            $target = $docRoot . '/bitrix/tools/' . basename($source);
            if (file_exists($source)) {
                copy($source, $target);
            }
        }
        return true;
    }

    public function UnInstallFiles()
    {
        $files = [
            '/bitrix/tools/mlk_tgbotapi_banner.php',
            '/bitrix/tools/mlk_tgbotapi_promo.php',
        ];
        foreach ($files as $rel) {
            $path = Application::getDocumentRoot() . $rel;
            if (file_exists($path)) {
                unlink($path);
            }
        }
        return true;
    }

    private function generateApiKey($length = 32)
    {
        return bin2hex(random_bytes($length / 2));
    }
}
?>

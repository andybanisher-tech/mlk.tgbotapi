<?php
namespace Mlk\Tgbotapi;

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Application;
use CIBlockElement;
use CFile;

class Helper
{
    protected $moduleId = 'mlk.tgbotapi';
    protected $settings;

    public function __construct()
    {
        $this->loadSettings();
    }

    protected function loadSettings()
    {
        $json = Option::get($this->moduleId, 'settings', '');
        $this->settings = json_decode($json, true);
        if (!is_array($this->settings)) {
            $this->settings = [
                'hl' => [
                    'id' => 0,
                    'code_field' => '',
                    'name_field' => '',
                    'group_field' => '',
                    'bonus_field' => '',
                    'group_separator' => ','
                ],
                'iblocks' => []
            ];
        }
    }

    // ========== БАННЕРЫ ==========
    public function getBannersByCode($code)
    {
        $contragentData = $this->getContragentDataByCode($code);
        $banners = $this->getBannersForGroups($contragentData['segments'] ?? []);
        return [
            'contragent' => $contragentData ? [
                'code' => $contragentData['code'],
                'name' => $contragentData['name'],
                'segments' => $contragentData['segments']
            ] : null,
            'banners' => $banners
        ];
    }

    protected function getBannersForGroups($contragentGroups)
    {
        if (empty($this->settings['iblocks']) || !Loader::includeModule('iblock')) {
            return [];
        }

        $banners = [];

        foreach ($this->settings['iblocks'] as $iblockSettings) {
            $iblockId = (int)$iblockSettings['iblock_id'];
            if ($iblockId <= 0) continue;

            $filter = [
                'IBLOCK_ID' => $iblockId,
                'ACTIVE' => 'Y',
                'ACTIVE_DATE' => 'Y'
            ];

            $select = [
                'ID',
                'IBLOCK_ID',
                'DATE_ACTIVE_FROM',
                'DATE_ACTIVE_TO'
            ];

            $fieldName = $iblockSettings['field_name'] ?? 'NAME';
            $fieldDesc = $iblockSettings['field_description'] ?? 'PREVIEW_TEXT';
            $fieldImage = $iblockSettings['field_image'] ?? 'DETAIL_PICTURE';
            $fieldLink = $iblockSettings['field_link'] ?? '';
            $linkTemplate = $iblockSettings['link_template'] ?? '';

            $select[] = $fieldName;
            $select[] = $fieldDesc;
            $select[] = $fieldImage;
            if (!empty($fieldLink)) {
                $select[] = $fieldLink;
            }

            $propGroupId = $iblockSettings['property_group_id'];
            if ($propGroupId > 0) {
                $select[] = 'PROPERTY_' . $propGroupId;
            }

            $rsElements = CIBlockElement::GetList(['SORT' => 'ASC'], $filter, false, false, $select);
            while ($element = $rsElements->GetNext()) {
                $bannerGroups = [];
                if ($propGroupId > 0) {
                    $bannerGroups = $element['PROPERTY_' . $propGroupId . '_VALUE'];
                    if (!is_array($bannerGroups)) {
                        $bannerGroups = [];
                    }
                    $bannerGroups = array_map('intval', $bannerGroups);
                }

                $show = false;
                if (empty($contragentGroups)) {
                    if (empty($bannerGroups)) {
                        $show = true;
                    }
                } else {
                    if (empty($bannerGroups) || array_intersect($contragentGroups, $bannerGroups)) {
                        $show = true;
                    }
                }

                if (!$show) {
                    continue;
                }

                $link = '';
                if (!empty($fieldLink)) {
                    $linkValue = $this->extractFieldValue($element, $fieldLink);
                    if (!empty($linkTemplate)) {
                        $link = str_replace('{value}', $linkValue, $linkTemplate);
                    } else {
                        $link = $linkValue;
                    }
                }

                $banner = [
                    'id' => $element['ID'],
                    'iblock_id' => $element['IBLOCK_ID'],
                    'name' => $this->extractFieldValue($element, $fieldName),
                    'description' => $this->extractFieldValue($element, $fieldDesc),
                    'image' => $this->extractImageValue($element, $fieldImage),
                    'link' => $link,
                    'date_from' => $element['DATE_ACTIVE_FROM'],
                    'date_to' => $element['DATE_ACTIVE_TO'],
                    'segments' => array_values(array_intersect($contragentGroups, $bannerGroups)),
                ];
                $banners[] = $banner;
            }
        }

        return $banners;
    }

    // ========== КОНТРАГЕНТЫ ==========
    protected function getContragentDataByCode($code)
    {
        if (empty($code) || $this->settings['hl']['id'] <= 0 || empty($this->settings['hl']['code_field'])) {
            return null;
        }

        if (!Loader::includeModule('highloadblock')) {
            return null;
        }

        $hlId = $this->settings['hl']['id'];
        $hlData = \Bitrix\Highloadblock\HighloadBlockTable::getById($hlId)->fetch();
        if (!$hlData) {
            return null;
        }

        $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlData);
        $entityClass = $entity->getDataClass();

        $codeField = $this->settings['hl']['code_field'];
        $nameField = $this->settings['hl']['name_field'] ?: 'ID';
        $groupField = $this->settings['hl']['group_field'];

        $originalCode = trim($code);
        $originalCode = urldecode($originalCode);
        $rawDecoded = rawurldecode($originalCode);

        $row = $this->findContragentWithEncodingFallback($entityClass, $codeField, $nameField, $groupField, $originalCode);

        if (!$row && $rawDecoded != $originalCode) {
            $row = $this->findContragentWithEncodingFallback($entityClass, $codeField, $nameField, $groupField, $rawDecoded);
        }

        if (!$row) {
            return null;
        }

        $contragent = [
            'code' => $row[$codeField],
            'name' => $row[$nameField] ?? '',
            'segments' => []
        ];

        if (!empty($groupField) && isset($row[$groupField])) {
            $groupsData = $row[$groupField];
            $userField = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields('HLBLOCK_' . $hlId, $row['ID'])[$groupField] ?? null;
            if ($userField && $userField['MULTIPLE'] == 'Y') {
                $groups = is_array($groupsData) ? $groupsData : [];
            } else {
                $separator = $this->settings['hl']['group_separator'] ?: ',';
                $groups = explode($separator, (string)$groupsData);
            }
            $groups = array_map('intval', $groups);
            $groups = array_filter($groups);
            $contragent['segments'] = array_values($groups);
        }

        return $contragent;
    }

    protected function findContragentWithEncodingFallback($entityClass, $codeField, $selectField1, $selectField2, $searchCode)
    {
        $select = [$codeField];
        if (!empty($selectField1)) {
            $select[] = $selectField1;
        }
        if (!empty($selectField2)) {
            $select[] = $selectField2;
        }

        $variants = [$searchCode];

        $toWin = iconv('UTF-8', 'CP1251//IGNORE', $searchCode);
        if ($toWin && $toWin != $searchCode) {
            $variants[] = $toWin;
        }

        $toUtf = iconv('CP1251', 'UTF-8//IGNORE', $searchCode);
        if ($toUtf && $toUtf != $searchCode && $toUtf != $toWin) {
            $variants[] = $toUtf;
        }

        $likeVariants = array_map(function($v) { return '%' . $v . '%'; }, $variants);
        $allVariants = array_merge($variants, $likeVariants);
        $allVariants = array_unique($allVariants);

        foreach ($allVariants as $variant) {
            $filter = (strpos($variant, '%') !== false) 
                ? ['=%' . $codeField => $variant] 
                : ['=' . $codeField => $variant];

            $row = $entityClass::getList([
                'filter' => $filter,
                'select' => $select,
                'limit' => 1
            ])->fetch();

            if ($row) {
                return $row;
            }
        }

        return null;
    }

    // ========== БОНУСЫ ==========
    public function getBonusByCode($code)
    {
        if (empty($code) || $this->settings['hl']['id'] <= 0 || empty($this->settings['hl']['code_field']) || empty($this->settings['hl']['bonus_field'])) {
            return null;
        }

        if (!Loader::includeModule('highloadblock')) {
            return null;
        }

        $hlId = $this->settings['hl']['id'];
        $hlData = \Bitrix\Highloadblock\HighloadBlockTable::getById($hlId)->fetch();
        if (!$hlData) {
            return null;
        }

        $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlData);
        $entityClass = $entity->getDataClass();

        $codeField = $this->settings['hl']['code_field'];
        $bonusField = $this->settings['hl']['bonus_field'];

        $originalCode = trim($code);
        $originalCode = urldecode($originalCode);
        $rawDecoded = rawurldecode($originalCode);

        $row = $this->findContragentWithEncodingFallback($entityClass, $codeField, $bonusField, null, $originalCode);

        if (!$row && $rawDecoded != $originalCode) {
            $row = $this->findContragentWithEncodingFallback($entityClass, $codeField, $bonusField, null, $rawDecoded);
        }

        if (!$row) {
            return null;
        }

        $bonus = $row[$bonusField] ?? null;
        if (is_numeric($bonus)) {
            return (float)$bonus;
        }
        return $bonus;
    }

    // ========== ПРОМО (акции) ==========
    /**
     * Получение данных одного промо по внешнему коду (обратная совместимость)
     */
    public function getPromoByCode($promoId)
    {
        $result = $this->getPromosByCodes([$promoId]);
        return !empty($result) ? $result[0] : null;
    }

    /**
     * Получение данных нескольких промо по массиву внешних кодов
     * @param array $promoIds
     * @return array
     */
    public function getPromosByCodes(array $promoIds)
    {
        if (empty($promoIds) || empty($this->settings['iblocks'])) {
            return [];
        }

        if (!Loader::includeModule('iblock')) {
            return [];
        }

        $results = [];
        foreach ($promoIds as $promoId) {
            $found = null;
            foreach ($this->settings['iblocks'] as $iblockSettings) {
                $iblockId = (int)$iblockSettings['iblock_id'];
                if ($iblockId <= 0) continue;

                $promoField = $iblockSettings['field_promo_code'] ?? '';
                if (empty($promoField)) continue;

                $fieldName = $iblockSettings['field_name'] ?? 'NAME';
                $fieldImage = $iblockSettings['field_image'] ?? 'DETAIL_PICTURE';
                $fieldLink = $iblockSettings['field_link'] ?? '';
                $linkTemplate = $iblockSettings['link_template'] ?? '';

                $filter = [
                    'IBLOCK_ID' => $iblockId,
                    'ACTIVE' => 'Y',
                    'ACTIVE_DATE' => 'Y',
                ];

                if (strpos($promoField, 'PROPERTY_') === 0) {
                    $propCode = substr($promoField, 9);
                    $filter['=PROPERTY_' . $propCode] = $promoId;
                } else {
                    $filter['=' . $promoField] = $promoId;
                }

                $select = ['ID', 'IBLOCK_ID', $fieldName, $fieldImage];
                if (!empty($fieldLink)) {
                    $select[] = $fieldLink;
                }

                $rsElement = CIBlockElement::GetList([], $filter, false, false, $select);
                if ($element = $rsElement->GetNext()) {
                    $link = '';
                    if (!empty($fieldLink)) {
                        $linkValue = $this->extractFieldValue($element, $fieldLink);
                        if (!empty($linkTemplate)) {
                            $link = str_replace('{value}', $linkValue, $linkTemplate);
                        } else {
                            $link = $linkValue;
                        }
                    }

                    $found = [
                        'id' => $element['ID'],
                        'iblock_id' => $element['IBLOCK_ID'],
                        'name' => $this->extractFieldValue($element, $fieldName),
                        'image' => $this->extractImageValue($element, $fieldImage),
                        'link' => $link,
                    ];
                    break;
                }
            }
            if ($found) {
                $results[] = $found;
            }
        }
        return $results;
    }

    // ========== ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ ==========
    protected function extractFieldValue($element, $fieldCode)
    {
        if (empty($fieldCode)) {
            return null;
        }

        if (strpos($fieldCode, 'PROPERTY_') === 0) {
            $propKey = $fieldCode . '_VALUE';
            if (isset($element[$propKey])) {
                $value = $element[$propKey];
                if (is_array($value)) {
                    return reset($value);
                }
                return $value;
            }
            return null;
        }

        return $element[$fieldCode] ?? null;
    }

    protected function extractImageValue($element, $fieldCode)
    {
        if (empty($fieldCode)) {
            return null;
        }

        if (strpos($fieldCode, 'PROPERTY_') === 0) {
            $propKey = $fieldCode . '_VALUE';
            if (isset($element[$propKey])) {
                $fileId = $element[$propKey];
                if (is_array($fileId)) {
                    $fileId = reset($fileId);
                }
                return $this->getFileUrl($fileId);
            }
            return null;
        }

        if ($fieldCode == 'PREVIEW_PICTURE' || $fieldCode == 'DETAIL_PICTURE') {
            return $this->getFileUrl($element[$fieldCode]);
        }

        return null;
    }

    protected function getFileUrl($fileId)
    {
        if ($fileId > 0) {
            $file = CFile::GetFileArray($fileId);
            if ($file) {
                $proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
                return $proto . '://' . $_SERVER['HTTP_HOST'] . $file['SRC'];
            }
        }
        return null;
    }
}
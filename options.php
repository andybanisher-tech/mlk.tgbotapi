<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin.php';

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Context;

$module_id = 'mlk.tgbotapi';
Loader::includeModule($module_id);
Loader::includeModule('highloadblock');
Loader::includeModule('iblock');

$request = Context::getCurrent()->getRequest();
$backUrl = $request->get('back_url_settings');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$sessionKey = $module_id . '_form_data';
if (!isset($_SESSION[$sessionKey]) || !is_array($_SESSION[$sessionKey])) {
    $_SESSION[$sessionKey] = [];
}

$savedSettings = json_decode(Option::get($module_id, 'settings', '{}'), true);
if (!is_array($savedSettings)) {
    $savedSettings = [
        'hl' => ['id' => 0, 'code_field' => '', 'name_field' => '', 'group_field' => '', 'bonus_field' => '', 'group_separator' => ','],
        'iblocks' => []
    ];
}

$currentData = !empty($_SESSION[$sessionKey]) ? $_SESSION[$sessionKey] : $savedSettings;

$activeTab = $request->getPost('tabControl_active_tab') ?: $request->getQuery('tabControl_active_tab') ?: 'edit1';

$needSaveToSession = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid()) {
    if (isset($_POST['save']) || isset($_POST['generate_key'])) {
        $newData = [
            'hl' => [
                'id' => (int)$_POST['hl_id'],
                'code_field' => $_POST['hl_code_field'],
                'name_field' => $_POST['hl_name_field'],
                'group_field' => $_POST['hl_group_field'],
                'bonus_field' => $_POST['hl_bonus_field'],
                'group_separator' => $_POST['hl_group_separator'],
            ],
            'iblocks' => []
        ];

        $iblockIds = $_POST['iblock_id'] ?? [];
        if (is_array($iblockIds)) {
            foreach ($iblockIds as $index => $iblockId) {
                if (empty($iblockId)) continue;
                $newData['iblocks'][] = [
                    'iblock_id' => (int)$iblockId,
                    'property_group_id' => (int)$_POST['property_group_id'][$index],
                    'field_name' => $_POST['field_name'][$index],
                    'field_description' => $_POST['field_description'][$index],
                    'field_image' => $_POST['field_image'][$index],
                    'field_link' => $_POST['field_link'][$index],
                    'link_template' => $_POST['link_template'][$index],
                    'field_promo_code' => $_POST['field_promo_code'][$index],
                    'field_id_for_promo' => $_POST['field_id_for_promo'][$index],
                ];
            }
        }

        Option::set($module_id, 'settings', json_encode($newData));
        $_SESSION[$sessionKey] = [];

        if (isset($_POST['generate_key'])) {
            $newKey = bin2hex(random_bytes(16));
            Option::set($module_id, 'api_key', $newKey);
        }

        LocalRedirect($APPLICATION->GetCurPage() . '?mid=' . $module_id . '&lang=' . LANGUAGE_ID . '&tabControl_active_tab=' . urlencode($activeTab) . '&back_url_settings=' . urlencode($backUrl));
    }
    elseif (isset($_POST['add_iblock'])) {
        $currentData['iblocks'][] = [
            'iblock_id' => 0,
            'property_group_id' => 0,
            'field_name' => '',
            'field_description' => '',
            'field_image' => '',
            'field_link' => '',
            'link_template' => 'https://stalker-co.ru/stock/{value}/',
            'field_promo_code' => '',
            'field_id_for_promo' => '',
        ];
        $needSaveToSession = true;
    }
    elseif (isset($_POST['delete_iblock_index'])) {
        $deleteIndex = (int)$_POST['delete_iblock_index'];
        if (isset($currentData['iblocks'][$deleteIndex])) {
            array_splice($currentData['iblocks'], $deleteIndex, 1);
            $needSaveToSession = true;
        }
    }
    elseif (isset($_POST['change_hl'])) {
        $currentData['hl']['id'] = (int)$_POST['hl_id'];
        $currentData['hl']['code_field'] = $_POST['hl_code_field'];
        $currentData['hl']['name_field'] = $_POST['hl_name_field'];
        $currentData['hl']['group_field'] = $_POST['hl_group_field'];
        $currentData['hl']['bonus_field'] = $_POST['hl_bonus_field'];
        $currentData['hl']['group_separator'] = $_POST['hl_group_separator'];
        $needSaveToSession = true;
    }
    elseif (isset($_POST['change_iblock'])) {
        $index = (int)$_POST['iblock_index'];
        if (isset($currentData['iblocks'][$index])) {
            $currentData['iblocks'][$index]['iblock_id'] = (int)$_POST['iblock_id_' . $index];
        }
        $needSaveToSession = true;
    }

    if ($needSaveToSession) {
        $_SESSION[$sessionKey] = $currentData;
        LocalRedirect($APPLICATION->GetCurPage() . '?mid=' . $module_id . '&lang=' . LANGUAGE_ID . '&tabControl_active_tab=' . urlencode($activeTab) . '&back_url_settings=' . urlencode($backUrl));
    }
}

$apiKey = Option::get($module_id, 'api_key', '');

$hlBlocks = [];
$rsHL = \Bitrix\Highloadblock\HighloadBlockTable::getList(['order' => ['NAME' => 'ASC']]);
while ($hl = $rsHL->fetch()) {
    $hlBlocks[] = $hl;
}

$hlFields = [];
if ($currentData['hl']['id'] > 0) {
    $userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields('HLBLOCK_' . $currentData['hl']['id'], 0, LANGUAGE_ID);
    foreach ($userFields as $fieldName => $field) {
        $hlFields[$fieldName] = '[' . $fieldName . '] ' . ($field['EDIT_FORM_LABEL'] ?: $fieldName);
    }
}

$arTabs = [
    [
        'DIV' => 'edit1',
        'TAB' => Loc::getMessage('MLK_TGBOTAPI_TAB_HL'),
        'ICON' => '',
        'TITLE' => Loc::getMessage('MLK_TGBOTAPI_TAB_HL_TITLE')
    ],
    [
        'DIV' => 'edit2',
        'TAB' => Loc::getMessage('MLK_TGBOTAPI_TAB_IBLOCKS'),
        'ICON' => '',
        'TITLE' => Loc::getMessage('MLK_TGBOTAPI_TAB_IBLOCKS_TITLE')
    ],
    [
        'DIV' => 'edit3',
        'TAB' => Loc::getMessage('MLK_TGBOTAPI_TAB_API'),
        'ICON' => '',
        'TITLE' => Loc::getMessage('MLK_TGBOTAPI_TAB_API_TITLE')
    ]
];

$tabControl = new CAdminTabControl('tabControl', $arTabs, false, true);
$APPLICATION->SetTitle(Loc::getMessage('MLK_TGBOTAPI_TAB_HL_TITLE'));
?>
<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= $module_id ?>&lang=<?= LANGUAGE_ID ?>&back_url_settings=<?= urlencode($backUrl) ?>">
    <?= bitrix_sessid_post() ?>
    <input type="hidden" name="tabControl_active_tab" value="<?= htmlspecialcharsbx($activeTab) ?>">
    <? $tabControl->Begin(); ?>

    <? $tabControl->BeginNextTab(); ?>
    <table>
        <td width="40%"><?= Loc::getMessage('MLK_TGBOTAPI_HL_ID') ?>:</td>
        <td width="60%">
            <select name="hl_id" id="hl_id" onchange="this.form.change_hl.value='1'; this.form.submit()">
                <option value=""><?= Loc::getMessage('MLK_TGBOTAPI_SELECT_HL') ?></option>
                <?php foreach ($hlBlocks as $hl): ?>
                    <option value="<?= $hl['ID'] ?>" <?= ($currentData['hl']['id'] == $hl['ID']) ? 'selected' : '' ?>>
                        <?= htmlspecialcharsbx($hl['NAME']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="change_hl" value="">
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('MLK_TGBOTAPI_HL_CODE_FIELD') ?>:</td>
        <td>
            <select name="hl_code_field">
                <option value=""><?= Loc::getMessage('MLK_TGBOTAPI_SELECT_FIELD') ?></option>
                <?php foreach ($hlFields as $fieldCode => $fieldName): ?>
                    <option value="<?= htmlspecialcharsbx($fieldCode) ?>" <?= ($currentData['hl']['code_field'] == $fieldCode) ? 'selected' : '' ?>>
                        <?= htmlspecialcharsbx($fieldName) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('MLK_TGBOTAPI_HL_NAME_FIELD') ?>:</td>
        <td>
            <select name="hl_name_field">
                <option value=""><?= Loc::getMessage('MLK_TGBOTAPI_SELECT_FIELD') ?></option>
                <?php foreach ($hlFields as $fieldCode => $fieldName): ?>
                    <option value="<?= htmlspecialcharsbx($fieldCode) ?>" <?= ($currentData['hl']['name_field'] == $fieldCode) ? 'selected' : '' ?>>
                        <?= htmlspecialcharsbx($fieldName) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('MLK_TGBOTAPI_HL_GROUP_FIELD') ?>:</td>
        <td>
            <select name="hl_group_field">
                <option value=""><?= Loc::getMessage('MLK_TGBOTAPI_SELECT_FIELD') ?></option>
                <?php foreach ($hlFields as $fieldCode => $fieldName): ?>
                    <option value="<?= htmlspecialcharsbx($fieldCode) ?>" <?= ($currentData['hl']['group_field'] == $fieldCode) ? 'selected' : '' ?>>
                        <?= htmlspecialcharsbx($fieldName) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('MLK_TGBOTAPI_HL_BONUS_FIELD') ?>:</td>
        <td>
            <select name="hl_bonus_field">
                <option value=""><?= Loc::getMessage('MLK_TGBOTAPI_SELECT_FIELD') ?></option>
                <?php foreach ($hlFields as $fieldCode => $fieldName): ?>
                    <option value="<?= htmlspecialcharsbx($fieldCode) ?>" <?= ($currentData['hl']['bonus_field'] == $fieldCode) ? 'selected' : '' ?>>
                        <?= htmlspecialcharsbx($fieldName) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('MLK_TGBOTAPI_HL_GROUP_SEPARATOR') ?>:</td>
        <td>
            <input type="text" name="hl_group_separator" value="<?= htmlspecialcharsbx($currentData['hl']['group_separator']) ?>" size="10">
            <br><small><?= Loc::getMessage('MLK_TGBOTAPI_HL_GROUP_SEPARATOR_HINT') ?></small>
        </td>
    </tr>

    <? $tabControl->BeginNextTab(); ?>
    <tr>
        <td colspan="2">
            <div id="iblocks-container">
                <?php foreach ($currentData['iblocks'] as $index => $iblock): ?>
                    <?php include __DIR__ . '/iblock_template.php'; ?>
                <?php endforeach; ?>
            </div>
            <input type="submit" name="add_iblock" value="<?= Loc::getMessage('MLK_TGBOTAPI_ADD_IBLOCK') ?>">
        </td>
    </tr>

    <? $tabControl->BeginNextTab(); ?>
    <tr>
        <td width="40%"><?= Loc::getMessage('MLK_TGBOTAPI_API_KEY') ?>:</td>
        <td width="60%">
            <input type="text" name="api_key" value="<?= htmlspecialcharsbx($apiKey) ?>" size="60" readonly style="background:#f0f0f0;">
        </td>
    </tr>
    <tr>
        <td></td>
        <td>
            <input type="submit" name="generate_key" value="<?= Loc::getMessage('MLK_TGBOTAPI_GENERATE_KEY') ?>">
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <?= Loc::getMessage('MLK_TGBOTAPI_API_URL') ?>: <br>
            <code><?= 'https://' . $_SERVER['HTTP_HOST'] . '/bitrix/tools/' . $module_id . '_banner.php?key=' . urlencode($apiKey) . '&code={CODE}' ?></code>
        </td>
    </tr>

    <? $tabControl->Buttons(); ?>
    <input type="submit" name="save" value="<?= Loc::getMessage('MAIN_SAVE') ?>" class="adm-btn-save">
    <input type="reset" name="reset" value="<?= Loc::getMessage('MAIN_RESET') ?>">
    <? $tabControl->End(); ?>
</form>

<style>
.iblock-item {
    border: 1px solid #ccc;
    padding: 10px;
    margin-bottom: 10px;
    background: #f9f9f9;
}
.iblock-item h4 {
    margin-top: 0;
}
.remove-btn {
    float: right;
    cursor: pointer;
    color: red;
    font-weight: bold;
    background: none;
    border: none;
    font-size: 16px;
}
</style>

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
?>
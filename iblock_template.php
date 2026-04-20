<?php
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$index = isset($index) ? (int)$index : 0;
$iblock = isset($iblock) ? $iblock : [];
?>
<div class="iblock-item" id="iblock-item-<?= $index ?>">
    <button type="submit" name="delete_iblock_index" value="<?= $index ?>" class="remove-btn" onclick="return confirm('Удалить этот инфоблок?')">✖</button>
    <h4><?= Loc::getMessage('MLK_TGBOTAPI_IBLOCK_ITEM') ?> <?= $index+1 ?></h4>
    <table class="adm-detail-content-table edit-table">
        <tr>
            <td width="30%"><?= Loc::getMessage('MLK_TGBOTAPI_IBLOCK_ID') ?>:</td>
            <td>
                <?= GetIBlockDropDownList($iblock['iblock_id'] ?? 0, 'iblock_type_id_' . $index, 'iblock_id[' . $index . ']', 'class="iblock-select" id="iblock_id_' . $index . '" onchange="this.form.change_iblock.value=\'1\'; this.form.iblock_index.value=\'' . $index . '\'; this.form.submit()"') ?>
                <input type="hidden" name="iblock_index" value="<?= $index ?>">
                <input type="hidden" name="change_iblock" value="">
            </td>
        </tr>
        <tr>
            <td><?= Loc::getMessage('MLK_TGBOTAPI_PROPERTY_GROUP_ID') ?>:</td>
            <td>
                <select name="property_group_id[<?= $index ?>]">
                    <option value=""><?= Loc::getMessage('MLK_TGBOTAPI_SELECT_PROPERTY') ?></option>
                    <?php if (($iblock['iblock_id'] ?? 0) > 0): ?>
                        <?php
                        $props = CIBlockProperty::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $iblock['iblock_id'], 'ACTIVE' => 'Y']);
                        while ($prop = $props->Fetch()):
                            $selected = (($iblock['property_group_id'] ?? 0) == $prop['ID']) ? 'selected' : '';
                        ?>
                        <option value="<?= $prop['ID'] ?>" <?= $selected ?>><?= '[' . $prop['CODE'] . '] ' . $prop['NAME'] ?></option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td><?= Loc::getMessage('MLK_TGBOTAPI_FIELD_NAME') ?>:</td>
            <td>
                <select name="field_name[<?= $index ?>]">
                    <option value=""><?= Loc::getMessage('MLK_TGBOTAPI_SELECT_FIELD') ?></option>
                    <?php if (($iblock['iblock_id'] ?? 0) > 0): ?>
                        <?php
                        $fields = ['NAME', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'CODE'];
                        foreach ($fields as $f):
                            $selected = (($iblock['field_name'] ?? '') == $f) ? 'selected' : '';
                        ?>
                        <option value="<?= $f ?>" <?= $selected ?>><?= '[' . $f . '] ' . $f ?></option>
                        <?php endforeach; ?>
                        <?php
                        $props = CIBlockProperty::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $iblock['iblock_id'], 'ACTIVE' => 'Y']);
                        while ($prop = $props->Fetch()):
                            $selected = (($iblock['field_name'] ?? '') == 'PROPERTY_' . $prop['CODE']) ? 'selected' : '';
                        ?>
                        <option value="PROPERTY_<?= $prop['CODE'] ?>" <?= $selected ?>><?= '[' . $prop['CODE'] . '] ' . $prop['NAME'] ?></option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td><?= Loc::getMessage('MLK_TGBOTAPI_FIELD_DESCRIPTION') ?>:</td>
            <td>
                <select name="field_description[<?= $index ?>]">
                    <option value=""><?= Loc::getMessage('MLK_TGBOTAPI_SELECT_FIELD') ?></option>
                    <?php if (($iblock['iblock_id'] ?? 0) > 0): ?>
                        <?php
                        $fields = ['PREVIEW_TEXT', 'DETAIL_TEXT'];
                        foreach ($fields as $f):
                            $selected = (($iblock['field_description'] ?? '') == $f) ? 'selected' : '';
                        ?>
                        <option value="<?= $f ?>" <?= $selected ?>><?= '[' . $f . '] ' . $f ?></option>
                        <?php endforeach; ?>
                        <?php
                        $props = CIBlockProperty::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $iblock['iblock_id'], 'ACTIVE' => 'Y']);
                        while ($prop = $props->Fetch()):
                            $selected = (($iblock['field_description'] ?? '') == 'PROPERTY_' . $prop['CODE']) ? 'selected' : '';
                        ?>
                        <option value="PROPERTY_<?= $prop['CODE'] ?>" <?= $selected ?>><?= '[' . $prop['CODE'] . '] ' . $prop['NAME'] ?></option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td><?= Loc::getMessage('MLK_TGBOTAPI_FIELD_IMAGE') ?>:</td>
            <td>
                <select name="field_image[<?= $index ?>]">
                    <option value=""><?= Loc::getMessage('MLK_TGBOTAPI_SELECT_FIELD') ?></option>
                    <?php if (($iblock['iblock_id'] ?? 0) > 0): ?>
                        <?php
                        $fields = ['PREVIEW_PICTURE', 'DETAIL_PICTURE'];
                        foreach ($fields as $f):
                            $selected = (($iblock['field_image'] ?? '') == $f) ? 'selected' : '';
                        ?>
                        <option value="<?= $f ?>" <?= $selected ?>><?= '[' . $f . '] ' . $f ?></option>
                        <?php endforeach; ?>
                        <?php
                        $props = CIBlockProperty::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $iblock['iblock_id'], 'ACTIVE' => 'Y']);
                        while ($prop = $props->Fetch()):
                            if ($prop['PROPERTY_TYPE'] == 'F'):
                                $selected = (($iblock['field_image'] ?? '') == 'PROPERTY_' . $prop['CODE']) ? 'selected' : '';
                        ?>
                        <option value="PROPERTY_<?= $prop['CODE'] ?>" <?= $selected ?>><?= '[' . $prop['CODE'] . '] ' . $prop['NAME'] ?></option>
                        <?php endif; endwhile; ?>
                    <?php endif; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td><?= Loc::getMessage('MLK_TGBOTAPI_FIELD_LINK') ?>:</td>
            <td>
                <select name="field_link[<?= $index ?>]" id="field_link_<?= $index ?>" onchange="updateLinkPreview(<?= $index ?>)">
                    <option value=""><?= Loc::getMessage('MLK_TGBOTAPI_SELECT_FIELD') ?></option>
                    <?php if (($iblock['iblock_id'] ?? 0) > 0): ?>
                        <?php
                        $fields = ['CODE'];
                        foreach ($fields as $f):
                            $selected = (($iblock['field_link'] ?? '') == $f) ? 'selected' : '';
                        ?>
                        <option value="<?= $f ?>" <?= $selected ?>><?= '[' . $f . '] ' . $f ?></option>
                        <?php endforeach; ?>
                        <?php
                        $props = CIBlockProperty::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $iblock['iblock_id'], 'ACTIVE' => 'Y']);
                        while ($prop = $props->Fetch()):
                            if ($prop['PROPERTY_TYPE'] == 'S'):
                                $selected = (($iblock['field_link'] ?? '') == 'PROPERTY_' . $prop['CODE']) ? 'selected' : '';
                        ?>
                        <option value="PROPERTY_<?= $prop['CODE'] ?>" <?= $selected ?>><?= '[' . $prop['CODE'] . '] ' . $prop['NAME'] ?></option>
                        <?php endif; endwhile; ?>
                    <?php endif; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td><?= Loc::getMessage('MLK_TGBOTAPI_LINK_TEMPLATE') ?>:</td>
            <td>
                <input type="text" name="link_template[<?= $index ?>]" id="link_template_<?= $index ?>" value="<?= htmlspecialcharsbx($iblock['link_template'] ?? 'https://stalker-co.ru/stock/{value}/') ?>" size="60" onchange="updateLinkPreview(<?= $index ?>)">
                <br><small><?= Loc::getMessage('MLK_TGBOTAPI_LINK_TEMPLATE_HINT') ?></small>
            </td>
        </tr>
        <tr>
            <td><?= Loc::getMessage('MLK_TGBOTAPI_LINK_PREVIEW') ?>:</td>
            <td>
                <span id="link_preview_<?= $index ?>">-</span>
            </td>
        </tr>

        <!-- Поле для внешнего кода (promoid) -->
        <tr>
            <td><?= Loc::getMessage('MLK_TGBOTAPI_FIELD_PROMO_CODE') ?>:</td>
            <td>
                <select name="field_promo_code[<?= $index ?>]" id="field_promo_code_<?= $index ?>">
                    <option value=""><?= Loc::getMessage('MLK_TGBOTAPI_SELECT_FIELD') ?></option>
                    <?php if (($iblock['iblock_id'] ?? 0) > 0): ?>
                        <?php
                        $standardFields = [
                            'ID' => 'ID',
                            'NAME' => 'Название',
                            'CODE' => 'Символьный код',
                            'XML_ID' => 'Внешний код (XML_ID)',
                            'PREVIEW_TEXT' => 'Текст анонса',
                            'DETAIL_TEXT' => 'Детальный текст',
                            'SORT' => 'Сортировка',
                            'ACTIVE_FROM' => 'Дата начала',
                            'ACTIVE_TO' => 'Дата окончания',
                            'CREATED_BY' => 'Кем создан',
                            'MODIFIED_BY' => 'Кем изменен',
                            'DATE_CREATE' => 'Дата создания',
                            'TIMESTAMP_X' => 'Дата изменения',
                        ];
                        foreach ($standardFields as $code => $name):
                            $selected = (($iblock['field_promo_code'] ?? '') == $code) ? 'selected' : '';
                        ?>
                        <option value="<?= $code ?>" <?= $selected ?>><?= '[' . $code . '] ' . $name ?></option>
                        <?php endforeach; ?>
                        <?php
                        $props = CIBlockProperty::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $iblock['iblock_id'], 'ACTIVE' => 'Y']);
                        while ($prop = $props->Fetch()):
                            $selected = (($iblock['field_promo_code'] ?? '') == 'PROPERTY_' . $prop['CODE']) ? 'selected' : '';
                        ?>
                        <option value="PROPERTY_<?= $prop['CODE'] ?>" <?= $selected ?>><?= '[' . $prop['CODE'] . '] ' . $prop['NAME'] ?></option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
                <br><small><?= Loc::getMessage('MLK_TGBOTAPI_FIELD_PROMO_CODE_HINT') ?></small>
            </td>
        </tr>

        <!-- Поле для идентификатора (id) в ответе API -->
        <tr>
            <td><?= Loc::getMessage('MLK_TGBOTAPI_FIELD_ID_FOR_PROMO') ?>:</td>
            <td>
                <select name="field_id_for_promo[<?= $index ?>]" id="field_id_for_promo_<?= $index ?>">
                    <option value=""><?= Loc::getMessage('MLK_TGBOTAPI_SELECT_FIELD') ?> (ID элемента)</option>
                    <?php if (($iblock['iblock_id'] ?? 0) > 0): ?>
                        <?php
                        $standardFields = [
                            'ID' => 'ID (внутренний)',
                            'CODE' => 'Символьный код',
                            'XML_ID' => 'Внешний код',
                            'NAME' => 'Название',
                            'PREVIEW_TEXT' => 'Текст анонса',
                            'DETAIL_TEXT' => 'Детальный текст',
                            'SORT' => 'Сортировка',
                            'ACTIVE_FROM' => 'Дата начала',
                            'ACTIVE_TO' => 'Дата окончания',
                        ];
                        foreach ($standardFields as $code => $name):
                            $selected = (($iblock['field_id_for_promo'] ?? '') == $code) ? 'selected' : '';
                        ?>
                        <option value="<?= $code ?>" <?= $selected ?>><?= '[' . $code . '] ' . $name ?></option>
                        <?php endforeach; ?>
                        <?php
                        $props = CIBlockProperty::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $iblock['iblock_id'], 'ACTIVE' => 'Y']);
                        while ($prop = $props->Fetch()):
                            $selected = (($iblock['field_id_for_promo'] ?? '') == 'PROPERTY_' . $prop['CODE']) ? 'selected' : '';
                        ?>
                        <option value="PROPERTY_<?= $prop['CODE'] ?>" <?= $selected ?>><?= '[' . $prop['CODE'] . '] ' . $prop['NAME'] ?></option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
                <br><small><?= Loc::getMessage('MLK_TGBOTAPI_FIELD_ID_FOR_PROMO_HINT') ?></small>
            </td>
        </tr>
    </table>
</div>
<script>
function updateLinkPreview(index) {
    var template = document.getElementById('link_template_' + index).value;
    var field = document.getElementById('field_link_' + index);
    var selected = field.options[field.selectedIndex];
    if (!selected) return;
    var fieldCode = selected.value;
    if (!fieldCode) {
        document.getElementById('link_preview_' + index).innerText = '-';
        return;
    }
    var exampleValue = 'example';
    var preview = template.replace(/{value}/g, exampleValue);
    document.getElementById('link_preview_' + index).innerText = preview;
}
BX.ready(function() {
    <?php if (($iblock['iblock_id'] ?? 0) > 0): ?>
        updateLinkPreview(<?= $index ?>);
    <?php endif; ?>
});
</script>
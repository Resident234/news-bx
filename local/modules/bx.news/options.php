<?php

use \Bitrix\Main\Application;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Loader;

global $MESS;
include(GetLangFileName($GLOBALS['DOCUMENT_ROOT'] . '/bitrix/modules/im/lang/', '/options.php'));
IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/options.php');
define(MODULE_ID, 'bx.news');
define(MODULE_RIGHTS, $APPLICATION->GetGroupRight(MODULE_ID));
Loader::includeModule(MODULE_ID);
CModule::IncludeModule(MODULE_ID);
$aTabs = [
    [
        "DIV"   => "rights",
        "TAB"   => GetMessage("MAIN_TAB_RIGHTS"),
        "ICON"  => "main_settings",
        "TITLE" => GetMessage("MAIN_TAB_TITLE_RIGHTS"),
    ],
];
$tabControl = new CAdminTabControl('tabControl', $aTabs);
$request = Application::getInstance()->getContext()->getRequest();
?>
<form method="post"
      action="<? echo $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($mid) ?>&lang=<? echo LANG ?>">
    <?php echo bitrix_sessid_post() ?>
    <?php $tabControl->Begin();
    $tabControl->BeginNextTab(); ?>
    <? $tabControl->BeginNextTab(); ?>
    <? require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/admin/group_rights.php"); ?>
    <? $tabControl->Buttons(); ?>
    <script language="JavaScript">
        function RestoreDefaults() {
            if (confirm('<?echo AddSlashes(GetMessage('MAIN_HINT_RESTORE_DEFAULTS_WARNING'))?>'))
                window.location = "<?echo $APPLICATION->GetCurPage()?>?RestoreDefaults=Y&lang=<?echo LANG?>&mid=<?echo urlencode($mid) . "&" . bitrix_sessid_get();?>";
        }
    </script>
    <input type="submit" name="Update" <? if (MODULE_RIGHTS < 'W') echo "disabled" ?>
           value="<? echo GetMessage('MAIN_SAVE') ?>">
    <input type="reset" name="reset" value="<? echo GetMessage('MAIN_RESET') ?>">
    <?= bitrix_sessid_post(); ?>
    <input type="button" <? if (MODULE_RIGHTS < 'W') echo "disabled" ?>
           title="<? echo GetMessage('MAIN_HINT_RESTORE_DEFAULTS') ?>" OnClick="RestoreDefaults();"
           value="<? echo GetMessage('MAIN_RESTORE_DEFAULTS') ?>">
    <? $tabControl->End(); ?>
</form>
<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\UI\Extension;
use Awz\Admin\Access\AccessController;
Loc::loadMessages(__FILE__);

global $APPLICATION;
$module_id = "awz.admin";
if(!Loader::includeModule($module_id)) return;
Extension::load('ui.sidepanel-content');
$request = Application::getInstance()->getContext()->getRequest();
$APPLICATION->SetTitle(Loc::getMessage('AWZ_ADMIN_OPT_TITLE'));

if($request->get('IFRAME_TYPE')==='SIDE_SLIDER'){
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
    require_once('lib/access/include/moduleright.php');
    CMain::finalActions();
    die();
}

if(!AccessController::isViewSettings())
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
$saveUrl = $APPLICATION->GetCurPage(false).'?mid='.htmlspecialcharsbx($module_id).'&lang='.LANGUAGE_ID.'&mid_menu=1';

if ($request->getRequestMethod()==='POST' && AccessController::isEditSettings() && $request->get('Update'))
{
    Option::set($module_id, "ACTIVE_GEN", $_REQUEST["ACTIVE_GEN"]=="Y" ? "Y" : "N", "");
}
?>
<?
    $aTabs = array();

    $aTabs[] = array(
        "DIV" => "edit1",
        "TAB" => Loc::getMessage('AWZ_ADMIN_OPT_SECT1'),
        "ICON" => "vote_settings",
        "TITLE" => Loc::getMessage('AWZ_ADMIN_OPT_SECT1')
    );

    $tabControl = new CAdminTabControl("tabControl", $aTabs);
    $tabControl->Begin();
        ?>
    <style>.adm-workarea option:checked {background-color: rgb(206, 206, 206);}</style>
    <form method="POST" action="<?=$saveUrl?>" id="FORMACTION">
        <?
        $tabControl->BeginNextTab();
        ?>
        <?if(Loader::includeModule('iblock')){?>
            <tr>
                <td style="width:300px;"><?=Loc::getMessage('AWZ_ADMIN_OPT_ACTIVE_GEN')?></td>
                <td>
                    <?$val = Option::get($module_id, "ACTIVE_GEN", "N","");?>
                    <input type="checkbox" value="Y" name="ACTIVE_GEN" id="ACTIVE_GEN" <?if ($val=="Y") echo "checked";?>>
                </td>

            </tr>
        <?}?>

        <?
        $tabControl->Buttons();
        ?>
        <input <?if (!AccessController::isEditSettings()) echo "disabled" ?> type="submit" class="adm-btn-green" name="Update" value="<?=Loc::getMessage('AWZ_ADMIN_OPT_L_BTN_SAVE')?>" />
        <input type="hidden" name="Update" value="Y" />
        <?if(AccessController::isViewRight()){?>
            <button class="adm-header-btn adm-security-btn" onclick="BX.SidePanel.Instance.open('<?=$saveUrl?>');return false;">
                <?=Loc::getMessage('AWZ_ADMIN_OPT_SECT3')?>
            </button>
        <?}?>
        <?$tabControl->End();?>
    </form>



<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
<?

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

$moduleName = "openurbantech.telegram";
\Bitrix\Main\Loader::includeModule($moduleName);

IncludeModuleLangFile(__FILE__);

$tabsArray = [
    [
        "DIV" => "index",
        "TAB" => Loc::getMessage("OUT_TAB_TELEGRAM"),
        "ICON" => "",
        "TITLE" => Loc::getMessage("OUT_TAB_TELEGRAM_TITLE"),
        "OPTIONS" => [
            "telegram_bot_name" => [
                Loc::getMessage("OUT_TAB_TELEGRAM_BOT_NAME"),
                [
                    "text",
                    80
                ]
            ],
            "telegram_bot_token" => [
                Loc::getMessage("OUT_TAB_TELEGRAM_BOT_TOKEN"),
                [
                    "text",
                    80
                ]
            ],
            "telegram_stream_channel" => [
                Loc::getMessage("OUT_TELEGRAM_STREAM_CHANNEL"),
                [
                    "text",
                    80
                ]
            ],

        ]
    ],
];

$tabControl = new \CAdminTabControl("tabControl", $tabsArray);

if ($REQUEST_METHOD == "POST" && strlen($Update.$Apply.$RestoreDefaults) > 0 && check_bitrix_sessid()) {
    if (strlen($RestoreDefaults) > 0) {
        $defaults = Option::getDefaults($moduleName);
        foreach ($defaults as $name => $val) {
            Option::set($moduleName, $name, $val);
        }
    } else {
        foreach ($tabsArray as $i => $tabConfig) {
            foreach ($tabConfig["OPTIONS"] as $name => $option) {
                if ($name == 'DIVIDER') {
                    continue;
                }
                $disabled = array_key_exists("disabled", $option) ? $option["disabled"] : "";
                if ($disabled) {
                    continue;
                }
                $val = $_POST[$name];
                if ($option[1][0] == "checkbox" && $val != "Y") {
                    $val = "N";
                }
                Option::set($moduleName, $name, $val);
            }
        }
    }
    if (strlen($Update) > 0 && strlen($_REQUEST["back_url_settings"]) > 0) {
        LocalRedirect($_REQUEST["back_url_settings"]);
    } else {
        LocalRedirect(
            $APPLICATION->GetCurPage()."?mid=".urlencode($mid)."&lang=".urlencode(
                LANGUAGE_ID
            )."&back_url_settings=".urlencode($_REQUEST["back_url_settings"])."&".$tabControl->ActiveTabParam()
        );
    }
}

$tabControl->Begin();
?>
<form method="post" action="<?
echo $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($mid) ?>&amp;lang=<?= LANGUAGE_ID ?>">
    <?
    foreach ($tabsArray as $tabConfig):
        $tabControl->BeginNextTab();
        foreach ($tabConfig["OPTIONS"] as $name => $option):
            if ($name == 'DIVIDER') {
                switch ($option['TYPE']) {
                    case 'br':
                        ?>
                        <tr>
                            <td colspan="2"><br/></td>
                        </tr>
                        <?
                        break;
                    case 'hr':
                    default:
                        ?>
                        <tr>
                            <td colspan="2">
                                <hr/>
                            </td>
                        </tr>
                        <?
                        break;
                }
                continue;
            }
            $val = Option::get($moduleName, $name);
            $type = $option[1];
            $disabled = array_key_exists("disabled", $option) ? $option["disabled"] : "";
            ?>
            <tr>
                <td width="40%" nowrap <?
                if ($type[0] == "textarea") echo 'class="adm-detail-valign-top"' ?>>
                    <label for="<?
                    echo htmlspecialcharsbx($name) ?>"><?
                        echo $option[0] ?></label>
                <td width="60%">
                    <?
                    switch ($type[0]) {
                        case 'checkbox':
                            ?>
                            <input type="checkbox" name="<?
                        echo htmlspecialcharsbx($name) ?>"
                                   id="<?
                                   echo htmlspecialcharsbx($name) ?>" value="Y"<?
                            if ($val == "Y") {
                                echo " checked";
                            } ?><?
                            if ($disabled) {
                                echo ' disabled="disabled"';
                            } ?>><?
                            if ($disabled) {
                                echo '<br>'.$disabled;
                            } ?>
                            <?
                            break;
                        case 'text':
                            ?>
                            <input type="text" size="<?
                            echo $type[1] ?>" maxlength="255"
                                   value="<?
                                   echo htmlspecialcharsbx($val) ?>"
                                   name="<?
                                   echo htmlspecialcharsbx($name) ?>">
                            <?
                            break;
                        case 'date':
                            ?>
                            <input type="date" size="10" maxlength="255" value="<?
                            echo htmlspecialcharsbx($val) ?>"
                                   name="<?
                                   echo htmlspecialcharsbx($name) ?>">
                            <?
                            break;
                        case 'textarea':
                            ?>
                            <textarea rows="<?
                            echo $type[1] ?>" cols="<?
                            echo $type[2] ?> "
                                      name="<?
                                      echo htmlspecialcharsbx($name) ?>" style=
                                      "width:100%"><?
                                echo htmlspecialcharsbx($val) ?></textarea>
                            <?
                            break;
                        case 'selectbox':
                            ?>
                            <select name="<?
                            echo htmlspecialcharsbx($name) ?>">
                                <?
                                foreach ($type[1] as $key => $value) {
                                    ?>
                                    <option value="<?= htmlspecialcharsbx(
                                        $key
                                    ) ?>" <?= $val == $key ? 'selected' : ''; ?>><?= $value ?></option>
                                    <?
                                } ?>
                            </select>
                            <?
                            break;
                    }
                    ?>
                </td>
            </tr>
        <?endforeach;
    endforeach; ?>

    <?
    $tabControl->Buttons(); ?>
    <input type="submit" name="Update" value="<?= Loc::GetMessage("OUT_MAIN_SAVE") ?>"
           title="<?= Loc::GetMessage("OUT_MAIN_SAVE_TITLE") ?>" class="adm-btn-save">
    <input type="submit" name="Apply" value="<?= Loc::GetMessage("OUT_MAIN_APPLY") ?>"
           title="<?= Loc::GetMessage("OUT_MAIN_APPLY_TITLE") ?>">
    <?
    if (strlen($_REQUEST["back_url_settings"]) > 0): ?>
        <input type="button" name="Cancel" value="<?= Loc::GetMessage("OUT_MAIN_CANCEL") ?>"
               title="<?= Loc::GetMessage("OUT_MAIN_CANCEL_TITLE") ?>" onclick="window.location='<?
        echo htmlspecialcharsbx(CUtil::addslashes($_REQUEST["back_url_settings"])) ?>'">
        <input type="hidden" name="back_url_settings" value="<?= htmlspecialcharsbx($_REQUEST["back_url_settings"]) ?>">
    <?
    endif ?>
    <input type="submit" name="RestoreDefaults" title="<?= Loc::GetMessage("OUT_MAIN_HINT_RESTORE_DEFAULTS") ?>"
           OnClick="return confirm('<?
           echo AddSlashes(Loc::GetMessage("OUT_MAIN_HINT_RESTORE_DEFAULTS_WARNING")) ?>')"
           value="<?= Loc::GetMessage("OUT_MAIN_RESTORE_DEFAULTS") ?>">
    <?= bitrix_sessid_post(); ?>
    <?
    $tabControl->End(); ?>
</form>

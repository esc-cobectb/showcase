<?
IncludeModuleLangFile(__FILE__);
$sModuleName = "openurbantech.strava";
CModule::IncludeModule($sModuleName);

$defaults = array(
	'client_secret' => '',
	'client_token' => '',
	'refresh_token' => '',
);


$aTabs = array(
	array(
		"DIV" => "index",
		"TAB" => GetMessage("OUT_TAB_MAIN"),
		"ICON" => "",
		"TITLE" => GetMessage("OUT_TAB_MAIN_TITLE"),
		"OPTIONS" => Array(
			"client_id" => Array(GetMessage("OUT_STRAVA_CLIENT_ID"), Array("text", 8)),
			"client_secret" => Array(GetMessage("OUT_STRAVA_CLIENT_SECRET"), Array("text", 40)),
			"client_token" => Array(GetMessage("OUT_STRAVA_CLIENT_TOKEN"), Array("text", 40)),
			"refresh_token" => Array(GetMessage("OUT_STRAVA_REFRESH_TOKEN"), Array("text", 40)),
			"hub.challenge" => Array(GetMessage("OUT_STRAVA_HUB_CHALLENGE"), Array("text", 40)),
			"proxy_unauthorized" => [
				GetMessage("OUT_STRAVA_PROXY_UNAUTHORIZED"), 
				[
					"checkbox"
				]
			],
		)
	),
	array(
		"DIV" => "proxy",
		"TAB" => GetMessage("OUT_TAB_PROXY"),
		"ICON" => "",
		"TITLE" => GetMessage("OUT_TAB_PROXY_TITLE"),
		"OPTIONS" => Array(
			"proxy_ip" => Array(GetMessage("OUT_STRAVA_PROXY_ID"), Array("text", 20)),
			"proxy_http_port" => Array(GetMessage("OUT_STRAVA_PROXY_HTTP_PORT"), Array("text", 20)),
			"proxy_socks5_port" => Array(GetMessage("OUT_STRAVA_PROXY_SOCKS5_PORT"), Array("text", 20)),
			"proxy_login" => Array(GetMessage("OUT_STRAVA_PROXY_LOGIN"), Array("text", 40)),
			"proxy_pass" => Array(GetMessage("OUT_STRAVA_PROXY_PASS"), Array("text", 40)),
			"proxy_all" => [
				GetMessage("OUT_STRAVA_PROXY_ALL"), 
				[
					"checkbox"
				]
			],
		)
	),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

if($REQUEST_METHOD=="POST" && strlen($Update.$Apply.$RestoreDefaults)>0 && check_bitrix_sessid())
{
	if(strlen($RestoreDefaults)>0)
	{
		COption::RemoveOption($sModuleName);
		foreach($defaults as $name => $val){
			COption::SetOptionString($sModuleName, $name, $val);
		}
	}
	else
	{

		foreach($aTabs as $i => $aTab)
		{
			foreach($aTab["OPTIONS"] as $name => $arOption) {
				if($name == 'DIVIDER'){
					continue;
				}
				$disabled = array_key_exists("disabled", $arOption)? $arOption["disabled"]: "";
				if($disabled){
					continue;
				}
				$val = $_POST[$name];
				if($arOption[1][0]=="checkbox" && $val!="Y"){
					$val="N";
				}
				COption::SetOptionString($sModuleName, $name, $val, $arOption[0]);
			}
		}
	}
	if(strlen($Update)>0 && strlen($_REQUEST["back_url_settings"])>0){
		LocalRedirect($_REQUEST["back_url_settings"]);
	} else {
		LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($mid)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($_REQUEST["back_url_settings"])."&".$tabControl->ActiveTabParam());
	}
}

$tabControl->Begin();
?>
<form method="post" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=urlencode($mid)?>&amp;lang=<?=LANGUAGE_ID?>">
<?
foreach($aTabs as $aTab):
	$tabControl->BeginNextTab();
	foreach($aTab["OPTIONS"] as $name => $arOption):
		if($name == 'DIVIDER'){
			switch ($arOption['TYPE']) {
				case 'br':
					?>
						<tr><td colspan="2"><br/></td></tr>
					<?
				break;
				case 'hr':
				default:
					?>
						<tr><td colspan="2"><hr/></td></tr>
					<?
					break;
			}
			continue;
		}
		$val = \Bitrix\Main\Config\Option::get($sModuleName, $name);
		$type = $arOption[1];
		$disabled = array_key_exists("disabled", $arOption)? $arOption["disabled"]: "";
	?>
		<tr>
			<td width="40%" nowrap <?if($type[0]=="textarea") echo 'class="adm-detail-valign-top"'?>>
				<label for="<?echo htmlspecialcharsbx($name)?>"><?echo $arOption[0]?></label>
			<td width="60%">
				<?
					switch($type[0]){
						case 'checkbox':
							?>
							<input type="checkbox" name="<?echo htmlspecialcharsbx($name)?>" id="<?echo htmlspecialcharsbx($name)?>" value="Y"<?if($val=="Y")echo" checked";?><?if($disabled)echo' disabled="disabled"';?>><?if($disabled) echo '<br>'.$disabled;?>
							<?
							break;
						case 'text':
							?>
							<input type="text" size="<?echo $type[1]?>" maxlength="255" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($name)?>">
							<?
							break;
						case 'textarea':
							?>
							<textarea rows="<?echo $type[1]?>" cols="<?echo $type[2]?> " name="<?echo htmlspecialcharsbx($name)?>" style=
						"width:100%"><?echo htmlspecialcharsbx($val)?></textarea>	
							<?
							break;
						case 'selectbox':
							?>
							<select name="<?echo htmlspecialcharsbx($name)?>">
								<?foreach($type[1] as $key=>$value){?>
									<option value="<?=htmlspecialcharsbx($key)?>" <?= $val==$key ? 'selected' : '' ;?>><?=$value?></option>
								<? } ?>
							</select>
							<?
							break;
					}
				?>
			</td>
		</tr>
	<?endforeach;
endforeach;?>

<?$tabControl->Buttons();?>
	<input type="submit" name="Update" value="<?=GetMessage("OUT_MAIN_SAVE")?>" title="<?=GetMessage("OUT_MAIN_SAVE_TITLE")?>" class="adm-btn-save">
	<input type="submit" name="Apply" value="<?=GetMessage("OUT_MAIN_APPLY")?>" title="<?=GetMessage("OUT_MAIN_APPLY_TITLE")?>">
	<?if(strlen($_REQUEST["back_url_settings"])>0):?>
		<input type="button" name="Cancel" value="<?=GetMessage("OUT_MAIN_CANCEL")?>" title="<?=GetMessage("OUT_MAIN_CANCEL_TITLE")?>" onclick="window.location='<?echo htmlspecialcharsbx(CUtil::addslashes($_REQUEST["back_url_settings"]))?>'">
		<input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST["back_url_settings"])?>">
	<?endif?>
	<input type="submit" name="RestoreDefaults" title="<?= GetMessage("OUT_MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="return confirm('<?echo AddSlashes(GetMessage("OUT_MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>')" value="<?= GetMessage("OUT_MAIN_RESTORE_DEFAULTS")?>">
	<?=bitrix_sessid_post();?>
<?$tabControl->End();?>
</form>
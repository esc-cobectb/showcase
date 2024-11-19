<?
IncludeModuleLangFile(__FILE__);
$sModuleName = "openurbantech.news";
CModule::IncludeModule($sModuleName);

$dateTime = new \DateTime();

$defaults = array(
	'iblock_id' => '1',
);


$aTabs = array(
	[
		"DIV" => "index",
		"TAB" => GetMessage("OUT_TAB_MAIN"),
		"ICON" => "",
		"TITLE" => GetMessage("OUT_TAB_MAIN_TITLE"),
		"OPTIONS" => Array(
			"iblock_id" => Array(GetMessage("OUT_TAB_NEWS_IBLOCK_ID"), Array("text", 4)),
		)
	],
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
						case 'date':
							?>
							<input type="date" size="10" maxlength="255" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($name)?>">
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

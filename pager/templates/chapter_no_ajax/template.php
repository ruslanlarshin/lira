<link rel="stylesheet" type="text/css" href="<?=$this->GetFolder()?>/style.css">
<div class='pager'>
	<?foreach($arResult['NAV']['PAGES'] as $value){?>
		<a href='<?=$_SERVER["SCRIPT_NAME"] . '/?PAGE=' . $value["VAL"]?>'>
			<div  class='navPages nav_<?=$value["VAL"]?> <?if($arResult['PARAM']['PAGE']==$value["VAL"]*1){ echo "active";}?> <?if($value["NAME"]=='...'){ echo "points";}?>' data-page='<?=$value["VAL"]?>'><?=$value["NAME"]?></div>
		</a>
	<?}?>
</div>
<?//echo "<pre>"; print_r($arResult); echo '</pre>';?>

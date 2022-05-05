<link rel="stylesheet" type="text/css" href="<?=$this->GetFolder()?>/style.css">
<div class='pager_for_cahpter'>
	<div class='main_nav bottom_nav'>
		<?foreach($arResult['NAV']['PAGES'] as $value){?>
			<div  class='nav_page nav_<?=$value["VAL"]?> <?if($arResult['PARAM']['PAGE']==$value["VAL"]*1){ echo "active";}?> <?if($value["NAME"]=='...'){ echo "points";}?>' data-page='<?=$value["VAL"]?>'><a><?=$value["NAME"]?></a></div>
		<?}?>
	</div>
</div>
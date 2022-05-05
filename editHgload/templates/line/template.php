<?php foreach($arResult['ITEMS'] as $item){?> 
	<div class='hgloadId'><?=$item['ID']?></div>
	<div class='hgloadName'><input name='UF_NAME' type='text' placeholder='Наименование' id='NAME' value='<?=$item['NAME']?>' /></div>
	<div class='hgloadPrice'><input name='UF_PRICE' type='number' placeholder='Цена' maxlength='7' id='PRICE' value='<?=$item['PRICE']?>'/></div>
	<div class='hgloadPicture'><input name='UF_PICTURE' type="file" accept = "image/*" placeholder='' id='PICTURE'/><img src="<?=$item['PICTURE']?>"/></div>
	<div class='hgloadDate'><input  name='UF_DATE' type='date' placeholder='01.10.21' id='DATE' value='<?=$item['DATEINPUT']?>' /></div>
	<div class='hgloadEdit'>
		<div class='editPopup blockBlue' data-id='<?=$item['ID']?>'>Редактировать</div>
		<div class='save blockBlue' data-id='<?=$item['ID']?>'>Сохранить</div>
	</div>
<?}?>
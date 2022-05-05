<style>
.contentPopup .blockBlue{
	width: 200px !important;
	margin-top : 2%;
}
.lineFlex{
	display: flex;
	margin-top:20px;
}
.left{
	width: 20%;
	font-size: 16px;
    line-height: 18px;
    font-weight: 500;
    cursor: default;
	font-family: system-ui;
}
.popupPicture img{
	width: 200px;
}
</style>
<?php foreach($arResult['ITEMS'] as $item){?> 
<form class='form<?=$item['ID']?>'>
	<div class='lineFlex' >
		<div class='left'>ID элемента</div>
		<div class='popupId'><?=$item['ID']?></div>
	</div>
	<div class='lineFlex' >
		<div class='left'>Название</div>
		<div class='popupName'><input name='UF_NAME' type='text' placeholder='Наименование' id='NAME' value='<?=$item['NAME']?>' /></div>
	</div>
	<div class='lineFlex' >
		<div class='left'>Цена</div>
		<div class='popupPrice'><input name='UF_PRICE' type='number' placeholder='Цена' maxlength='7' id='PRICE' value='<?=$item['PRICE']?>'/></div>
	</div>
	<div class='lineFlex' >
		<div class='left'>Изображение</div>
		<div class='popupPicture'><input name='UF_PICTURE' type="file" accept = "image/*" placeholder='' id='PICTURE'/><img src="<?=$item['PICTURE']?>"/></div>
	</div>
	<div class='lineFlex' >
		<div class='left'>Дата создания</div>
		<div class='popupDate'><input  name='UF_DATE' type='date' placeholder='01.10.21' id='DATE' value='<?=$item['DATEINPUT']?>' /></div>
	</div>
	<div class='popupEdit'>
		<div class='savePopup blockBlue' data-id='<?=$item['ID']?>'>Сохранить</div>
	</div>
	<br />
	<div class='resultPopup success'></div>
	<div class='errorPopup errors'></div>
</form>
<?}?>
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

<form class='formAdd'>
	<div class='lineFlex' >
		<div class='left'>Название</div>
		<div class='popupName'><input name='UF_NAME' type='text' placeholder='Наименование' id='NAME' value='' /></div>
	</div>
	<div class='lineFlex' >
		<div class='left'>Цена</div>
		<div class='popupPrice'><input name='UF_PRICE' type='number' placeholder='Цена' maxlength='7' id='PRICE' value=''/></div>
	</div>
	<div class='lineFlex' >
		<div class='left'>Изображение</div>
		<div class='popupPicture'><input name='UF_PICTURE' type="file" accept = "image/*" placeholder='' id='PICTURE'/><img src="/local/img/noPhoto"/></div>
	</div>
	<div class='lineFlex' >
		<div class='left'>Дата создания</div>
		<div class='popupDate'><input  name='UF_DATE' type='date' placeholder='01.10.21' id='DATE' value='' /></div>
	</div>
	<div class='popupEdit'>
		<div class='addPopup blockBlue' data-id='<?=$item['ID']?>'>Сохранить</div>
	</div>
	<br />
	<div class='resultPopup success'></div>
	<div class='errorPopup errors'></div>
</form>

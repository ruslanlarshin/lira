<?
global $DB;
global $USER;
global $APPLICATION;
global $INTRANET_TOOLBAR;

CModule::IncludeModule("iblock");
CModule::IncludeModule("sale");
CModule::IncludeModule("catalog");
//—амый полезный компонент дл€ примеров-это новости-сделаем компонент новостей на а€ксе!!
$arOption=array();
$arError=array(); 
$time=3600000;// врем€ жизни кеша в секундах -дл€ отключени€ и тестировани€-0
$time=0;// врем€ жизни кеша в секундах -дл€ отключени€ и тестировани€-0

if($this->StartResultCache($time, array($arParams['ID']))){ //кеш беретс€ по значению $arParams и $arOption-если таковых ранее не загружалось-начнетс€ загрузка компонента
	if($arError){ //если шаблон ошибочен-то кеш не запишетс€
		$this->AbortResultCache();
		ShowError(GetMessage("IBLOCK_MODULE_NOT_INSTALLED"));
		return;
	}
	$arResult=array();
	require_once($_SERVER['DOCUMENT_ROOT'].'/local/lib/Hgload/Hgload.php');
	$arGoods = new \Hgload\Hgload();
	if($arParams['ID'])
		$arResult = $arGoods->getById($arParams['ID']);
	$this->IncludeComponentTemplate();
	if($arError)
	{
		$this->AbortResultCache();
		ShowError("ERROR");
		@define("ERROR_404", "Y");
		if($arParams["SET_STATUS_404"]==="Y")
			CHTTP::SetStatus("404 Not Found");
	}
}else{
	//echo 'Ўаблон вз€т и кеша!<BR>';// происходит тогда, когда загружен кеш-эффективно дл€ проверки работы кеша и скорости без него!!
}
?>
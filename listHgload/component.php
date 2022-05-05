<?
global $DB;
global $USER;
global $APPLICATION;

CModule::IncludeModule("iblock");
CModule::IncludeModule("sale");
CModule::IncludeModule("catalog");
//Самый полезный компонент для примеров-это новости-сделаем компонент новостей на аяксе!!
$time=3600000;// время жизни кеша в секундах -для отключения и тестирования-0
$time=0;// время жизни кеша в секундах -для отключения и тестирования-0

if($this->StartResultCache($time, array($arParams['PAGE'],$arParams['SEARCH']))){ //кеш берется по значению $arParams и $arOption-если таковых ранее не загружалось-начнется загрузка компонента
	if($arError){ //если шаблон ошибочен-то кеш не запишется
		$this->AbortResultCache();
		ShowError(GetMessage("IBLOCK_MODULE_NOT_INSTALLED"));
		return;
	}
	$arResult=array();
	require_once($_SERVER['DOCUMENT_ROOT'].'/local/lib/Hgload/Hgload.php');
	$arGoods = new \Hgload\Hgload(); 
	$arRequest = $arGoods->getRequest();
	$arResult = $arGoods->getListGoods($arRequest['PAGE']); 
	
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
	//echo 'Шаблон взят и кеша!<BR>';// происходит тогда, когда загружен кеш-эффективно для проверки работы кеша и скорости без него!!
}
?>
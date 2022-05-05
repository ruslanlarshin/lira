<?
global $DB;
global $USER;
global $APPLICATION;
global $INTRANET_TOOLBAR;

CModule::IncludeModule("iblock");
CModule::IncludeModule("sale");
CModule::IncludeModule("catalog");
//Самый полезный компонент для примеров-это новости-сделаем компонент новостей на аяксе!!
$arOption=array();
$arError=array(); 
$time=3600000;// время жизни кеша в секундах -для отключения и тестирования-0
$time=0;// время жизни кеша в секундах -для отключения и тестирования-0

if($this->StartResultCache($time, array($arOption))){ //кеш берется по значению $arParams и $arOption-если таковых ранее не загружалось-начнется загрузка компонента
	if($arError){ //если шаблон ошибочен-то кеш не запишется
		$this->AbortResultCache();
		ShowError(GetMessage("IBLOCK_MODULE_NOT_INSTALLED"));
		return;
	}
	$arResult=array();
	$arResult['PARAM']=$arParams;
	
	//$arResult["ID"]=1234567; //здесь тяжелая серверная логика-которую хотим закешировать
	$this->IncludeComponentTemplate();
	//echo 'Загрузился весь шаблон и сохранился в кеш';
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
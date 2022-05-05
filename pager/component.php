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
if($_REQUEST['clear']!='yes'){
	$time=3600000;// время жизни кеша в секундах -для отключения и тестирования-0
}else{
	$time=0;// время жизни кеша в секундах -для отключения и тестирования-0
}
$time=0;
if($this->StartResultCache($time, array($arOption))){ //кеш берется по значению $arParams и $arOption-если таковых ранее не загружалось-начнется загрузка компонента
	if($arError){ //если шаблон ошибочен-то кеш не запишется
		$this->AbortResultCache();
		ShowError(GetMessage("IBLOCK_MODULE_NOT_INSTALLED"));
		return;
	}
	$arResult=array();
	$arResult['PARAM']=$arParams;
	$max=$arParams['ALL_PAGE'];
	$page_num=$arParams['PAGE'];
	$page_all=$max; 
	$arResult['NAV']['NOW_PAGE']=$page_num;
	if($max==1){
			$arPage=array();
			$arPage['NAME']='1';
			$arPage['VAL']='1';
		//	$arPage['URL']=addParamtrUrl('PAGE','1');
			$arResult['NAV']['PAGES'][]=$arPage;
		$arResult['NAV']['FLAG']=1;	
	}else{
			$arResult['NAV']['ALL_PAGE']=$page_all;
	}
	$arResult['NAV']['PAGES']=array();
	if($page_all<=10){
		for($i=1;$i<$page_all+1;$i++){
			$arPage=array();
			$arPage['NAME']=$i;
			//$arPage['URL']=addParamtrUrl('PAGE',$i);
			$arPage['VAL']=$i;
			$arResult['NAV']['PAGES'][]=$arPage;
		}
	}	else{
		if($page_num<=5){
				for($i=1;$i<6;$i++){
				$arPage=array();
				$arPage['NAME']=$i;
				$arPage['VAL']=$i;
				//$arPage['URL']=addParamtrUrl('PAGE',$i);;
				$arResult['NAV']['PAGES'][]=$arPage;
				}
			if($page_num==5){
				$arPage=array();
				$arPage['NAME']='6';
				$arPage['VAL']='6';
				//$arPage['URL']=addParamtrUrl('PAGE','4');;
				$arResult['NAV']['PAGES'][]=$arPage;
			}
			$arPage=array();
			$arPage['NAME']='...';
			$cnt=ceil(($page_all-$page_num)/2)+$page_num;
			$arPage['VAL']=$cnt;
			//$arPage['URL']=addParamtrUrl('PAGE',$cnt);
			$arResult['NAV']['PAGES'][]=$arPage;
			
			$arPage=array();
			$arPage['NAME']=$page_all;
			$arPage['VAL']=$page_all;
			$arPage['URL']=$_SERVER['REQUEST_URI'].'&PAGE='.$page_all;
			$arResult['NAV']['PAGES'][]=$arPage;
		}else{
			$arPage=array();
			$arPage['NAME']='1';
			$arPage['VAL']='1';
		//	$arPage['URL']=addParamtrUrl('PAGE','1');
			$arResult['NAV']['PAGES'][]=$arPage;
			
			$arPage=array();
			$arPage['NAME']='...';
		//	$cnt=ceil(($page_num-1)/2)+1;
			$cnt=ceil(($page_num-1)/2)-1;
			$arPage['VAL']=$cnt;
		//	$arPage['URL']=addParamtrUrl('PAGE',$cnt);
			$arResult['NAV']['PAGES'][]=$arPage;
			
			if($page_all-$page_num <=3){
				for($i=$page_all-6;$i<$page_all+1;$i++){
					$arPage=array();
					$arPage['NAME']=$i;
					$arPage['VAL']=$i;
				//	$arPage['URL']=addParamtrUrl('PAGE',$i);
					$arResult['NAV']['PAGES'][]=$arPage;
				}
			}else{
				for($i=$page_num-2;$i<$page_num+3;$i++){
				if($i<=$page_all){
					$arPage=array();
					$arPage['NAME']=$i;
					$arPage['VAL']=$i;
					//$arPage['URL']=addParamtrUrl('PAGE',$i);
					$arResult['NAV']['PAGES'][]=$arPage;
					}
				}
				
					$arPage=array();
					$arPage['NAME']='...';
					$cnt=ceil(($page_all-$page_num)/2)+$page_num;
					$arPage['VAL']=$cnt;
					//$arPage['URL']=addParamtrUrl('PAGE',$cnt);
					$arResult['NAV']['PAGES'][]=$arPage;
					$arPage=array();
					$arPage['NAME']=$page_all;
					$arPage['VAL']=$page_all;
				//	$arPage['URL']=addParamtrUrl('PAGE',$page_all);
					$arResult['NAV']['PAGES'][]=$arPage;
			}
		}
	}

	//$arResult["ID"]=1234567; //здесь тяжелая серверная логика-которую хотим закешировать
	if($arParams['ALL_PAGE']>1){
		$this->IncludeComponentTemplate();
	}else{
		echo '<br/>';
	}
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
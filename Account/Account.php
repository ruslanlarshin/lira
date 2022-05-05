<?php

/**
	*Account это класс для личного кабинета лиры(используется для создания 4 вкладок: info,contents,brands,contacts по дефолту загружается вкалдка info
	* @param int $id -это идентификатор зарегистрированного пользователя, если параметр не заданто по умолчанию получаем $USER->GetID()
	* @author RuslanLarshin
	* @return  массив с первичными данными для пользователя 
*/
 
 
namespace Larshin\Account;
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
use Bitrix\Main\UserTable,
	Bitrix\Main,
	Bitrix\Main\SystemException,
	Bitrix\Main\Loader,
	Bitrix\Highloadblock as HL,
	Bitrix\Main\Entity,
	Bitrix\Main\Type\DateTime;
	
Loader::includeModule("highloadblock"); 
	
	define("IBLOCK_BRAND", 12); 
define("IBLOCK_CONTANTS", 9);
define("IBLOCK_DOPCONTACTS_CODE", 'dopcontacts');
define("OWNER_ID", 4);
define("RegUrl", "/^(http:\/\/|https:\/\/)?[0-9a-zA-Zа-яА-ЯёЁ]{2,23}+[.][0-9a-zA-Zа-яА-ЯёЁ.=?\/]{2,26}+$/");
define("RegUrlMax", "/_^(?:(?:https?|ftp)://)(?:\S+(?::\S*)?@)?(?:(?!10(?:\.\d{1,3}){3})(?!127(?:\.\d{1,3}){3})(?!169\.254(?:\.\d{1,3}){2})(?!192\.168(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,})))(?::\d{2,5})?(?:/[^\s]*)?$_iuS+/");
define("HighloadTags", 9);
define("HighloadTagsBrands", 10);

class Account
{
    public $id;
	public $name;

    public function __construct(int $id=0)
    {
		global $APPLICATION;
		global $USER;
		if(!$USER->IsAuthorized())
		{
			global $USER;
			$USER->Authorize(409); // убрать!!! для теста удаленного апи
		}
		if(!$id){
			$id=$USER->GetID();
		}
		$this->id=$id;
		
		return true;	
    }
	
	public function deleteElement(int $ID)
	{
		$arResult = array();
		if(!$ID)
		{
			$arResult['ERROR'][]='Для удаления необходим ID эелемента!!';
			return false;
		}else{
			try
			{
				$arResult['DELETE'] = \CIBlockElement::Delete($ID);
				if($arResult['DELETE'])
				{
					$arResult['SUCCESS'] = true;
				}else{
					$arResult['ERROR'][]='Ошибка сохранения!';
					}
			}catch (\Bitrix\Main\SystemException $e) 
			{
				$e->getMessage();
				$arResult['ERROR'][]=$user->LAST_ERROR;
			}
		}
		
		return $arResult;
	}
	
	public function getInfo() 
	{
		$arResult=array();
		try {
			if(!$this->id)
			{
				$arResult['ERRORS'][]='Ошибка пользователя, при использовании класса Larshin/User/ нужно либо передать id пользователя, либо быть авторизованнным';
			}else{
				$dbUser = \Bitrix\Main\UserTable::getList(array(
					'select' => array('ID', 'NAME','UF_NAMEORG','UF_DESCRIPTION','UF_TIP','UF_BANK','UF_RASCHET','WORK_COMPANY',
										'UF_KORSCHET','UF_INN','UF_KPP','UF_BIK','UF_OKPO','UF_OKVED','UF_SAIT','UF_LOGOO'),
					'filter' => array('ID' => $this->id)
				));
				if ($arUser = $dbUser->fetch()){
					$arResult=$arUser;
					if($arUser['UF_LOGOO']){
						$arResult['UF_LOGOO']=$this->GetImg($arUser['UF_LOGOO']); 
					}
				}
				$arProp=array('WORK_COMPANY', 'LAST_NAME' ,'SECOND_NAME','UF_NAMEORG','UF_DESCRIPTION','UF_TIP','UF_BANK','UF_RASCHET','UF_KORSCHET','UF_INN','UF_KPP','UF_BIK','UF_OKPO','UF_OKVED','UF_SAIT');
				foreach($arProp as $prop){
					if($arResult[$prop] || $arResult[$prop]==''){
						$arResult[$prop]=trim($arResult[$prop]);
					}
				}
				$this->name = $arResult['NAME']; 
			}
		} catch (\Bitrix\Main\SystemException $e) 
		{
			$arResult['ERRORS'][]='Ощибка вывода личной ифнормации по id=' . $this->id;
			$e->getMessage();
		}
		
		return $arResult;
	}
	
	public function saveInfo(array $UserInfoFields, array $FILES)
	{
		global $USER;
		$arResult=array();
		if($this->id){
			$user = new \CUser;
			$fields = Array(); 
			$arProp=array('WORK_COMPANY', 'LAST_NAME' ,'SECOND_NAME','UF_NAMEORG','UF_DESCRIPTION','UF_TIP','UF_BANK','UF_RASCHET','UF_KORSCHET','UF_INN','UF_KPP','UF_BIK','UF_OKPO','UF_OKVED','UF_SAIT');
			foreach($arProp as $prop){
				if($UserInfoFields[$prop] || $UserInfoFields[$prop]==''){
					$fields[$prop]=trim($UserInfoFields[$prop]);
				}
			}

			if($UserInfoFields['NEW_PASSWORD'] && $UserInfoFields['CONFIRM_PASSWORD'] && $UserInfoFields['NEW_PASSWORD'] == $UserInfoFields['CONFIRM_PASSWORD'] && $this->checkPassword($UserInfoFields['PASSWORD']))
			{
				$fields['PASSWORD'] = $UserInfoFields['NEW_PASSWORD'];
				$fields['CONFIRM_PASSWORD'] = $UserInfoFields['CONFIRM_PASSWORD'];
			}
			
			if($FILES['UF_PHOTO']){
				$fields['UF_PHOTO']=$FILES['UF_PHOTO'];
			}
			if($FILES['UF_LOGOO'] && $FILES['UF_LOGOO']['name']){
				$fields['UF_LOGOO']=$FILES['UF_LOGOO'];
			}
			if($UserInfoFields['DELETE'])
			{
				 $fields['UF_LOGOO'] = \CFile::MakeFileArray('\img\noPhoto.jpg');
			}
			try
			{
				$arResult['SUCCESS'] = $user->Update($USER->GetID(), $fields);
				$arResult['ERROR'][]=$user->LAST_ERROR;
			}catch (\Bitrix\Main\SystemException $e) 
			{
				$e->getMessage();
				$arResult['ERROR'][]=$user->LAST_ERROR;
			}
		}else{
			$arResult['SUCCESS']=false;
			$arResult['ERROR'][]='Информацию пользователя можно только обновлять, но не создавать..неоходим ID(авторизация)';
		}
		
		return  $arResult;	
	}
	
	public function getContents(int $id = 0, string $search = '', array $pager = null)
	{
		$arNav=array();
		$arResult=array();
		$arNav['nPageSize'] = $pager['nPageSize'] ?? 10;
		$arNav['iNumPage'] = $pager['iNumPage'] ?? 1;
		if($pager['ALL']){
			$arNav['nPageSize'] = 1000;
		}
		
		$arOrder=$pager['ORDER'] ?? array('DATE_CREATE' => 'DESC');
		if($arOrder == 1)
			$arOrder = array('DATE_CREATE' => 'DESC');
		if($pager['ORDER'] == 2)
			$arOrder = array('NAME' => 'ASC');
		
		$arSelect= array('ID','NAME','PREVIEW_PICTURE','DATE_ACTIVE_FROM','ACTIVE','DATE_CREATE','PREVIEW_TEXT');
		if($pager['SELECT'])
		{
			$arSelect = array($pager['SELECT']);
		}
		
	   $arFilter = array('IBLOCK_ID' => IBLOCK_CONTANTS,'PROPERTY_COMPANY' => $this->getID() ,'ACTIVE'=>'Y');
		if($search){
			$arFilter['NAME'] = '%' . $search . '%';
		}
		
		
		if($id){
			$arFilter['ID'] = $id;
			$arSelect= array('ID','NAME','PREVIEW_PICTURE','ACTIVE','DATE_ACTIVE_FROM','ACTIVE_FROM','PREVIEW_TEXT','DETAIL_TEXT','PROPERTY_PUBLIC','PROPERTY_BRAND_SVYAZ','PROPERTY_PICTURES','PROPERTY_TAGS_HIGHLOAD','PROPERTY_TYPE_NEWS','PROPERTY_CATEGORY');
			 $res = \CIBlockElement::GetList(array(), array('IBLOCK_ID' => IBLOCK_BRANDS, 'PROPERTY_COMPANY' => $this->getID()), false, false, ['ID', 'NAME'] ); 
			while($data = $res->Fetch()) {
				$arResult['BRANDS'][] = $data;
			}
		}

		$res = \CIBlockElement::GetList($arOrder, $arFilter, false, $arNav, $arSelect);
		while ($data = $res->GetNextElement()) 
		{
			$arFields = $data->GetFields();
			$pos = explode('.',$arFields['PREVIEW_TEXT']);
			$time=explode(' ' , $arFields['DATE_ACTIVE_FROM']);
			//echo '<pre>'; print_r($time); echo '</pre>';
			$arContent=array(
				'ID' => $arFields['ID'],
				'NAME' => $arFields['NAME'],
				'ACTIVE' => $arFields['ACTIVE'],
				'DATE_CREATE' => $time[0],
				'CATALOG' => $arFields['PROPERTY_BRAND_SVYAZ_VALUE'],
				'PICTURES' => $arFields['PROPERTY_PICTURES'],
				'PREVIEW_TEXT' =>$arFields['NAME'],
				'TEST' =>'123',
		
				//'TAGS_HIGHLOAD' => $arFields['PROPERTY_TAGS_HIGHLOAD_VALUE'] ?? array(),
			);
			
			if($arFields['PROPERTY_CATEGORY_VALUE'])
						$arContent['CATEGORY'] = $arFields['PROPERTY_CATEGORY_ENUM_ID'];   
						//$arContent['CATEGORY'] = array('ENUM_ID'=>$arFields['PROPERTY_CATEGORY_ENUM_ID'], 'NAME' => $arFields['PROPERTY_CATEGORY_VALUE']);   
			$arContent['TAGS_HIGHLOAD'] = array();
			foreach($arFields['PROPERTY_TAGS_HIGHLOAD_VALUE'] as $key=>$tags){
					if($tags)
						$arContent['TAGS_HIGHLOAD'][] = array('UF_NAME' =>$tags); 
				}
			if(!$arContent['CATALOG'] || $arContent['CATALOG']==null)
				$arContent['CATALOG']='';
			if($pager['SELECT'])
			{
				$arContent=array(
					$pager['SELECT'] => $arFields[$pager['SELECT']],
				);
			}
			$arFields['PREVIEW_PICTURE'] =  $this->GetImg($arFields['PREVIEW_PICTURE'] ?? 0);
			
			if($id)
			{
				$arContent['PREVIEW_PICTURE'] = $arFields['PREVIEW_PICTURE'];
				$arContent['PREVIEW_TEXT'] = $arFields['PREVIEW_TEXT'];
				$arContent['DETAIL_TEXT'] = $arFields['DETAIL_TEXT'];
				$arContent['PUBLIC'] = $arFields['PROPERTY_PUBLIC_VALUE'];
				if($arFields['PROPERTY_TYPE_NEWS_VALUE'])
					$arContent['TYPE_NEWS'] = array('ENUM_ID'=>$arFields['PROPERTY_TYPE_NEWS_ENUM_ID'],'NAME'=>$arFields['PROPERTY_TYPE_NEWS_VALUE']); 
				
				//$arContent['TYPE_NEWS'] = $arFields['PROPERTY_TYPE_NEWS_VALUE'];
				if(!$arContent['PUBLIC'])
					$arContent['PUBLIC'] = 'N';
				
				foreach($arFields['PROPERTY_PICTURES_VALUE'] as $img)
				{
					$buf = $this->GetImg($img ?? 0) ?? 0;  
					if($buf && $buf != 0)
						$arContent['PICTURES'][] = $buf;  
					
				}
			}
			
			$arResult['ITEMS'][] = $arContent;
		} 
		$count = $res->SelectedRowsCount();
		$arResult['PAGER']=array(
			'COUNT' => $count,
			'PAGE' => $arNav['iNumPage']*1,
			'PAGE_COUNT' => ceil($count / $arNav['nPageSize']),
		);
		
		return $arResult;
	}
	
	public function getBrandList(int $id = null, string $search = '', array $pager = null)
	{
		$arNav=array();
		$arNav['nPageSize'] = $pager['nPageSize'] ?? 10;
		$arNav['iNumPage'] = $pager['iNumPage'] ?? 1;
		if($pager['ALL']){
			$arNav['nPageSize'] = 10000;
		}
		
		$arOrder = $pager['ORDER'] ?? array('DATE_CREATE' => 'DESC');
		if($pager['ORDER'] == 2)
			$arOrder = array('NAME' => 'ASC');
		if($pager['ORDER'] == 1)
			$arOrder = array('DATE_CREATE' => 'DESC');
		
		$arSelect= array('ID','NAME','PREVIEW_PICTURE','ACTIVE','DATE_CREATE','PROPERTY_LOGO');

		$arFilter = array('IBLOCK_ID' => IBLOCK_BRAND, 'PROPERTY_COMPANY' => $this->getID() ,'ACTIVE' => 'Y');
		if($search)
		{
			$arFilter['NAME'] = '%'.$search.'%';
		}
		
		if($id)
		{
			$arFilter = array('IBLOCK_ID' => IBLOCK_BRAND,'ID' => $id);
			$arSelect = array('ID','NAME','PREVIEW_PICTURE','ACTIVE','DETAIL_TEXT','DATE_CREATE','PROPERTY_SUBTITLE','PROPERTY_COPYRIGHT','PROPERTY_TAGS','PROPERTY_PRODUCT_GROUP',
								'PROPERTY_PRESENTATION','PROPERTY_GALLERY','PROPERTY_PRESENT','PROPERTY_LOGO','PROPERTY_VIDEO_IMAGE',
								'PROPERTY_VIDEO_LINK','PROPERTY_AGE_FROM','PROPERTY_AUDITORIUM','PROPERTY_PRODUCTS','PROPERTY_LIGHT_SALE','PROEPRTY_COPYRIGHT','PROPERTY_GALLERY','PROPERTY_CATALOG','PROPERTY_TAGS_HIGHLOAD','PROPERTY_KOBREND_PROEKTS');
		}
		if($pager['SELECT'])
		{
			$arSelect = array($pager['SELECT']);
		}
		$res = \CIBlockElement::GetList($arOrder, $arFilter, false, $arNav, $arSelect);
		while ($data = $res->GetNextElement()) 
		{
			$arFields = $data->GetFields();

			$arImg = array();
			foreach($arFields['PROPERTY_GALLERY_VALUE'] as $img)
			{
				
				$buf = $this->GetImg($img ?? 0) ?? 0;  
				if($buf && $buf != 0)
					$arImg['GALLERY'][] = $buf;  
				
			}
			if(!$arImg['GALLERY'])
				$arImg['GALLERY']=null;
			
			foreach($arFields['PROPERTY_PRODUCTS_VALUE'] as $img)
			{
				$buf = $this->GetImg($img ?? 0) ?? 0;  
				if($buf && $buf != 0)
					$arImg['PRODUCTS'][] = $buf;  
				
			}
			if(!$arImg['PRODUCTS'])
				$arImg['PRODUCTS']=null;
			
			$time=explode(' ' , $arFields['DATE_CREATE']);
			$arBrand=array(
				'ID' => $arFields['ID'],
				'NAME' => $arFields['NAME'] ?? '',
				'ACTIVE' => $arFields['ACTIVE'],
				'DATE_CREATE' => $time[0],
				'LOGO' => $this->GetImgSmall($arFields['PROPERTY_LOGO_VALUE'] ?? 0),
			);
			
			if($id){
				$arBrand['SUBTITLE'] = $arFields['PROPERTY_SUBTITLE_VALUE']['TEXT'] ?? '';
				$arBrand['DETAIL_TEXT'] = $arFields['DETAIL_TEXT'] ?? '';
				$arBrand['PRESENTATION'] = $this->GetImg($arFields['PROPERTY_PRESENTATION_VALUE'] ?? 0);
				//if($arFields['PROPERTY_PRESENTATION_VALUE'])
					//$arBrand['PDF'] = '/img/pdf.png';
				$arBrand['LOGO'] = $this->GetImg($arFields['PROPERTY_LOGO_VALUE'] ?? 0);
				$arBrand['VIDEO_IMAGE'] = $this->GetImg($arFields['PROPERTY_VIDEO_IMAGE_VALUE'] ?? 0);
				$arBrand['VIDEO_LINK'] = $arFields['PROPERTY_VIDEO_LINK_VALUE'] ?? '';
				$arBrand['AGE_FROM'] = $arFields['PROPERTY_AGE_FROM_VALUE'] ?? '';
				$arBrand['COPYRIGHT'] = $arFields['PROPERTY_COPYRIGHT_VALUE'] ?? '';
				$arBrand['PRODUCTS'] = $arImg['PRODUCTS'] ?? array();
				$arBrand['LIGHT_SALE'] = $arFields['PROPERTY_LIGHT_SALE_VALUE'] ?? 'N';
				$arBrand['KOBREND'] = $arFields['PROPERTY_KOBREND_PROEKTS_VALUE'] ?? 'N';
				if($arFields['PROPERTY_KOBREND_PROEKTS_VALUE'] == 'Кобрендинговые проекты')
					$arBrand['KOBREND'] = 'Y';
				$arBrand['AUDITORIUM'] = $arFields['PROPERTY_AUDITORIUM_ENUM_ID'] ?? '';
				//$arBrand['TAGS'] = $arFields['PROPERTY_TAGS_VALUE_ID'] ?? '';
				//$arBrand['PRODUCT_GROUP'] = $arFields['PROPERTY_PRODUCT_GROUP_VALUE'] ?? '';
				$arBrand['TAGS_HIGHLOAD'] = array();
				foreach($arFields['PROPERTY_TAGS_HIGHLOAD_VALUE'] as $key=>$tags){
					if($tags)
						$arBrand['TAGS_HIGHLOAD'][] = array('UF_NAME' =>$tags); 
				}
				$arBrand['TAGS'] = array();
				foreach($arFields['PROPERTY_TAGS_VALUE'] as $key=>$tags){
					if($tags)
						$arBrand['TAGS'] = ''.$key;
				}
				/*$arBrand['PRODUCT_GROUP'] = array();
				foreach($arFields['PROPERTY_PRODUCT_GROUP_VALUE'] as $key=>$tags){
					if($tags)
						$arBrand['PRODUCT_GROUP'][] = array('ENUM_ID'=>$key, 'NAME' => $tags);   
				}*/
				//$arBrand['COMPANY'] = $arFields['PROPERTY_COMPANY_VALUE'] ?? '';
				if($arFields['PROPERTY_CATALOG_VALUE'])
					$arBrand['CATALOG'] = $this->getOwner($arFields['PROPERTY_CATALOG_VALUE']) ?? '';
				$arBrand['GALLERY'] = $arImg['GALLERY'] ?? array();
			}
			if($pager['SELECT'])
			{
				$arBrand=array(
					$pager['SELECT'] => $arFields[$pager['SELECT']],
				);
			}
			//echo '<pre>'; print_r($arFields); echo '</pre>';
			//echo '<pre>'; print_r($arBrand); echo '</pre>';
			$arResult['JSON']['ITEMS'][] = $arBrand;
		}
		
		$count = $res->SelectedRowsCount();
		$arResult['JSON']['PAGER'] = array(
			'COUNT' => $count,
			'PAGE' => $arNav['iNumPage']*1,
			'PAGE_COUNT' => ceil($count / $arNav['nPageSize']),
		);
		return $arResult['JSON'];
	}
	
	public function addBrand(array $arFormData, array $FILES)
	{
		global $USER;
		$arResult=array();
		$el = new \CIBlockElement;
	
		$PROP = array();
		if($arFormData['LIGHT_SALE'] == 'Y'){ 
			$id=462;
			$db_enum_list = \CIBlockProperty::GetPropertyEnum("LIGHT_SALE", Array(), Array("IBLOCK_ID"=>IBLOCK_BRAND));
			if($ar_enum_list = $db_enum_list->GetNext())
			{
				$id=$ar_enum_list['ID'];
			}
			if($id){
				$PROP['LIGHT_SALE'] = $id;  
			}
		}
		
		if($arFormData['KOBREND'] == 'Y'){ 
			$db_enum_list = \CIBlockProperty::GetPropertyEnum("KOBREND_PROEKTS", Array(), Array("IBLOCK_ID"=>IBLOCK_BRAND));
			if($ar_enum_list = $db_enum_list->GetNext())
			{
				$id=$ar_enum_list['ID'];
			}
			if($id){
				$PROP['KOBREND_PROEKTS'] = $id;  
			}
		}
		
		if($arFormData['CATALOG']){
			$PROP['BRAND_SVYAZ'] = $arFormData['CATALOG']; 
			$PROP['CATALOG'] = $arFormData['CATALOG']; 
		}
		
		if($arFormData['SUBTITLE']) 
			$PROP['SUBTITLE']=Array("VALUE" => Array ("TEXT" => $arFormData['SUBTITLE'], "TYPE" => "html"));
		
		if($FILES['GALLERY'])
			$PROP['GALLERY']=$this->reArrayFiles($FILES['GALLERY']);
		
		
		if($FILES['LOGO'])
			$PROP['LOGO'] = $FILES['LOGO'];
		
		if($FILES['VIDEO_IMAGE'])
			$PROP['VIDEO_IMAGE']=$FILES['VIDEO_IMAGE'];
		
		if($FILES['PRESENTATION'])
			$PROP['PRESENTATION']=$FILES['PRESENTATION'];
		
		if($arFormData['VIDEO_LINK'])
			$PROP['VIDEO_LINK'] = $arFormData['VIDEO_LINK']; 
		
		if($arFormData['AUDITORIUM'])
			$PROP['AUDITORIUM'] = $arFormData['AUDITORIUM']; 
		
		if($arFormData['COPYRIGHT'])
			$PROP['COPYRIGHT'] = $arFormData['COPYRIGHT']; 
		
		if($arFormData['AGE_FROM'])
			$PROP['AGE_FROM'] = $arFormData['AGE_FROM']; 

		if($FILES['PRODUCTS'])
			$PROP['PRODUCTS'] = $this->reArrayFiles($FILES['PRODUCTS']);
		
		if($arFormData['TAGS_HIGHLOAD'])
			$PROP['TAGS_HIGHLOAD'] = explode(',',$arFormData['TAGS_HIGHLOAD']);
		
		if($arFormData['TAGS'])
			$PROP['TAGS'] = array($arFormData['TAGS']);
		
		
		if($arFormData['PRODUCT_GROUP'])
			$PROP['PRODUCT_GROUP'] = explode(',',$arFormData['PRODUCT_GROUP']);
	
		$PROP['PROMO'] = array();
		if($PROP['PRESENTATION'])
			$PROP['PROMO'][] = 445;
		
		if($PROP['VIDEO_LINK'])
			$PROP['PROMO'][] = 447;
		
		if($PROP['GALLERY'])
			$PROP['PROMO'][] = 446;	

		$PROP['COMPANY'] = $USER->GetID();
		$arLoadProductArray = Array(
		  "MODIFIED_BY"    => $this->id, // элемент изменен текущим пользователем
		  "IBLOCK_SECTION_ID" => false,          // элемент лежит в корне раздела
		  "IBLOCK_ID"      => IBLOCK_BRAND,
		  "PROPERTY_VALUES"=> $PROP,
		  "NAME"           => trim($arFormData['NAME']),
		  "ACTIVE"         => "Y",            // активен
		  "DETAIL_TEXT_TYPE"=> 'html',
		  "PREVIEW_TEXT_TYPE"=> 'html',
		  "PREVIEW_TEXT"   => $this->updateHref($arFormData['PREVIEW_TEXT']),
		  "DETAIL_TEXT"    => $this->updateHref($arFormData['DETAIL_TEXT']),
		  'CODE' =>$this -> translit($arFormData['NAME']),
		  "PREVIEW_PICTURE" => $FILES['PREVIEW_PICTURE'],
		  );
		if($PRODUCT_ID = $el->Add($arLoadProductArray)){
			if($arFormData['TAGS_HIGHLOAD']) 
			{
				$arFormData['TAGS_HIGHLOAD'] = explode(',',$arFormData['TAGS_HIGHLOAD']);
 				$this->updateTagsHg($arFormData['TAGS_HIGHLOAD'],$PRODUCT_ID,HighloadTagsBrands);
			}
			$arResult['SUCCESS'] = true;
		}else{
			$arResult['ERROR'][] = 'Сохранение не прошло на сервере! Ошибка: '.$el->LAST_ERROR;  
		}
		
		return $arResult;
	}
	
	public function updateBrand(array $arFromData, array $FILES)
	{
		global $USER;
		if($arFromData['ID']){
			$arResult = array();
			$el = new \CIBlockElement;
			$PROP = array();
			if($arFromData['LIGHT_SALE']){ 
				$id=462;
				$db_enum_list = \CIBlockProperty::GetPropertyEnum("LIGHT_SALE", Array(), Array("IBLOCK_ID"=>IBLOCK_BRAND));
				if($ar_enum_list = $db_enum_list->GetNext())
				{
					$PROP['LIGHT_SALE'] = $ar_enum_list['ID'] ?? 0;

					if($arFromData['LIGHT_SALE'] == 'N' || $arFromData['LIGHT_SALE'] == false){
						$PROP['LIGHT_SALE'] = '';
					}
				}
			}
			if($arFromData['KOBREND'] == 'Y'){ 
				$db_enum_list = \CIBlockProperty::GetPropertyEnum("KOBREND_PROEKTS", Array(), Array("IBLOCK_ID"=>IBLOCK_BRAND));
				if($ar_enum_list = $db_enum_list->GetNext())
				{
					$id=$ar_enum_list['ID'];
				}
				if($id){
					$PROP['KOBREND_PROEKTS'] = $id;  
				}
			}
			if($arFormData['KOBREND'] == 'N')
				$PROP['KOBREND_PROEKTS'] = '';  
			
			if($arFromData['CATALOG']){
				$PROP['BRAND_SVYAZ'] = $arFromData['CATALOG']; 
				$PROP['CATALOG'] = $arFromData['CATALOG']; 
			}
			
			if($arFromData['SUBTITLE']) 
				$PROP['SUBTITLE'] = Array("VALUE" => Array ("TEXT" => $arFromData['SUBTITLE'], "TYPE" => "html"));
			
			if($FILES['GALLERY'])
				$PROP['GALLERY'] = $this->reArrayFiles($FILES['GALLERY']);
			
			if($FILES['LOGO'])
				$PROP['LOGO'] = $FILES["LOGO"];
			
			if($FILES['VIDEO_IMAGE'])
				$PROP['VIDEO_IMAGE'] = $FILES['VIDEO_IMAGE'];
			
			if($FILES['PRESENTATION'])
				$PROP['PRESENTATION'] = $FILES['PRESENTATION'];
			
			if($arFromData['VIDEO_LINK'])
				$PROP['VIDEO_LINK'] = $arFromData['VIDEO_LINK']; 
			
			if($arFromData['AUDITORIUM'])
				$PROP['AUDITORIUM'] = $arFromData['AUDITORIUM']; 
			
			if($arFromData['COPYRIGHT'])
				$PROP['COPYRIGHT'] = $arFromData['COPYRIGHT']; 
			
			if($arFromData['AGE_FROM'])
				$PROP['AGE_FROM'] = $arFromData['AGE_FROM']; 
			
			if($FILES['PRODUCTS'])
				$PROP['PRODUCTS'] = $this->reArrayFiles($FILES['PRODUCTS']);
			
			if($arFromData['TAGS_HIGHLOAD'])
				$PROP['TAGS_HIGHLOAD'] = explode(',',$arFromData['TAGS_HIGHLOAD']);
			
			if($arFromData['TAGS'])
				$PROP['TAGS'] = array($arFromData['TAGS']);
			
			if($arFromData['PRODUCT_GROUP'])
				$PROP['PRODUCT_GROUP'] = explode(',',$arFromData['PRODUCT_GROUP']);
			
			$PROP['COMPANY'] = $USER->GetID();;

			$arLoadProductArray = Array(
				"MODIFIED_BY" => $this->$id, // элемент изменен текущим пользователем
				"IBLOCK_SECTION_ID" => false,          // элемент лежит в корне раздела
				"IBLOCK_ID" => IBLOCK_BRAND,
				"PROPERTY_VALUES" => $PROP,
				"NAME" => trim($arFromData['NAME']),
				"ACTIVE" => "Y",            // активен
				"DETAIL_TEXT_TYPE"=> 'html',
				"PREVIEW_TEXT_TYPE"=> 'html',
				"PREVIEW_TEXT" => $this->updateHref($arFromData['PREVIEW_TEXT']),
				"DETAIL_TEXT" => $this->updateHref($arFromData['DETAIL_TEXT']),
				"PREVIEW_PICTURE" => $FILES['PREVIEW_PICTURE'],
			);
			  
			if($arFromData['DELETE']){//удаляем картинки после клика на удаление
			  $buf=explode(':' , $arFromData['DELETE']);
			  foreach($buf as $idDel){
				  if($idDel){
					  \CFile::Delete($idDel);
				  }
			  }
			}
			  
			//echo '<pre>'; print_r($arLoadProductArray) ; echo '</pre>';
			if($PRODUCT_ID = $el->Update($arFromData['ID'], $arLoadProductArray)){
				if($arFromData['TAGS_HIGHLOAD'])
				{
					$arFromData['TAGS_HIGHLOAD'] = explode(',',$arFromData['TAGS_HIGHLOAD']);
					$this->updateTagsHg($arFromData['TAGS_HIGHLOAD'],$PRODUCT_ID,HighloadTagsBrands);
				}
				$arResult['SUCCESS'] = true;
			}else{
				$arResult['SUCCESS'] = false;
				//$arResult['ERRORS'][] = 'Сохранение не прошло на сервере! Ошибка: ' . $el->LAST_ERROR; 
			}
		}

		return $arResult;
	}
	
	public function getOwner($id = 0)
	{
		$arResult=array();
		$HLBLOCK_ID=OWNER_ID;
		$hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById($HLBLOCK_ID)->fetch();
		$entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
		$entityDataClass = $entity->getDataClass();
		$arFilter = array();
		if($id)
			$arFilter=array('ID' => $id);
		$result = $entityDataClass::getList(array(
			"select" => array("ID",'UF_NAME'),
			"order" => array("UF_NAME"=>"ASC"),
			"filter" => $arFilter,
		));
		while ($arRow = $result->Fetch()){
			$arResult[]=$arRow;
		}
		
		return $arResult;
	}
	
	public function getAuditorium()
	{
		$db_enum_list = \CIBlockProperty::GetPropertyEnum("AUDITORIUM", Array('ID' =>'ASC'), Array("IBLOCK_ID"=>IBLOCK_BRAND));
		 while($ar_enum_list = $db_enum_list->GetNext())
		{
			$arResult[]=array(
				'ENUM_ID'=>$ar_enum_list['ID'],
				'NAME'=>$ar_enum_list['VALUE'],
			);
		}
		return $arResult;
	}
	
	public function getContacts()
	{
		$arResult=array();
		$dbUser = \Bitrix\Main\UserTable::getList(array(
					'select' => array('ID', 'EMAIL','PERSONAL_MOBILE','UF_URADRESS','UF_CONTACT'),
					'filter' => array('ID' => $this->id)
				));
		if ($arUser = $dbUser->fetch()){
			$arResult['UF_URADRESS']=$arUser['UF_URADRESS'];//это основной контакт+еще привязанные доп контакты есть
			$arResult['MAIN']=array(
				'ID'=>$arUser['ID'],
				'EMAIL'=>$arUser['EMAIL'],
				'PERSONAL_MOBILE'=>$arUser['PERSONAL_MOBILE'],
				'UF_CONTACT'=>$arUser['UF_CONTACT'],
			);
		}
		
		return $arResult;
	}
	
	public function saveMainContact(array $arFormData)
	{
	global $USER;
		$arResult = array();
		$user = new \CUser;
		$fields = Array(); 
		$arProp = array('EMAIL', 'PERSONAL_MOBILE', 'UF_URADRESS', 'UF_CONTACT');
		foreach($arProp as $prop){
			if($arFormData[$prop]){
				$fields[$prop] = $arFormData[$prop];
			}
		}
		
		try
		{
			$user->Update($USER->GetID(), $fields);
			if($user)
				$arResult['SUCCESS'] = true;
			
		}catch (\Bitrix\Main\SystemException $e) 
		{
			$e->getMessage();
			$arResult['ERROR'][]=$user->LAST_ERROR;
		}
		
		if($user->LAST_ERROR )
			$arResult['ERROR'][] = $user->LAST_ERROR;
		if(count($fields)<1)
			$arResult['ERROR'][] = 'Необходимо ввести хотя бы одно непустое поле';
		if(!$fields['EMAIL'] )
			$arResult['ERROR'][] = 'EMAIL обязательное поле! для основного контакта!';
		
		return $arResult;
	}
	
	public function addDopContact(array $arFormData)
	{
		$arResult = array();
			$el = new \CIBlockElement;
			$IBLOCK_ID = $this->getIblockId(IBLOCK_DOPCONTACTS_CODE);
			if(!$IBLOCK_ID){
				$arResult['ERROR'] = 'Инфоблок с символьным кодом ' . IBLOCK_DOPCONTACTS_CODE . 'не найден';
			}
			
			$PROP['USER'] = $this->id;
			$PROP['EMAIL'] = $arFormData['EMAIL'];
			$PROP['PHONE'] = $arFormData['PERSONAL_MOBILE'];
			$arLoadProductArray = Array(
			  "MODIFIED_BY" => $GLOBALS['USER']->GetID(), // элемент изменен текущим пользователем
			  "IBLOCK_SECTION_ID" => false,          // элемент лежит в корне раздела
			  "IBLOCK_ID" => $IBLOCK_ID,
			  "PROPERTY_VALUES" => $PROP,
			  "NAME" => $arFormData['UF_CONTACT'], 
			  "ACTIVE" => "Y",            // активен
			  );
			  
			try
			{
				if($PRODUCT_ID = $el->Add($arLoadProductArray)){
					$arResult['SUCCESS'] = true;
				}else{
					$arResult['ERROR'] = 'доп контакт не сохранен! по id=' . $el->LAST_ERROR;
				}
			}catch (\Bitrix\Main\SystemException $e) 
			{
				$e->getMessage();
				$arResult['ERROR'][]=$el->LAST_ERROR;
			}
		
		return $arResult;
	}
	
	public function editDopcontact(array $arFormData)
	{
		$el = new \CIBlockElement;
		$IBLOCK_ID = $this->getIblockId(IBLOCK_DOPCONTACTS_CODE);
		if(!$IBLOCK_ID){
			$arResult['ERROR'] = 'Инфоблок с символьным кодом ' . IBLOCK_DOPCONTACTS_CODE . 'не найден';
		}
		$arResult = array();
		if($arFormData['ID']){ 
			$PROP['USER'] = $this->id;
			$PROP['EMAIL'] = $arFormData['EMAIL'];
			$PROP['PHONE'] = $arFormData['PERSONAL_MOBILE'];
			$arLoadProductArray = Array(
			  "MODIFIED_BY" => $GLOBALS['USER']->GetID(), // элемент изменен текущим пользователем
			  "IBLOCK_SECTION_ID" => false,          // элемент лежит в корне раздела
			  "IBLOCK_ID" => $IBLOCK_ID,
			  "PROPERTY_VALUES" => $PROP,
			  "NAME" => $arFormData['UF_CONTACT'], 
			  "ACTIVE" => "Y",            // активен
			  );
			  
			try
			{
				if($PRODUCT_ID = $el->Update($arFormData['ID'], $arLoadProductArray)){
					$arResult['SUCCESS'] = true;
				}else{
					$arResult['ERROR'] = 'доп контакт не сохранен! по id=' . $el->LAST_ERROR;
				}
			}catch (\Bitrix\Main\SystemException $e) 
			{
				$e->getMessage();
				$arResult['ERROR'][]=$el->LAST_ERROR;
			}
		}else{
			$arResult['ERROR'] = 'Для редактирования доп контактов необходим id записи';
		}
		
		return $arResult;
	}
	
	public function getAllContacts()
	{
		$arResult=$this->getContacts();
	
		$IBLOCK_ID = $this->getIblockId(IBLOCK_DOPCONTACTS_CODE);
		$res = \CIBlockElement::GetList(false, array('IBLOCK_ID'=>$IBLOCK_ID,'PROPERTY_USER'=>$this->id), false, false, array('ID', 'NAME', 'PROPERTY_EMAIL', 'PROPERTY_PHONE'));
		while ($data = $res->GetNextElement()) {
			$arFields = $data->GetFields();
			$arResult['CONTACTS'][]=array(
				'ID'=>$arFields['ID'],
				'EMAIL'=>$arFields['PROPERTY_EMAIL_VALUE'],
				'PERSONAL_MOBILE'=>$arFields['PROPERTY_PHONE_VALUE'],
				'UF_CONTACT'=>$arFields['NAME'],
			);
		}
		
		return $arResult;
	}
	
	public function saveContent(array $arFormData, array $FILES)
	{
		$arResult=array(); 
		//echo '<pre>'; print_r($FILES); echo '</pre>';
		$el = new \CIBlockElement;
		$PROP = array();
		$db_enum_list = \CIBlockProperty::GetPropertyEnum("PUBLIC", Array(), Array("IBLOCK_ID"=>IBLOCK_CONTANTS));
		if($ar_enum_list = $db_enum_list->GetNext())
		{
			$PROP['PUBLIC'] = $ar_enum_list['ID'];
			if($arFormData['PUBLIC']=='N'){
				$PROP['PUBLIC'] = '';
			}else{
			
			}
		}
			
		if($arFormData['CATALOG'])
		{
			$PROP['BRAND_SVYAZ'] = $arFormData['CATALOG']; 
		}
		if($arFormData['TYPE_NEWS'])
		{
			$PROP['TYPE_NEWS'] = $arFormData['TYPE_NEWS']; 
		}
		if($arFormData['CATEGORY'])
		{
			$PROP['CATEGORY'] =$arFormData['CATEGORY']; 
		}
		if($arFormData['TAGS_HIGHLOAD'])
		{
			$PROP['TAGS_HIGHLOAD'] = explode(',',$arFormData['TAGS_HIGHLOAD']); 
		}
		$PROP['COMPANY'] = $this->id;
		if($FILES['PICTURES'])
			$PROP['PICTURES']=$this->reArrayFiles($FILES['PICTURES']);

		$arLoadProductArray = Array(
		  "MODIFIED_BY"    => $this->id, // элемент изменен текущим пользователем
		  "IBLOCK_SECTION_ID" => false,          // элемент лежит в корне раздела
		  "IBLOCK_ID"      => IBLOCK_CONTANTS,
		  "PROPERTY_VALUES"=> $PROP,
		   'CODE' =>$this -> translit($arFormData['NAME']),
		  "NAME"           => $arFormData['NAME'],
		  "ACTIVE"         => "Y",            // активен
		  "PREVIEW_TEXT"   => $this->updateHref($arFormData['PREVIEW_TEXT']),
		  "DETAIL_TEXT"    => $this->updateHref($arFormData['DETAIL_TEXT']),
		  "DETAIL_TEXT_TYPE"=> 'html',
		  "PREVIEW_TEXT_TYPE"=> 'html',
		 "PREVIEW_PICTURE" => $PROP['PICTURES'][0],
		 'DATE_ACTIVE_FROM' => date("d.m.Y"),   
		 //проверка гита
		  );

		if($PRODUCT_ID = $el->Add($arLoadProductArray))
		{
			if($arFormData['PUBLIC']!='N')
			{
				$SITE_ID = 's1';
				$EVENT_TYPE = 'REQUEST_NEWS'; 
				$arFeedForm = array(
					"URL_SITE" => 'https://lira.notamedia.ru/news/detail/' . $this->translit($arFormData['NAME']) . '/', 
					"URL_ADMIN" =>'https://lira.notamedia.ru/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=9&type=concept_kraken_s1&ID=' . $PRODUCT_ID,
				);
				$result = \CEvent::Send($EVENT_TYPE, $SITE_ID, $arFeedForm );
			}
			if($arFormData['TAGS_HIGHLOAD'])
			{
				$this->updateTagsHg($arFormData['TAGS_HIGHLOAD'],$PRODUCT_ID);
			}
			//$arResult['SUCCES'] = 'Новость сохранена успешно! по id='.$PRODUCT_ID;
			$arResult['SUCCESS'] = true;
		}else{
			$arResult['ERROR'][] = 'Новость не сохранена! ' . $el->LAST_ERROR;
		}
		
		return $arResult;

	}
	
	public function editContent(array $arFormData, array $FILES)
	{
		$arResult = array();
		if($arFormData['ID']) 
		{//если не пришел идентификатор,то метод не должен отрабатываться
			$el = new \CIBlockElement;
			$PROP = array();
			$db_enum_list = \CIBlockProperty::GetPropertyEnum("PUBLIC", Array(), Array("IBLOCK_ID"=>IBLOCK_CONTANTS));
			if($ar_enum_list = $db_enum_list->GetNext())
			{
				$PROP['PUBLIC'] = $ar_enum_list['ID'] ?? 0;
				if($arFormData['PUBLIC']=='N')
					$PROP['PUBLIC'] = '';
			}
			
			if($arFormData['CATALOG']){
				$PROP['BRAND_SVYAZ'] = $arFormData['CATALOG']; 
			}
			if($arFormData['TYPE_NEWS'])
			{
				$PROP['TYPE_NEWS'] = $arFormData['TYPE_NEWS']; 
			}
			if($arFormData['TAGS_HIGHLOAD'])
			{
				$PROP['TAGS_HIGHLOAD'] = explode(',',$arFormData['TAGS_HIGHLOAD']); 
			}
			  if($FILES['PICTURES']){
				$PROP['PICTURES'] = $this->reArrayFiles($FILES['PICTURES']);
			  }
			  
			  if($PROP['PICTURES'][0])
			  $arLoadProductArray["PREVIEW_PICTURE"]=$PROP['PICTURES'][0];
		  
			if($arFormData['CATEGORY'])
			{
				$PROP['CATEGORY'] =$arFormData['CATEGORY']; 
			}
			
			$PROP['COMPANY'] = $this->id;
			$arLoadProductArray = Array(
			  "MODIFIED_BY"    => $this->id, // элемент изменен текущим пользователем
			  "IBLOCK_SECTION_ID" => false,          // элемент лежит в корне раздела
			  "IBLOCK_ID"      => IBLOCK_CONTANTS,
			  "PROPERTY_VALUES"=> $PROP,
			  "ACTIVE"         => "Y",            // активен
			  );
			   
			if($arFormData['NAME'])
			  $arLoadProductArray["NAME"]=trim($arFormData['NAME']);
		  
			

			if($arFormData['DETAIL_TEXT'])
			{
				$arLoadProductArray['DETAIL_TEXT_TYPE'] = "html";
				$arLoadProductArray["DETAIL_TEXT"]=$this->updateHref($arFormData['DETAIL_TEXT']);
			}
		  
			if($arFormData['PREVIEW_TEXT'])
			{
				$arLoadProductArray['PREVIEW_TEXT_TYPE'] = "html";
				$arLoadProductArray["PREVIEW_TEXT"]=$this->updateHref($arFormData['PREVIEW_TEXT']);
			}

			if($arFormData['DELETE'])
			{//удаляем картинки после клика на удаление
			  $buf=explode(':' , $arFormData['DELETE']);
			  foreach($buf as $id){
				  if($id)
				  {
					  \CFile::Delete($id);
				  }
			  }
			}
			if($PRODUCT_ID = $el->Update($arFormData['ID'],$arLoadProductArray))
			{
				if($arFormData['PUBLIC']!='N')
				{
					$SITE_ID = 's1';
					$EVENT_TYPE = 'REQUEST_NEWS';  
					$arFeedForm = array(
						"URL_SITE" => 'https://lira.notamedia.ru/news/detail/' . $this->translit($arFormData['NAME']) . '/', 
						"URL_ADMIN" =>'https://lira.notamedia.ru/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=9&type=concept_kraken_s1&ID=' . $PRODUCT_ID,
					);
					$result = \CEvent::Send($EVENT_TYPE, $SITE_ID, $arFeedForm );
				}
				if($arFormData['TAGS_HIGHLOAD'])
				{
					$this->updateTagsHg($arFormData['TAGS_HIGHLOAD'],$PRODUCT_ID);
				}
				$arResult['SUCCESS'] = true;
			}else{
				$arResult['ERROR'] = 'Сохранение не прошло на сервере! Ошибка: ' . $el->LAST_ERROR;
			}
		}
		
		return $arResult;
	}
	
	public function viewJson(array $param)
	{
		echo json_encode($param, JSON_UNESCAPED_UNICODE);
	}
	
	private function GetImg(int $idimg)
	{
		$result=array();
		if($idimg)
		{
			$result['ID']=$idimg;
			$result['SRC']=\CFile::GetPath($idimg);
			$result['FILE']=\CFile::MakeFileArray($idimg);
			if(!$result['SRC']){
				return null;
			}
			return $result;
		}else{
			return null;
		}
	}
	
	private function GetImgSmall(int $idimg)
	{
		$result=array();
		if($idimg)
		{
			$result['ID']=$idimg;
			$buf = \CFile::ResizeImageGet($idimg ,  array("width" => 120, "height" => 90));
			$result['SRC']=$buf['src'];
			//$result['SRC']=\CFile::GetPath($idimg);
			$result['FILE']=\CFile::ResizeImageGet($idimg ,  array("width" => 120, "height" => 90));
			if(!$result['SRC']){
				return null;
			}
			return $result;
		}else{
			return null;
		}
	}
	
	public function viewLogo()
	{
		echo '<img src="'.$this->UserInfo['UF_LOGOO']['SRC'].'"/>';
		return  true;
	}		

    public function getID()
    {
        return $this->id;
    }
	
	public function getIblockId(string  $code)
	{
		$res = \CIBlock::GetList(Array(),  Array("CODE" => $code ), true);
		while($ar_res = $res->Fetch()){
			$IBLOCK_ID = $ar_res['ID'];//при переносах id блоков не совпадают будем привязыватьсчя  к символьному коду
		}
	
		return $IBLOCK_ID ?? false;	
	}
	
	public function getRequest()
	{
		$arRequest=array();
		$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
		$postValues = $request->getPostList()->toArray(); 
		$getValues = $request->getQueryList()->toArray();
		$arRequest=array_merge($postValues,$getValues);
		
		return $arRequest;
	}
	
	public function viewResponceStatus(array $arError)
	{
		if($arError[['ERROR']])
		{
			foreach($arError as $error)
			{
				$response = new \Bitrix\Main\HttpResponse();
				$response->addHeader('Content-Type', 'text/plain');
				$response->setContent($error); 
				http_response_code(401); 
			}
		}else{
			http_response_code(200);
		}
		
		return true;
	}
	
	public function getTypeBusiness()
	{
		$ID=16;
		$rsData = \CUserTypeEntity::GetList( array('NAME'=>'asc'), array('FIELD_NAME'=>'UF_TIP') );
		while($arRes = $rsData->Fetch())
		{ 
			$ID=$arRes['ID'];
		}

		$arResult=array();
		$obEnum = new \CUserFieldEnum;
		$rsEnum = $obEnum->GetList(array(), array("USER_FIELD_ID" => $ID));
		$enum = array();
		while($arEnum = $rsEnum->Fetch())
		{
			$arResult[]=array(
				'ID'=>$arEnum["ID"],
				'NAME'=>$arEnum["VALUE"],
			);
		}
		
		return $arResult;
	}
	
	public function getSort()
	{
		$arResult[] = array(
			'NAME' => 'по дате создания',
			'ID' => 1,
		);
		$arResult[] = array(
			'NAME' => 'по алфавиту',
			'ID' => 2,
		);
		
		return $arResult;
 	}
		
	public function regUrl($url)
	{
		if(preg_match(RegUrl,$url))
		{
			echo 'true';
		}else{
			echo 'false';
		}
	}
	
	public function initHighLoad(int $HgloadId = 1)
    {
		$hlblock = HL\HighloadBlockTable::getById($HgloadId)->fetch(); 
		$entity = HL\HighloadBlockTable::compileEntity($hlblock); 
		$entity_data_class = $entity->getDataClass(); 
		return $entity_data_class;	
    }
	
	public function getTags($search = '',$HgId = HighloadTags)
	{
		$result = array();
		
		$arFilter = array();
		if($search)
		{
			$arFilter = array('UF_NAME' => '%' . $search . '%');
		}
		$rsData = $this->initHighLoad($HgId)::getList(array(
		   "select" => array('ID',"UF_NAME",'UF_NEWS_STRING','UF_NEWS'),
		   "order" => array("UF_NAME" => "ASC"),
		   "filter" => $arFilter , // Задаем параметры фильтра выборки
		   'limit' => 10,
		));

		while($arData = $rsData->Fetch()){
			$result[] = $arData;
		}
		
		return $result;
	}
	
	public function updateTagsHg($arTags,$newsId,$HgId = HighloadTags)
	{	
		$arFilter = array('UF_NAME' => $arTags);
		$rsData = $this->initHighLoad($HgId)::getList(array(
		   "select" => array('ID',"UF_NAME",'UF_NEWS_STRING','UF_NEWS'),
		   "order" => array("ID" => "ASC"),
		   "filter" => $arFilter  // Задаем параметры фильтра выборки
		));
		while($arData = $rsData->Fetch()){
			$arData['UF_NEWS_STRING'] = str_replace(':' . $newsId,'',$arData['UF_NEWS_STRING']);
			$arData['UF_NEWS_STRING'] = $arData['UF_NEWS_STRING'] . ':' . $newsId; 
			//if(!in_array($newsId,$arData['UF_NEWS']))
				//$arData['UF_NEWS'][] = $newsId;
			$result = $this->initHighLoad($HgId)::update($arData['ID'], $arData);
		}
		return true;
	}
	
	 public function translit($str) 
	 {
		$rus = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я');
		$lat = array('A', 'B', 'V', 'G', 'D', 'E', 'E', 'Gh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Sch', 'Y', 'Y', 'Y', 'E', 'Yu', 'Ya', 'a', 'b', 'v', 'g', 'd', 'e', 'e', 'gh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch', 'y', 'y', 'y', 'e', 'yu', 'ya');
		$str=str_replace(array(' ','"','/','_','.',';','!','^',':','+',',','«',"»",'(',')','&','?',':','/','?','#','[',']','@','!','$','&',"'",'(',')','*','+',',',';','=','~','%','{','}','|','”','>','<','E','”','№','%','Y',"’",'|','\\'),'',$str);
		if(!$str)
			$str = 'codeNote';
		return str_replace($rus, $lat, $str);
	}
	  
	public function reArrayFiles($arr) 
	{
		$result = array();
		foreach ($arr['name'] as $key => $val) {
			$result[$key]['name'] = $val;
			$result[$key]['type'] = $arr['type'][$key];
			$result[$key]['tmp_name'] = $arr['tmp_name'][$key];
			$result[$key]['size'] = $arr['size'][$key];
		}

		$arFiles = array();
		foreach ($result as $item){
				$arFiles[] = array('VALUE' => $item); 
		}
		
		return $arFiles;
	}
	
	public function checkPassword($password = '')
	{
		$result = array();
		global $USER;
		if($USER->IsAuthorized()){
		   $userData = \CUser::GetByID($USER->GetID())->Fetch();
			$salt = substr($userData['PASSWORD'], 0, (strlen($userData['PASSWORD']) - 32));
			$realPassword = substr($userData['PASSWORD'], -32);
			$password = md5($salt.$password);
			$result['SUCCESS'] = ($password == $realPassword);
		}else{
			$result['ERROR'] = 'Данная функция недосутпна для неавторизованного пользователя!';
		}
		return $result;
	}
	
	public function changePassword($PASSWORD,$NEW_PASSWORD, $CONFIRM_PASSWORD)
	{
		global $USER;
		if($USER->IsAuthorized() && $NEW_PASSWORD && $CONFIRM_PASSWORD && $NEW_PASSWORD == $CONFIRM_PASSWORD && $this->checkPassword($PASSWORD))
		{
			$user = new \CUser;
			$fields['PASSWORD'] = $NEW_PASSWORD;
			$fields['CONFIRM_PASSWORD'] = $CONFIRM_PASSWORD;
			try
			{
				$arResult['SUCCESS'] = $user->Update($USER->GetID(), $fields);
				$arResult['SUCCESS'] = true;
			}catch (\Bitrix\Main\SystemException $e) 
			{
				$e->getMessage();
				$arResult['ERROR'][]=$user->LAST_ERROR;
			}
		}
		return $arResult;
	}
	
	public function productsGroup($search = '')
	{
		$result = array();
		$key = 0;
		$filter = Array('ID' =>'ASC',"IBLOCK_ID"=>IBLOCK_BRAND);
		if($search !='')
			$filter['VALUE'] = '%' . $search . '%';
		
		$db_enum_list = \CIBlockProperty::GetPropertyEnum("PRODUCT_GROUP", $filter);
		 while($ar_enum_list = $db_enum_list->GetNext())
		{	
			if($key <10)
			{
				if(str_replace(mb_strtolower($search),'',mb_strtolower($ar_enum_list['VALUE'])) != mb_strtolower($ar_enum_list['VALUE']) || $search == '')
				{
					$result[]=array(
						'ENUM_ID'=>$ar_enum_list['ID'],
						'NAME'=>$ar_enum_list['VALUE'],
					);
				}
			}
			$key++;
		}
		return $result;
	}
	
	public function getPropertyList($code)
	{
		$result = array();
		if($code)
		{
			$db_enum_list = \CIBlockProperty::GetPropertyEnum($code, Array('ID' =>'ASC'), Array("IBLOCK_ID"=>IBLOCK_BRAND));
			 while($ar_enum_list = $db_enum_list->GetNext())
			{
				$result[]=array(
					'ENUM_ID'=>$ar_enum_list['ID'],
					'NAME'=>$ar_enum_list['VALUE'],
				);
			}
		}
		return $result;
	}
	
	public function updateHref($text)
	{
		$text= preg_replace("/(^|[\n ])([\w]*?)((ht|f)tp(s)?:\/\/[\w]+[^ \,\"\n\r\t<]*)/is", "$1$2<a href=\"$3\" target='_blank'>$3</a>", $text);
		$text= preg_replace("/(^|[\n ])([\w]*?)((www|ftp)\.[^ \,\"\t\n\r<]*)/is", "$1$2<a href=\"http://$3\" target='_blank'>$3</a>", $text);
		$text= preg_replace("/(^|[\n ])([a-z0-9&\-_\.]+?)@([\w\-]+\.([\w\-\.]+)+)/i", "$1<a href=\"mailto:$2@$3\" target='_blank'>$2@$3</a>", $text);
		//$text= strip_tags($text);
		return($text);
	}

}
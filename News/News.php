<?php
	
namespace Larshin\News;
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
use Bitrix\Main\UserTable,
	Bitrix\Main,
	Bitrix\Main\SystemException,
	Bitrix\Main\Loader,
	Bitrix\Highloadblock as HL,
	Bitrix\Main\Entity;	
	
Loader::includeModule("highloadblock"); 
	
define("IBLOCK_BRAND", 12);
define("IBLOCK_NEWS", 9);
define("IBLOCK_CONTANTS", 9);
define("IBLOCK_DOPCONTACTS_CODE", 'dopcontacts');
define("OWNER_ID", 4);
define("RegUrl", "/^(http:\/\/|https:\/\/)?[0-9a-zA-Zа-яА-ЯёЁ]{2,23}+[.][0-9a-zA-Zа-яА-ЯёЁ.=?\/]{2,26}+$/");
define("HighloadTags", 9);
define("HighloadBrandsTags", 10);

class News
{
    public function __construct(int $id=0)
    {
		return true;	
    }
	
	public function initHg($HgId)
	{
		$hlblock = HL\HighloadBlockTable::getById($HgId)->fetch(); 
		$entity = HL\HighloadBlockTable::compileEntity($hlblock); 
		$entityHgload = $entity->getDataClass(); 
		return $entityHgload;	
	}
	
	public function convertDate($date)
	{
		$Month= array('0','янв','фев','мар','апр','мая','июн','июл','авг','сен','окт','ноя','дек');
		$result = ConvertDateTime($date, "DD", "ru") . ' ' . $Month[ConvertDateTime($date, "M", "ru")*1] . ' ' .  ConvertDateTime($date, "YYYY", "ru");
		return $result;
	}
	
	public function getList($filter = array(),$arNav = false,$sort = array())
	{
		$filter['IBLOCK_ID'] = IBLOCK_NEWS;
		$arResult = array();
		if(!$sort)
			$sort =  array('DATE_ACTIVE_FROM'=>'DESC','DATE_CREATE'=>'DESC');
		
		$res = \CIBlockElement::GetList($sort, $filter, false, $arNav, array('ID','NAME','DATE_CREATE','DATE_ACTIVE_FROM','PREVIEW_PICTURE','PROPERTY_PICTURES','PREVIEW_TEXT','DETAIL_TEXT','PROPERTY_NEWS_TYPE','CODE'));
		while ($data = $res->GetNextElement()) 
		{
			$arFields = $data->GetFields();
			$arResult[] = array(
				'NAME' => $arFields['~NAME'],
				'ID' => $arFields['ID'],
				'CODE' => $arFields['CODE'],
				'URL' => '/news/detail/' . $arFields['CODE'] .'/',
				'DATE_CREATE' => $this->convertDate($arFields['DATE_ACTIVE_FROM']),
				'PREVIEW_TEXT' => $arFields['~PREVIEW_TEXT'],
				'DETAIL_TEXT' => $arFields['~DETAIL_TEXT'],
				'PICTURE' => \CFile::GetPath($arFields['PREVIEW_PICTURE'] ?? $arFields['PROPERTY_PICTURES_VALUE'][0]),
				'PICTURE_SMALL' => \CFile::ResizeImageGet($arFields['PREVIEW_PICTURE'] ?? $arFields['PROPERTY_PICTURES_VALUE'][0],array('width'=>166, 'height'=>132), BX_RESIZE_IMAGE_PROPORTIONAL),
			);
		} 
		return $arResult;	
	}
	
	public function getBrandsIdByCompany($companyId)
	{
		$result = array();
		if($companyId)
		{
			$res = \CIBlockElement::GetList(array(), array('IBLOCK_ID' => IBLOCK_BRAND,'PROPERTY_COMPANY' => $companyId), false, false, array('ID'));
			while ($data = $res->GetNextElement()) 
			{
				$arFields = $data->GetFields();
				$result[] = $arFields['ID'];
			}
		}
		return $result;
	}
	
	public function getCompanyNews($companyId)
	{
		$result = array();
		if($companyId)
		{
			$arBrands = $this->getBrandsIdByCompany($companyId);
			if($arBrands[0])
				$result = $this->getList(array("PROPERTY_BRAND_SVYAZ" => $arBrands),array('nTopCount'=>'5'),array('DATE_CREATE'=>'DESC'));
		}
		return $result;
	}
	
	public function getTags($search = '',$HgId = HighloadTags)
	{
		$result = array();
		$arFilter = array();
		if($search)
		{
			$arFilter = array('UF_NAME' => '%' . $search . '%');
		}
		$rsData = $this->initHg($HgId)::getList(array(
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
	
	public function addTags($name)
	{
		$id = $this->initHg(HighloadTags)::add(array('UF_NAME' => $name));
		return $id;
	}
	
	public function addTagsBrands($name)
	{
		$id = $this->initHg(HighloadBrandsTags)::add(array('UF_NAME' => $name));
		return $id;
	}
	
	public function getDetailNews($code)
	{
		$result = array();
		$res = \CIBlockElement::GetList(array('DATE_CREATE'=>'DESC'), array('IBLOCK_ID'=>IBLOCK_NEWS,'CODE'=>$code), false, false, array('ID','NAME','DATE_CREATE','DATE_ACTIVE','PREVIEW_PICTURE','PROPERTY_PICTURES','PREVIEW_TEXT','DETAIL_TEXT','PROPERTY_NEWS_TYPE','CODE','PROPERTY_TAGS_HIGHLOAD'));
		while ($data = $res->GetNextElement()) 
		{
			$arFields = $data->GetFields();
			$result = array(
				'ID' => $arFields['ID'],
				'NAME' => $arFields['NAME'],
				'DATE' => explode(' ',$arFields['DATE_ACTIVE'] ?? $arFields['DATE_CREATE']),
				'DETAIL_TEXT' => $arFields['~DETAIL_TEXT'],
				'PREVIEW_TEXT' => $arFields['~PREVIEW_TEXT'],
				'TAGS' => $arFields['PROPERTY_TAGS_HIGHLOAD_VALUE'],
			);
			
			$anounce = \CFile::GetPath($arFields['PREVIEW_PICTURE']);
			//if($anounce['src'])
			//	$arPicture = array($anounce);
			foreach($arFields['PROPERTY_PICTURES_VALUE'] as $img){
				if(\CFile::GetPath($img)){
					$buf = \CFile::GetPath($img);
					if($buf['src'] && trim($buf['src'])){
						$arPicture[] = $buf;
					}
				}
			}
			$result['PICTURE'] = $arPicture;
		}
		return $result;
	}
}
?>
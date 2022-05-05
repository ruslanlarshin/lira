<?namespace Larshin\Brands;
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
use Bitrix\Main\UserTable,
	Bitrix\Main,
	Bitrix\Main\SystemException,
	Bitrix\Main\Loader,
	Bitrix\Highloadblock as HL,
	Bitrix\Main\Entity;	
	
Loader::includeModule("highloadblock"); 
define("IBLOCK_BRAND", 12); 


class Brands
{	
    public function __construct()
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
	
	public function getListBrandsImg()
	{
		$result = array();
		$res = \CIBlockElement::GetList(array(), array('IBLOCK_ID' => IBLOCK_BRAND,), false, array('nTopCount'=>16), array('ID','CODE','NAME','PROPERTY_LOGO'));
		while ($data = $res->GetNextElement()) 
		{
			$arFields = $data->GetFields();
			$result['items'][] = array(
				'title' => $arFields['NAME'],
				'logo' => \CFile::GetPath($arFields['PROPERTY_LOGO_VALUE']),
				'url' => '/catalog/' . $arFields['CODE'] . '/',
			);
		}
		return $result;
	}
}
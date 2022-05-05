<?namespace Larshin\Lira;
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
use Bitrix\Main\UserTable,
	Bitrix\Main,
	Bitrix\Main\SystemException,
	Bitrix\Main\Loader,
	Bitrix\Highloadblock as HL,
	Bitrix\Main\Entity;	
	
Loader::includeModule("highloadblock"); 
require_once($_SERVER['DOCUMENT_ROOT'].'/local/php_interface/lib/Larshin/News/News.php');	
define("IBLOCK_BRAND", 12); 
define("IBLOCK_CONTANTS", 9);
define("IBLOCK_DOPCONTACTS_CODE", 'dopcontacts');
define("OWNER_ID", 4);
define("SOCHIAL", 21);
define("mailNews", 22);
define("RegUrl", "/^(http:\/\/|https:\/\/)?[0-9a-zA-Zа-яА-ЯёЁ]{2,23}+[.][0-9a-zA-Zа-яА-ЯёЁ.=?\/]{2,26}+$/");
define("RegEmail", "/^([A-Za-z0-9_\.-]+)@([A-Za-z0-9_\.-]+)\.([A-za-z\.]{2,6})+$/"); 
define("HighloadTags", 9);
define("HighloadTagsBrands", 10);
define("HighloadPurchase", 11);
define("HighloadQuestions", 12);
define("BottomMenu", 'bottom');
define("BottomMenuId", 23);
define("BottomMenuSmallId", 24);

class Lira
{
	public function initHg($HgId)
	{
		$hlblock = HL\HighloadBlockTable::getById($HgId)->fetch(); 
		$entity = HL\HighloadBlockTable::compileEntity($hlblock); 
		$entityHgload = $entity->getDataClass(); 
		return $entityHgload;	
	}
	
    public function __construct()
    {
		return true;	
    }
	
	public function sochialMenu()
	{
		$result = array();
		$res = \CIBlockElement::GetList(array('SORT'=>'ASC'), array('IBLOCK_ID'=>SOCHIAL,'ACTIVE'=>'Y'), false, false, array('ID','NAME','PROPERTY_ACTIVE','PROPERTY_DISABLED','PROPERTY_URL'));
		while ($data = $res->GetNextElement()) 
		{
			$arFields = $data->GetFields();
			$result[] = array(
				'ID' => $arFields['ID'],
				'NAME' => $arFields['NAME'],
				'URL' => $arFields['PROPERTY_URL_VALUE'],
				'PICTURE' => \CFile::GetPath($arFields['PROPERTY_ACTIVE_VALUE']),
				'GREY' => \CFile::GetPath($arFields['PROPERTY_DISABLED_VALUE']),
			);
			//echo '<pre>'; print_r($arFields); echo '</pre>';
		}
		return $result;
	}
	
	public function boldResult($search,$text)
	{
		$result = '';
		//одно дело когда слово одно
		$numFirst = mb_strpos($text,$search) ??  mb_strpos($text,mb_strtoupper($search)) ?? mb_strpos($text,ucfirst($search));
		if(!$numFirst || $numFirst <= 75)
		{
			$result = mb_substr($text,0,75+$numFirst);
			if(mb_strlen($text) > 75)
				$result .= '...';
		}else{
			$result = mb_substr($text,$numFirst-75,mb_strlen($search)+150);
			if(mb_strlen($text) > $numFirst + mb_strlen($search) + 150)
				$result .= '...';
		}
		$resultBuf = str_replace($search,'<mark>' . $search . '</mark>', $result); 
		if($resultBuf == $result)
		{
			$resultBuf = str_replace(mb_strtoupper($search),'<mark>' . mb_strtoupper($search) . '</mark>', $result); 
		}
		if($resultBuf == $result)
		{
			$resultBuf = str_replace($this->mb_ucfirst($search),'<mark>' . $this->mb_ucfirst($search) . '</mark>', $result);  
		}
		if($resultBuf == $result)
		{
			$resultBuf = str_replace(mb_strtolower($search),'<mark>' . mb_strtolower($search) . '</mark>', $result);  
		}
		$result = $resultBuf;
		//--одно дело когда слово одно
		return trim($result);
	}
	
	public function mb_ucfirst($string, $encoding = 'UTF-8')  
	{
		$firstChar = mb_substr($string, 0, 1, $encoding);
		$then = mb_substr($string, 1, null, $encoding);
		return mb_strtoupper($firstChar, $encoding) . $then;
	}
	
	public function searchNews($search,$page = 1,$all = '')
	{
		$page = $page ?? 1;
		$filter =  array(
			"LOGIC" => "OR",
			array('NAME' => '%' . $search . '%','IBLOCK_ID' => IBLOCK_CONTANTS),
			array('PREVIEW_TEXT' => '%' . $search . '%','IBLOCK_ID' => IBLOCK_CONTANTS),
			array('DETAIL_TEXT' => '%' . $search . '%','IBLOCK_ID' => IBLOCK_CONTANTS),
		);
		$arResult = array();
		$arNav = array(
			'nPageSize' => 10,
			'iNumPage' => $page,
		);
		if($all == 'ALL')
			$arNav['nPageSize'] = 5;
		$news = new \Larshin\News\News();
		$res = \CIBlockElement::GetList(array('ID'=>'ASC'), $filter, false, $arNav, array('ID','NAME','PREVIEW_PICTURE','PROPERTY_PICTURES','PREVIEW_TEXT','DETAIL_TEXT','CODE'));
		$count = $res->SelectedRowsCount();
		while ($data = $res->GetNextElement()) 
		{
			$arFields = $data->GetFields();
			$picture = '';
			$pictureSmall = '';
			if(\CFile::GetPath($arFields['PREVIEW_PICTURE']))
			{
				$picture = \CFile::GetPath($arFields['PREVIEW_PICTURE']);
				$pictureSmall = \CFile::ResizeImageGet( $arFields['PREVIEW_PICTURE'],array('width'=>166, 'height'=>132), BX_RESIZE_IMAGE_PROPORTIONAL);
			}
			
			foreach($arFields['PROPERTY_PICTURES_VALUE'] as $pic)
			{
				if(!$picture)
				{
					$picture = \CFile::GetPath($pic);
					$pictureSmall = \CFile::ResizeImageGet($pic,array('width'=>166, 'height'=>132), BX_RESIZE_IMAGE_PROPORTIONAL);
				}
			}
			$arResult['items'][] = array(
				'NAME' => $this->boldResult($search,$arFields['NAME']), 
				//'NAME' => $arFields['NAME'], 
				'ID' => $arFields['ID'],
				'CODE' => $arFields['CODE'],
				'DATE_CREATE' => $news->convertDate($arFields['DATE_CREATE']),
				'PREVIEW_TEXT' => $this->boldResult($search,$arFields['PREVIEW_TEXT']),
				//'PREVIEW_TEXT' => $arFields['~PREVIEW_TEXT'],
				'DETAIL_TEXT' => $this->boldResult($search,$arFields['DETAIL_TEXT']),
				'URL' => '/news/detail/' . $arFields['CODE'] . '/',
				//'DETAIL_TEXT' => $arFields['~DETAIL_TEXT'],
				'PICTURE' => $picture,
				'PICTURE_SMALL' => $pictureSmall,
			);
		}
		if($all != 'ALL')
		{
			$arResult['pager'] = array(
				'PAGE' => $page*1,
				'PAGE_COUNT' => ceil(($count) / 10),
				'COUNT' => $count*1,
			);
		}else{
			if($count > 5)
			{
				$arResult['more'] = true;
			}else{
				$arResult['more'] = false;
			}
		}
		return $arResult;	
	}
	
	public function searchBrands($search, $page = 1, $all = '')
	{
		$page = $page ?? 1;
		$filter =  array(
			"LOGIC" => "OR",
			array('NAME' => '%' . $search . '%','IBLOCK_ID' => IBLOCK_BRAND),
			array('PREVIEW_TEXT' => '%' . $search . '%','IBLOCK_ID' => IBLOCK_BRAND),
			array('DETAIL_TEXT' => '%' . $search . '%','IBLOCK_ID' => IBLOCK_BRAND),
		);
		$arResult = array();
		$arNav = array(
			'nPageSize' => 10,
			'iNumPage' => $page,
		);
		if($all == 'ALL')
			$arNav['nPageSize'] = 5;
		
		$news = new \Larshin\News\News();
		$res = \CIBlockElement::GetList(array('NAME'=>'ASC'), $filter, false, $arNav, array('ID','NAME','PREVIEW_PICTURE','PREVIEW_TEXT','DETAIL_TEXT','CODE','PROPERTY_GALLERY'));
		$count = $res->SelectedRowsCount();
		while ($data = $res->GetNextElement()) 
		{
			$arFields = $data->GetFields();
			$picture = '';
			$pictureSmall = '';
			if(\CFile::GetPath($arFields['PREVIEW_PICTURE']))
			{
				$picture = \CFile::GetPath($arFields['PREVIEW_PICTURE']);
				$pictureSmall = \CFile::ResizeImageGet( $arFields['PREVIEW_PICTURE'],array('width'=>166, 'height'=>132), BX_RESIZE_IMAGE_PROPORTIONAL);
			}
			
			foreach($arFields['PROPERTY_GALLERY_VALUE'] as $pic)
			{
				if(!$picture)
				{
					$picture = \CFile::GetPath($pic);
					$pictureSmall = \CFile::ResizeImageGet($pic,array('width'=>166, 'height'=>132), BX_RESIZE_IMAGE_PROPORTIONAL);
				}
			}
			$arResult['items'][] = array(
				'NAME' => $this->boldResult($search,$arFields['NAME']), 
				//'NAME' =>$arFields['NAME'], 
				'ID' => $arFields['ID'],
				'CODE' => $arFields['CODE'],
				'DATE_CREATE' => $news->convertDate($arFields['DATE_CREATE']),
				'PREVIEW_TEXT' => $this->boldResult($search,$arFields['PREVIEW_TEXT']),
				//'PREVIEW_TEXT' => $arFields['~PREVIEW_TEXT'],
				'DETAIL_TEXT' => $this->boldResult($search,$arFields['DETAIL_TEXT']),
				'URL' => '/catalog/' . $arFields['CODE'] . '/',
				//'DETAIL_TEXT' => $arFields['~DETAIL_TEXT'],
				'PICTURE' => $picture,
				'PICTURE_SMALL' => $pictureSmall,
			);
			//echo '<pre>'; print_r($arResult); echo '</pre>';
		}
		if($all != 'ALL')
		{
			$arResult['pager'] = array(
				'PAGE' => $page*1,
				'PAGE_COUNT' => ceil(($count) / 10),
				'COUNT' => $count*1,
			);
		}else{
			if($count > 5)
			{
				$arResult['more'] = true;
			}else{
				$arResult['more'] = false;
			}
		}
		return $arResult;			
	}
	
	public function searchCompany($search, $page = 1, $all = '')
	{
		$limit = 10;
		if($all == 'ALL')
			$limit = 5;
		
		$page = $page ?? 1;
		
		$filter =  array(
			"LOGIC" => "OR",
			//array('NAME' => '%' . $search . '%'),
			array('UF_DESCRIPTION' => '%' . $search . '%'),
			array('WORK_COMPANY' => '%' . $search . '%'),
		);
		$offset = ($page-1)*10;
		$dbUser = \Bitrix\Main\UserTable::getList(array(
            'select' => array('NAME','LOGIN','UF_LOGOO','WORK_COMPANY','UF_DESCRIPTION','ID'),
            'filter' => $filter,
			'order' => array('WORK_COMPANY' => 'ASC'),
			'limit' =>  $limit,
			'offset' => $offset, 
			'count_total' => true, 
        ));
		$count = $dbUser->getCount();
		while($arUser = $dbUser->fetch()){
			$arResult['items'][] = array(
				'NAME' =>  $this->boldResult($search,$arUser['WORK_COMPANY']) ?? $this->boldResult($search,$arUser['NAME']), 
				//'NAME' => $arUser['NAME'], 
				'ID' => $arUser['ID'], 
				//'LOGIN' => $this->boldResult($search,$arUser['LOGIN']), 
				//'PREVIEW_TEXT' => $this->boldResult($search,$arUser['UF_DESCRIPTION']), 
				//'LOGIN' => $arUser['LOGIN'], 
				'WORK_COMPANY' => $this->boldResult($search,$arUser['WORK_COMPANY']),   
				'DETAIL_TEXT' => $this->boldResult($search,$arUser['UF_DESCRIPTION']), 
				'URL' => '/catalog/company/?id=' . $arUser['ID'],
				//'WORK_COMPANY' =>$arUser['WORK_COMPANY'],  
				//'PICTURE_SMALL' => \CFile::ResizeImageGet($arUser['UF_LOGOO'],array('width'=>166, 'height'=>132), BX_RESIZE_IMAGE_PROPORTIONAL),
			);
		}
		
		if($all != 'ALL')
		{
			$arResult['pager'] = array(
				'PAGE' => $page*1,
				'PAGE_COUNT' => ceil(($count) / 10),
				'COUNT' => $count*1,
				);
		}else{
			if($count > 5)
			{
				$arResult['more'] = true;
			}else{
				$arResult['more'] = false;
			}
		}
		return $arResult;	
	}
	
	public function searchGlobal($search = '')
	{
		$result = array();
		$resultBuf['title'] = 'Бренды';
		$resultBuf['name'] = 'brands';
		$result[] = array_merge($resultBuf,$this->searchBrands($search,1,'ALL'));
		$resultBuf['title'] = 'Компании';
		$resultBuf['name'] = 'companies';
		$result[] = array_merge($resultBuf,$this->searchCompany($search,1,'ALL'));
		$resultBuf['title'] = 'Новости';
		$resultBuf['name'] = 'news';
		$result[] = array_merge($resultBuf,$this->searchNews($search,1,'ALL'));
		return $result;
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
	
	public function viewResponceStatus($arError = array())
	{
		if($arError['ERROR'])
		{
			foreach($arError as $error)
			{
					//$response = new \Bitrix\Main\HttpResponse();
				//$response->addHeader('Content-Type', 'text/plain');
			//	$response->setContent($error); 
			}
			http_response_code(400); 
		}else{
			http_response_code(200);
		}
		
		return true;
	}
	
	public function viewJson($param = array())
	{
		if($param)
		{
			if($param['ERROR'])
			{
				$buf = array();
				foreach($param['ERROR'] as $error)
				{
					$error = explode('<br>',$error);
					foreach($error as $item)
					{
						$buf[] = $item;
					}
				}
				$param['ERROR'] = $buf;
			}
			echo json_encode($param, JSON_UNESCAPED_UNICODE);
		}
		return '';
	}
	
	public function saveMaleNews($name,$email)
	{ 
		global $USER;
		$arResult = array();
		$el = new \CIBlockElement;

		$PROP['USER'] = $USER->GetID();
		$PROP['EMAIL'] = $email;
		$PROP['NAME'] = $name;
		$arLoadProductArray = Array(
		  "MODIFIED_BY" => $USER->GetID(), // элемент изменен текущим пользователем
		  "IBLOCK_SECTION_ID" => false,          // элемент лежит в корне раздела
		  "IBLOCK_ID" => mailNews,
		  "PROPERTY_VALUES" => $PROP,
		  "NAME" => $name, 
		  "ACTIVE" => "Y",            // активен
		  );
		try
		{
			if($this->regEmail($email)){
				if($PRODUCT_ID = $el->Add($arLoadProductArray)){
				}else{
					$arResult['ERROR'][] = 'Подписка на новости не сохранена ! ' . strip_tags($el->LAST_ERROR); 
				}
				if(!$PRODUCT_ID)
				{
					$arResult['ERROR'][] = 'Элемент не сохранен!';
				}
			}else{
				$arResult['ERROR'][] = 'Поле Email некорректно';
			}
			
		}catch (\Bitrix\Main\SystemException $e) 
		{
			$e->getMessage();
			$arResult['ERROR'][]=$el->LAST_ERROR;
		}
		return $arResult;
	}
	
	public function getBrandDetail($code)
	{
		$arResult = array();
		$res = \CIBlockElement::GetList(array('NAME'=>'ASC'), array('IBLOCK_ID'=>IBLOCK_BRAND,'CODE'=>$code), false, false, array('ID','NAME','PREVIEW_PICTURE','PREVIEW_TEXT','DETAIL_TEXT','CODE',
																																	'PROPERTY_VIDEO_LINK','PROPERTY_GALLERY','PROPERTY_TAGS_HIGHLOAD',
																																	'PROPERTY_LOGO','PROPERTY_SUBTITLE','PROPERTY_AGE_FROM','PROPERTY_AUDITORIUM',
																																	'PROPERTY_PRESENTATION','PROPERTY_PRODUCTS','PROPERTY_VIDEO_IMAGE','PROPERTY_TAGS'
																																	,'PROPERTY_PRODUCT_GROUP','PROPERTY_COMPANY'));
		while ($data = $res->GetNextElement()) 
		{
			$arFields = $data->GetFields();
			$arGallery = array();
			if($arFields['PROPERTY_GALLERY_VALUE'])
			{
				foreach($arFields['PROPERTY_GALLERY_VALUE'] as $imgId)
				{
					$buf = \CFile::GetPath($imgId);
					if($buf)
						$arGallery[] = $buf;
				}
			}
			
			$arProducts=array();
			if($arFields['PROPERTY_PRODUCTS_VALUE'])
			{
				foreach($arFields['PROPERTY_PRODUCTS_VALUE'] as $imgId)
				{
					$buf = \CFile::GetPath($imgId);
					if($buf)
						$arProducts[] = $buf;
				}
			}
			$link = ''; 
			if($arFields['PROPERTY_VIDEO_LINK_VALUE'])
			{
				$link = str_replace('watch?v=','',$arFields['PROPERTY_VIDEO_LINK_VALUE']);
				$link = explode('/',$link);
				$link = $link[count($link)-1];
			}
			$presentation = array();
			if($arFields['PROPERTY_PRESENTATION_VALUE'])
			{
				$buf = \CFile::GetFileArray($arFields['PROPERTY_PRESENTATION_VALUE']);
				$type = $buf['CONTENT_TYPE'];
				if($type == 'application/pdf')
					$type = '[pdf]';
				$presentation = array(
					'SRC' => $buf['SRC'],
					'TYPE' => $type,
					'NAME' => $buf['ORIGINAL_NAME'], 
				);
			}
			
			$arResult = array(
				'NAME' => $arFields['NAME'], 
				'DETAIL_TEXT' => $arFields['~DETAIL_TEXT'], 
				'PREVIEW_TEXT' => $arFields['~PREVIEW_TEXT'], 
				'SUBTITLE' => $arFields['PROPERTY_SUBTITLE_VALUE']['TEXT'], 
				'ID' => $arFields['ID'],
				'CODE' => $arFields['CODE'],
				'GALLERY' => $arGallery,
				'PRODUCTS' => $arProducts,
				'VIDEO_IMAGE' => \CFile::GetPath($arFields['PROPERTY_VIDEO_IMAGE_VALUE']),
				'LOGO' => \CFile::GetPath($arFields['PROPERTY_LOGO_VALUE']),
				'VIDEO_LINK' => $link,
				'TAGS' => $arFields['PROPERTY_TAGS_HIGHLOAD_VALUE'],
				'TAGS' => $arFields['PROPERTY_TAGS_HIGHLOAD_VALUE'],
				'AGE_FROM' => $arFields['PROPERTY_AGE_FROM_VALUE'] ?? 0,
				'GROUP' => $arFields['PROPERTY_PRODUCT_GROUP_VALUE'],
				'LICENSE' => $arFields['PROPERTY_TAGS_VALUE'],
				'AUDITORIUM' => $arFields['PROPERTY_AUDITORIUM_VALUE'],
				'COMPANY' => $arFields['PROPERTY_COMPANY_VALUE'],
				'PRESENTATION' => $presentation,
				'PREVIEW_PICTURE' => \CFile::GetPath($arFields['PREVIEW_PICTURE']),
			);
			
			//company
			if ($arFields['PROPERTY_COMPANY_VALUE']) {
				$rsUser = \CUser::GetByID($arResult['COMPANY']);
				$arUser = $rsUser->Fetch();
				$arResult["COMPANYS"] = $arUser['WORK_COMPANY'];
				$arResult["EMAIL"] = $arUser['EMAIL'];
				$arResult["PERSONAL_WWW"] = $arUser["PERSONAL_WWW"];
			}


				//another brands
				$res = \CIBlockElement::GetList(array('NAME'=>'ASC'),array("IBLOCK_ID" => IBLOCK_BRAND, "ACTIVE" => "Y", "PROPERTY_COMPANY" => $arResult['COMPANY'], "!=ID" => $arResult['ID']),false,false,array("ID", 'CODE',"PREVIEW_PICTURE", "NAME",
																																																	"DETAIL_PAGE_URL",'PROPERTY_PRODUCTS','PROPERTY_LOGO','PROPERTY_SKIDKA'));
				while ($ob = $res->GetNextElement()) {
					$arFields = $ob->GetFields();
					$arProducts =array();
					foreach($arFields['PROPERTY_PRODUCTS_VALUE'] as $img)
					{
						if(\CFile::GetPath($img))
							$arProducts[] = \CFile::GetPath($img);
					}
					$pic = \CFile::ResizeImageGet($arFields['PREVIEW_PICTURE'], array('width' => 300, 'height' => 400), BX_RESIZE_IMAGE_PROPORTIONAL, false);
					$arResult['ANOTHER_BRANDS'][] = array(
						'ID' => $arFields['ID'],
						'CODE' => $arFields['CODE'],
						'NAME' => $arFields['NAME'],
						'PREVIEW_PICTURE' => $pic['src'] ?? \CFile::GetPath($arFields['PREVIEW_PICTURE']),
						'LOGO' => \CFile::GetPath($arFields['PROPERTY_LOGO_VALUE']),
						'PRODUCTS' => $arProducts ,
						//'SKIDKA' => $arFields['PROPERTY_SKIDKA_VALUE'] ,
					);
				}
		}
		//echo '<pre>'; print_r($arResult); echo '</pre>';   
		
		return $arResult;
	}
	
	public function regEmail($email)
	{
		if(preg_match(RegEmail,$email))
		{
			return true;
		}else{
			return false;
		}
	}
	
	public function addPurchase($name,$email,$text)
	{
		if($name && $email && $text)
		{
			if($this->regEmail($email)){
				$id = $this->initHg(HighloadPurchase)::add(array('UF_NAME' => $name,'UF_EMAIL'=>$email,'UF_TEXT'=>$text,'UF_ID'=>$id));
				if(!$id)
				{
					$result['ERROR'][] = 'Элемент не сохранен!';
				}
			}else{
				$result['ERROR'][] = 'Поле Email некорректно';
			}
		}else{
			$result['ERROR'][] = 'Не задано одно из обязательных полей!';
		}
		return $result;
	}
	
	public function addQuestions($name,$email,$text)
	{
		if($name && $email && $text)
		{
			if($this->regEmail($email)){
				$id = $this->initHg(HighloadQuestions)::add(array('UF_NAME' => $name,'UF_EMAIL'=>$email,'UF_TEXT'=>$text));
				if(!$id)
				{
					$result['ERROR'][] = 'Элемент не сохранен!';
				}
			}else{
				$result['ERROR'][] = 'Поле Email некорректно';
			}
		}else{
			$result['ERROR'][] = 'Не задано одно из обязательных полей!';
		}
		return $result;
	}
	
	public function getBottomMenu($ID = BottomMenuId)
	{
		$arFilter = array('IBLOCK_ID' =>$ID, 'DEPTH_LEVEL'=>array(1,2,3)); //получим рубрики это 1й уровень разделов
		$rsSections = \CIBlockSection::GetList(array('SORT' => 'ASC'), $arFilter,false,array('DEPTH_LEVEL','LEFT_MARGIN','SECTION_ID','ID','NAME','UF_*'));
		while ($arSection = $rsSections->GetNext())
		{
			$section = array(
				'SECTION_ID'=>$arSection['ID'],
				'NAME'=>$arSection['NAME'],
				'DEPTH_LEVEL'=>$arSection['DEPTH_LEVEL'],  
				'URL'=>$arSection['UF_URL'],  
			);
			$arResult['SECTION'][$arSection['LEFT_MARGIN']] = $section;
			if($arSection['DEPTH_LEVEL'] == 1)
			{
				$arBuf[$arSection['ID']] = $section;
			}
		}
		$depthCode1 = 0;
		$arMenu = array();
		ksort($arResult['SECTION']);
		//теперь распределим разделы по 3м уровням!
		foreach($arResult['SECTION'] as $key => $item)
		{
			if($item['DEPTH_LEVEL'] == 1){
				$arMenu['items'][$item['SECTION_ID']] = $item; 
				$depthLevel1 = $key; // мы сейчас на верхнем уровне 
				$depthCode1 = $item['SECTION_ID'];
			}else if($item['DEPTH_LEVEL'] == 2)
			{
				$arMenu['items'][$depthCode1]['podmenu'][$item['SECTION_ID']] = $item;
			}
		}
		return $arMenu;
	}
	
	public function getDetailCompany($id)
	{
		$result = array();
		if($id)
		{
			$dbUser = \Bitrix\Main\UserTable::getList(array(
					'select' => array('ID','EMAIL', 'NAME','WORK_COMPANY','UF_DESCRIPTION','UF_SAIT','UF_LOGOO','UF_PHOTOS','UF_VIDEO_SRC','UF_VIDEO_IMG','WORK_WWW','UF_PRESENTS','PERSONAL_MOBILE','UF_URADRESS','UF_CONTACT','UF_LICENSE'),
					'filter' => array('ID' => $id)
				));
				while ($arUser = $dbUser->fetch()){
					$result[] = array(
						'ID' => $arUser['ID'],
						'name' => $arUser['NAME'] ?? null,
						'companyName' => $arUser['WORK_COMPANY'] ?? null,
						'description' => $arUser['UF_DESCRIPTION'] ?? null,
						'webSite' => $this->addHttps($arUser['WORK_WWW'] ?? $arUser['UF_SAIT']) ?? null,
						'logo' => \CFile::GetPath($arUser['UF_LOGOO']) ?? null,  
						'galleryHeader' => $this->getImages($arUser['UF_PHOTOS'],array('width'=>1100,'height'=>500)),
						'youtube' => $this->getIdYoutube($arUser['UF_VIDEO_SRC']),
						'youtubeImg' => \CFile::GetPath($arUser['UF_VIDEO_IMG']) ?? null,  
						'presentation' => \CFile::GetPath($arUser['UF_PRESENTS']) ?? null,  
						'license' => $this->getUserPropertyValue('UF_LICENSE',$arUser['UF_LICENSE']),  
						'email' =>$arUser['EMAIL'] ?? null,  
						'mobile' => $arUser['PERSONAL_MOBILE'] ?? null,  
						'address' => $arUser['UF_URADRESS'] ?? null,  
						'contaсtsName' => $arUser['UF_CONTACT'] ?? null,  
						'brands' => $this->getBrandsByCompany($id),  
					);
				}
		}else{
			$result['ERROR'] = 'Нужен Id компании для дальнейшей работы';
		}
		return $result;
	}
	
	public function addHttps($link)
	{
		if($link && str_replace('http','',$link) == $link)
			$link = 'https://' . $link;
		return $link;
	}
	
	public function getBrandsByCompany($companyId)
	{
		$result['items'] = array();
		if($companyId)
		{
			$res = \CIBlockElement::GetList(array(), array('IBLOCK_ID' => IBLOCK_BRAND,'PROPERTY_COMPANY' => $companyId), false, false, array('ID','CODE','NAME','PROPERTY_LOGO','PROPERTY_PRODUCTS'));
			while ($data = $res->GetNextElement()) 
			{
				$arFields = $data->GetFields();
				$result['items'][] = array(
					'title' => $arFields['NAME'],
					'logo' => \CFile::GetPath($arFields['PROPERTY_LOGO_VALUE']),
					'url' => '/catalog/' . $arFields['CODE'] . '/',
					'gallery' => $this->getImages($arFields['PROPERTY_PRODUCTS_VALUE']),
				);
			}
		}
		return $result;
	}
	
	public function getImages($arImg,$arResize = array())
	{
		$result = array();
		if(!$arResize['width'] || !$arResize['height'])
		{
			foreach($arImg as $img)
			{
				$url = \CFile::GetPath($img);
				if($url) 
					$result[] = $url;
			}
		}else{
			foreach($arImg as $img)
			{
				$url = \CFile::ResizeImageGet($img ,  array("width" => $arResize['width'], "height" => $arResize['height']));
				if($url['src']) 
				{
					$result[] = $url['src'];
				}else{
					if(\CFile::GetPath($img))
						$result[] = \CFile::GetPath($img); 
				}
			}
		}
		return $result;
	}
	
	public function getIdYoutube($url)
	{
		$link = ''; 
		if($url)
		{
			$link = str_replace('watch?v=','',$url);
			$link = explode('/',$link);
			$link = $link[count($link)-1];
		}
		return $link;
	}
	
	public function getUserPropertyValue($propertyName,$arId)
	{	
		$result = array();
		if($arId)
		{
			$obEnum = new \CUserFieldEnum;
			$rsEnum = $obEnum->GetList(array(), array("USER_FIELD_NAME" => $propertyName,'ID' => $arId));
			$enum = array();
			while($arEnum = $rsEnum->Fetch())
			{
				$result[] = $arEnum["VALUE"];
			}
		}
		return $result;
	}
	
	public function companyTitle($id)
	{
		$result = array();
		if($id)
		{
			$dbUser = \Bitrix\Main\UserTable::getList(array(
					'select' => array('ID','WORK_COMPANY'),
					'filter' => array('ID' => $id)
				));
				while ($arUser = $dbUser->fetch()){
					$result = array(
						'ID' => $arUser['ID'],
						'companyName' => $arUser['WORK_COMPANY'] ?? null,
					);
				}
		}else{
			$result['ERROR'] = 'Нужен Id компании для дальнейшей работы';
		}
		if(!$result['companyName'])
			$result = false;
		
		return $result;
	}
	
	public function getPropertyList($code,$iblock)
	{
		$result = array();
		if($code)
		{
			$db_enum_list = \CIBlockProperty::GetPropertyEnum($code, Array('ID' =>'ASC'), Array("IBLOCK_ID"=>$iblock));
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
}
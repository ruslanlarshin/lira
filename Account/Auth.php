<?php

namespace Larshin\Account;
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
use Bitrix\Main\UserTable,
	Bitrix\Main,
	Bitrix\Main\SystemException,
	Bitrix\Main\Loader,
	Bitrix\Highloadblock as HL,
	Bitrix\Main\Entity;	
	
Loader::includeModule("highloadblock"); 
	
define("IBLOCK_BRAND", 12); 
define("IBLOCK_CONTANTS", 9);
define("IBLOCK_DOPCONTACTS_CODE", 'dopcontacts');
define("OWNER_ID", 4);
define("RegUrl", "/^(http:\/\/|https:\/\/)?[0-9a-zA-Zа-яА-ЯёЁ]{2,23}+[.][0-9a-zA-Zа-яА-ЯёЁ.=?\/]{2,26}+$/");
define("RegUrlMax", "/_^(?:(?:https?|ftp)://)(?:\S+(?::\S*)?@)?(?:(?!10(?:\.\d{1,3}){3})(?!127(?:\.\d{1,3}){3})(?!169\.254(?:\.\d{1,3}){2})(?!192\.168(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,})))(?::\d{2,5})?(?:/[^\s]*)?$_iuS+/");
define("HighloadTags", 9);
define("HighloadTagsBrands", 10);

class Auth
{
    public $id;
	public $name;

    public function __construct(int $id=0)
    {
		global $APPLICATION;
		global $USER;
		if(!$USER->IsAuthorized())
		{
			
		}
		if(!$id){
			$this->id=$USER->GetID();
		}		
		return true;	
    }
	
	public function enter($login,$password,$save = 'N')
	{
		$result = array();
		global $USER;
		if(!$USER->IsAuthorized())  
		{
			try{
				global $USER;
				if (!is_object($USER)) 
					$USER = new \CUser;
				 
				if(str_replace('@','',$login) != $login)
				{
					$dbUser = \Bitrix\Main\UserTable::getList(array(
						'select' => array('ID','EMAIL','LOGIN','ACTIVE'),
						'filter' => array('EMAIL' => $login),
					));
					while ($arUser = $dbUser->fetch()){
						$login2 = $arUser['LOGIN'];
						if($arUser['ACTIVE'] == 'N')
							$result['ERROR'][] = 'Ваш аккаунт на данный момент не активен, обратитесь к администратору';
					}
				}
				$dbUser = \Bitrix\Main\UserTable::getList(array(
						'select' => array('ID','EMAIL','LOGIN','ACTIVE'),
						'filter' => array('LOGIN' => $login),
					));
					while ($arUser = $dbUser->fetch()){
						if($arUser['ACTIVE'] == 'N')
							$result['ERROR'][] = 'Ваш аккаунт на данный момент не активен, обратитесь к администратору';
					}
					
				if(!$result['ERROR'])
					$resultBuf = $USER->Login($login, $password, $save);
				if($resultBuf['TYPE'] != 'ERROR')
				{
			
				}else{
					if(!$result['ERROR'])
					{
						$resultBuf = $USER->Login($login2, $password, $save);
					}
					if($resultBuf['TYPE'] == 'ERROR')
						$result['ERROR'][] = $resultBuf['MESSAGE'];
					
				}
				\Bitrix\Main\Context::getCurrent()->getResponse()->writeHeaders();      
			}catch(Bitrix\Main\SystemException $e){
				$result['ERROR'][] = $e;
			}
		}else{
			$result['ERROR'][] = 'Вы уже авторизованы!';
		}
		return $result;
	}
	
	public function logout() 
	{
		$result = array();
		try{
			global $USER;
			$USER->Logout();
			\Bitrix\Main\Context::getCurrent()->getResponse()->writeHeaders();
		}catch(Bitrix\Main\SystemException $e){
			$result['ERROR'][] = $e;
		}
		return $result;
	}
	
	public function  gen_password($length = 6)
	{				
		$chars = 'qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP'; 
		$size = strlen($chars) - 1; 
		$password = ''; 
		while($length--) {
			$password .= $chars[random_int(0, $size)]; 
		}
		return $password;
	}
 
	public function registrationUr($arData)
	{
		$result = array();
		$result = $this->checkEmail($arData['email']);
		global $USER;
		if(!$result['ERROR'])
		if(!$USER->IsAuthorized()) 
		{
			$result = $USER->Register($arData['login'], $arData['name'], $arData['surname'], $arData['password'], $arData['confirm'], $arData['email']);
			$id = $result['ID']; 
			if($id)
			{
				$user = new \CUser;
				$confirm = $this->gen_password(8);
				$arResult['SUCCESS'] = $user->Update($id, array(
						'UF_CONFIRM' => $confirm, 
						"GROUP_ID" => array(1,2,6),
						'WORK_COMPANY' => $arData['companyName'],
						'WORK_POSITION' => $arData['position'],
						'UF_SAIT' => $arData['webSite'],
						'UF_URADRESS' => $arData['address'], 
						'UF_CONTACT' => $arData['contact'],
					)
				);
				$SITE_ID = 's1';
				$EVENT_TYPE = 'NEW_USER_CONFIRM'; 
				$arFeedForm = array(
					"USER_ID" => $id, 
					"CONFIRM_CODE" =>$confirm,
					"EMAIL" => $arData['email'],
				);
				$resultSend = \CEvent::Send($EVENT_TYPE, $SITE_ID, $arFeedForm );
				if(!$resultSend)
					$result['ERROR'][] = 'Письмо с подвтерждением не отправлено!';
			}else{
				$result['ERROR'][] = $result['MESSAGE'];
			}
			
		}else{
			$result['ERROR'][] = 'Вы уже авторизованы, регистрация невозможна!';
		}
		//echo $USER->GetID(); // ID нового пользователя
		return $result;
	}
 
	public function registrationPhysical($arData)
	{
		$result = array();
		$result = $this->checkEmail($arData['email']);
		global $USER;
		if(!$result['ERROR'])
		if(!$USER->IsAuthorized()) 
		{
			$result = $USER->Register($arData['login'], $arData['name'], $arData['surname'], $arData['password'], $arData['confirm'], $arData['email'],'NO_SEND');
			$id = $result['ID']; 
			if($id)
			{
				$user = new \CUser;
				$confirm = $this->gen_password(8);
				$arResult['SUCCESS'] = $user->Update($id, array('UF_CONFIRM' => $confirm));
				$SITE_ID = 's1';
				$EVENT_TYPE = 'NEW_USER_CONFIRM2'; 
				$arFeedForm = array(
					"USER_ID" => $id, 
					"CONFIRM_CODE" =>$confirm,
					"EMAIL" => $arData['email'],
				);
				$resultSend = \CEvent::Send($EVENT_TYPE, $SITE_ID, $arFeedForm );
				if(!$resultSend)
					$result['ERROR'][] = 'Письмо с подвтерждением не отправлено!';
			}else{
				$result['ERROR'][] = $result['MESSAGE'];
			}
			
		}else{
			$result['ERROR'][] = 'Вы уже авторизованы, регистрация невозможна!';
		}
		//echo $USER->GetID(); // ID нового пользователя
		return $result;
	}
	
	public function requestChange($login, $email)
	{
		$id = 0 ;
		$arEmail = array();
		$confirm = $this->gen_password(8);
		if($login || $email)
		{
			$dbUser = \Bitrix\Main\UserTable::getList(array(
				'select' => array('ID','EMAIL','LOGIN','NAME','LAST_NAME'),
				'filter' => array("LOGIC" => "OR",
								array('LOGIN' => $login),	
								array('EMAIL' => $email),	
							),
			));
			while ($arUser = $dbUser->fetch()){
				if(!$id)
				{
					$id = $arUser['ID'];
					
					$arEmail = array(
						'USER_ID' => $arUser['ID'],
						'LOGIN' => $arUser['LOGIN'],
						'NAME' => $arUser['NAME'],
						'LAST_NAME' => $arUser['LAST_NAME'],
						'CHECKWORD' => $confirm,
						'EMAIL' => $email ?? $arUser['EMAIL'],
					);
				}
			}
			if($id)
			{	
				$SITE_ID = 's1';
				$EVENT_TYPE = 'USER_PASS_REQUEST'; 
				$resultSend = \CEvent::Send($EVENT_TYPE, $SITE_ID, $arEmail );
				$user = new \CUser;
				$arResult['SUCCESS'] = $user->Update($arEmail['USER_ID'], array('UF_CONFIRM_PASSWORD' => $confirm));
				if(!$resultSend)
					$result['ERROR'][] = 'Письмо с подвтерждением не отправлено!';
				
			}else{
				$result['ERROR'][] = 'Пользователь ' . $login . ' - ' . $email . ' не найден';
			}
		}else{
			$result['ERROR'][] = 'Нужно ввести хотя бы одно значение';
		}
		return $result;
	}
	
	public function confirmUser($id,$confirm)
	{
		$result = array();
		try
		{
			if($id && $confirm)
			{
				$dbUser = \Bitrix\Main\UserTable::getList(array(
					'select' => array('ID', 'NAME','UF_CONFIRM','ACTIVE'),
					'filter' => array('ID' => $id)
				));
				if ($arUser = $dbUser->fetch()){
					$error = 'N';
					if($arUser['ACTIVE'] == 'Y')
					{
						$result['ERROR'][] = 'Нельзя активировать уже активную запись';
						$error = 'Y';
					}
					if(!$arUser['UF_CONFIRM'])
					{
						$result['ERROR'][] = 'Данная учетка не может быть активирована- нет строки подтверждения в базе';
						$error = 'Y';
					}
					if($arUser['UF_CONFIRM'] != $confirm)
					{
						$result['ERROR'][] = 'Неверная строка подтверждения';
						$error = 'Y';
					}
					if($error !='Y')
					{
						$user = new \CUser;
						$dbUser = \Bitrix\Main\UserTable::getList(array(
							'select' => array('ID','EMAIL','LOGIN','ACTIVE'),
							'filter' => array('ID' => $id),
						));
						while ($arUser = $dbUser->fetch()){
							$login2 = $arUser['LOGIN'];
							if($arUser['ACTIVE'] == 'Y')
								$result['ERROR'][] = 'Ваш аккаунт на данный момент уже активен, обратитесь к администратору';
							
							$arFeedForm = array(
								"USER_ID" => $id, 
								'EMAIL' => $arUser['EMAIL'],
								'LOGIN' => $arUser['LOGIN'],
							);
						}
						
						$SITE_ID = 's1';
						$EVENT_TYPE = 'USER_REQUEST_REGISTRATION'; 
						$resultSend = \CEvent::Send($EVENT_TYPE, $SITE_ID, $arFeedForm );
						//$arResult['SUCCESS'] = $user->Update($id, array('ACTIVE'=>'Y','UF_CONFIRM' => $confirm));
					}
				}
			}else{
				$result['ERROR'][] = 'Для потдверждения учетной записи необходимы id пользователя и строка подтверждения!';
			}
		}
		catch (Exception $e)
		{
			$result['ERROR'][] = $e->getMessage();
		}
		return $result;
	}
	
	public function checkEmail($email)
	{
		$result = array();
		$dbUser = \Bitrix\Main\UserTable::getList(array(
				'select' => array('ID','EMAIL','LOGIN'),
				'filter' => array('EMAIL'=>$email),
			));
			while ($arUser = $dbUser->fetch()){
				$result['ERROR'] = array();
				$result['ERROR'][] = 'Пользователь с данным email уже зарегистрирован на портале!';
			}
		return $result;
	}
	
	public function changePassword($id,$code,$NEW_PASSWORD, $CONFIRM_PASSWORD)
	{
		$result = array();
		$arUserChange = 0;
		if(!$id)
			$result['ERROR'][] = 'Для смены пароля нреобходим id  пользователя';
		if(!$code)
			$result['ERROR'][] = 'Для смены пароля нреобходим code  пользователя';
		
		if(!$result['ERROR'])
		{
			$dbUser = \Bitrix\Main\UserTable::getList(array(
				'select' => array('ID','EMAIL','LOGIN','NAME','UF_CONFIRM_PASSWORD'),
				'filter' => array('ID'=>$id),
			));
			while ($arUser = $dbUser->fetch()){
				$arUserChange = array(
					'ID' => $arUser['ID'],
					'CODE' => $arUser['UF_CONFIRM_PASSWORD'],
				);
			}
			if($arUserChange['ID'])
			{
				if($arUserChange['CODE'] != $code)
				{
					$result['ERROR'][] = 'Неверный код подтвержения ' . $code . ' для смены пароля';
				}else{
					$user = new \CUser;
					$fields['PASSWORD'] = $NEW_PASSWORD;
					$fields['CONFIRM_PASSWORD'] = $CONFIRM_PASSWORD;
					try
					{
						$arResult['SUCCESS'] = $user->Update($id, $fields);
						$arResult['SUCCESS'] = true;
					}catch (\Bitrix\Main\SystemException $e) 
					{
						$e->getMessage();
						$result['ERROR'][]=$user->LAST_ERROR;
					}
				}
			}else{
				$result['ERROR'][] = 'Пользователь  с id ' . $id . 'не найден в системе!';
			}
		}
		return $result;
	}	
	
}
	
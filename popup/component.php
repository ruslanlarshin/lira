<?
global $DB;
global $USER;
global $APPLICATION;
global $INTRANET_TOOLBAR;

CModule::IncludeModule("iblock");
CModule::IncludeModule("sale");
CModule::IncludeModule("catalog");
//����� �������� ��������� ��� ��������-��� �������-������� ��������� �������� �� �����!!
$arOption=array();
$arError=array(); 
$time=3600000;// ����� ����� ���� � �������� -��� ���������� � ������������-0
$time=0;// ����� ����� ���� � �������� -��� ���������� � ������������-0

if($this->StartResultCache($time, array($arOption))){ //��� ������� �� �������� $arParams � $arOption-���� ������� ����� �� �����������-�������� �������� ����������
	if($arError){ //���� ������ ��������-�� ��� �� ���������
		$this->AbortResultCache();
		ShowError(GetMessage("IBLOCK_MODULE_NOT_INSTALLED"));
		return;
	}
	$arResult=array();
	$arResult['PARAM']=$arParams;
	
	//$arResult["ID"]=1234567; //����� ������� ��������� ������-������� ����� ������������
	$this->IncludeComponentTemplate();
	//echo '���������� ���� ������ � ���������� � ���';
	if($arError)
	{
		$this->AbortResultCache();
		ShowError("ERROR");
		@define("ERROR_404", "Y");
		if($arParams["SET_STATUS_404"]==="Y")
			CHTTP::SetStatus("404 Not Found");
	}
}else{
	//echo '������ ���� � ����!<BR>';// ���������� �����, ����� �������� ���-���������� ��� �������� ������ ���� � �������� ��� ����!!
}
?>
<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if( !CModule::IncludeModule("iblock") ) die();

global $arComponentParameters;

$arProp = $arComponentParameters["PARAMETERS"]["NAME_PROP"]["VALUES"];

$arTemplateParameters = array(
	"PARAMS" => Array(
		"PARENT" => "COMMON",
		"NAME" => GetMessage("PARAMS"),
		"TYPE" => "LIST",
		"MULTIPLE" => "Y",
		"VALUES" => $arProp,		
	),
	
	"COND_PARAMS" => Array(
		"PARENT" => "COMMON",
		"NAME" => GetMessage("COND_PARAMS"),
		"TYPE" => "LIST",
		"MULTIPLE" => "Y",
		"VALUES" => $arProp,		
	)
);

?>
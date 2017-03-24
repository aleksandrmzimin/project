<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

define ('DESCRIPTION_SIZE', 500);

if(!CModule::IncludeModule("iblock")) die();

global $bCatalog;
asd
as
d
asde('catalog');
$bCurrency = CModule::IncludeModule('currency');

//$file = fopen($_SERVER['DOCUMENT_ROOT']. "/yandex.txt","w+");

/*
function testMemory($msg){
	static $memoryUsage = 0;
	$newMemory = memory_get_usage();
	echo $msg, ', total: ', $newMemory, ' diff: ', ($newMemory - $memoryUsage), "\n";
	$memoryUsage = $newMemory;
}
*/

/*************************************************************************
	Processing of received parameters
*************************************************************************/

if($componentTemplate == 'Realty_YRL')
	$arParams['IBLOCK_ORDER'] = 'Y';

if(!isset($arParams["CACHE_TIME"]))
	$arParams["CACHE_TIME"] = 3600;
	
if(!isset($arParams["DO_NOT_INCLUDE_SUBSECTIONS"]))
	$arParams["DO_NOT_INCLUDE_SUBSECTIONS"] = "N";

if(!is_array($arParams["PROPERTY_CODE"]))
	$arParams["PROPERTY_CODE"] = array();

if(!$arParams['SKU_PROPERTY'])
	$arParams['SKU_PROPERTY'] = 'PROPERTY_CML2_LINK';

	
$arParams['SKU_PROPERTY'] = strtoupper($arParams['SKU_PROPERTY']);

foreach($arParams["PROPERTY_CODE"] as $key=>$value)
{
	if($value==="")
		unset($arParams["PROPERTY_CODE"][$key]);
	else
		$arProperty[]="PROPERTY_". trim($value);
}

if ($arParams['IBLOCK_AS_CATEGORY'] != 'N')
	$arParams['IBLOCK_AS_CATEGORY']  = 'Y';



$arParams["IBLOCK_TYPE"] = trim($arParams["IBLOCK_TYPE"]);

$arParams["COMPANY"] = trim($arParams["COMPANY"]);

if(!is_array($arParams["IBLOCK_ID_IN"]))
	$arParams["IBLOCK_ID_IN"] = array();
foreach($arParams["IBLOCK_ID_IN"] as $k=>$v)
	if($v==="")
		unset($arParams["IBLOCK_ID_IN"][$k]);

if ( (count($arParams["IBLOCK_ID_IN"]) > 0 && $arParams["IBLOCK_ID_IN"][0] === '0') )
	$arParams["IBLOCK_ID_IN"] = '';


if(!is_array($arParams["IBLOCK_ID_EX"]))
	$arParams["IBLOCK_ID_EX"] = array();
foreach($arParams["IBLOCK_ID_EX"] as $k=>$v)
	if($v==="")
		unset($arParams["IBLOCK_ID_EX"][$k]);

/* old sort
if(strlen($arParams["ELEMENT_SORT_FIELD"])<=0)
	$arParams["ELEMENT_SORT_FIELD"]="sort";
if($arParams["ELEMENT_SORT_ORDER"]!="desc")
	 $arParams["ELEMENT_SORT_ORDER"]="asc";
*/

if(strlen($arParams["FILTER_NAME"])<=0 || !preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $arParams["FILTER_NAME"]))
{
	$arrFilter = array();
}
else
{
	global $$arParams["FILTER_NAME"];
	$arrFilter = ${$arParams["FILTER_NAME"]};
	if(!is_array($arrFilter))
		$arrFilter = array();
}

if($arParams["SHOW_PRICE_COUNT"]<=0)
	$arParams["SHOW_PRICE_COUNT"]=1;



$arParams["CACHE_FILTER"]=($arParams["CACHE_FILTER"]=="Y");
if(!$arParams["CACHE_FILTER"] && count($arrFilter)>0)
	$arParams["CACHE_TIME"] = 0;


$arParams["PRICE_VAT_INCLUDE"] = $arParams["PRICE_VAT_INCLUDE"] !== "N";

if (empty($arParams["DISCOUNTS"])) $arParams["DISCOUNTS"] = "DISCOUNT_CUSTOM";

function getBaseCurrencyTempl()
{
	if ( CModule::IncludeModule('currency') )
	{
		$res = CCurrency::GetList( ($by="name"), ($order="asc"), "RU" );
		while( $arRes = $res->Fetch() )
		{
			if ( $arRes["AMOUNT"] == 1 )
				return $arRes["CURRENCY"];
		}
	}
}


if (!function_exists("yandex_replace_special"))
{
	function yandex_replace_special($arg)
	{
		$ent = html_entity_decode($arg[0], ENT_QUOTES, LANG_CHARSET);
		
		if ($ent == $arg[0]) return '';
		return $ent;
	}
}

if (!function_exists("yandex_text2xml"))
{
	function yandex_text2xml($text, $bHSC = true, $bDblQuote = false)
	{
	//	$text = $GLOBALS['APPLICATION']->ConvertCharset($text, LANG_CHARSET, 'windows-1251');

		$bDblQuote = (true == $bDblQuote ? true: false);

		if ($bHSC)
		{
			$text = htmlspecialcharsBx($text);
			if ($bDblQuote)
				$text = str_replace('&quot;', '"', $text);
		}
		$text = preg_replace("/[\x1-\x8\xB-\xC\xE-\x1F]/", "", $text);
		$text = str_replace("'", "&apos;", $text);
		return $text;
	}
}

function convert_price (&$arOldPrices)
{
	$arCurrencies = array();
	$obCurrencies = CCurrency::GetList($by = 'sort', $order = 'asc');
	while ($arCurrency = $obCurrencies->Fetch()) {
		$arCurrency['DECIMALS'] = intval($arCurrency['DECIMALS']);
		if ($arCurrency['DECIMALS'] < 0) $arCurrency['DECIMALS'] = 0;
		$arCurrencies[$arCurrency['CURRENCY']] = $arCurrency;
	}

	$arOldPrices["PRICE"] = CCurrencyRates::ConvertCurrency( $arOldPrices["PRICE"], $arOldPrices["CURRENCY"], $arOldPrices["CURRENCY_CONVERT"] );
	$arOldPrices["CURRENCY"] = $arOldPrices["CURRENCY_CONVERT"];

	$arOldPrices["PRICE"] = round($arOldPrices["PRICE"], $arCurrencies[$arOldPrices["CURRENCY"]]['DECIMALS']);
}

if ($arParams["OLD_PRICE_LIST"] == "TYPE_PRICE")
{
	if ($arParams["DISCOUNTS"] == "DISCOUNT_CUSTOM"){
		function yenisite_yandex_GetOldPrice ($product_id, &$arOldPrices, &$arOffers,$arPruductPrices)
		{	
			$arUserGroups = $GLOBALS["USER"]->GetUserGroupArray();
			$price_doscount = 0;
				foreach ($arPruductPrices as $arProductPrice) 
				{
					$arDiscounts = CCatalogDiscount::GetDiscountByProduct($product_id, $arUserGroups,  "N", $arProductPrice['CATALOG_GROUP_ID'], SITE_ID, array());
					foreach($arDiscounts as &$arDiscount)
					{
						switch ($arDiscount["VALUE_TYPE"]) {
							case 'P': $price_doscount = $arProductPrice["PRICE"] - $arDiscount["VALUE"] * $arProductPrice["PRICE"] / 100;
								break;
							case 'F': $price_doscount = $arProductPrice["PRICE"] - $arDiscount["VALUE"];
								break;
							default:  $price_doscount = $arDiscount["VALUE"];
								break;
						}
					}
					$arDiscounts = null;
					if($arProductPrice['PRODUCT_ID'] == $product_id) 
					{
						if (empty($arOldPrices["CURRENCY_CONVERT"]))
						{
							$arOldPrices["CURRENCY_CONVERT"] = $arProductPrice['CURRENCY'];
						}
						else
						{
							$arProductPrice['CURRENCY_CONVERT'] = $arOldPrices["CURRENCY_CONVERT"];
							convert_price($arProductPrice);
						}
						convert_price($arOldPrices);

						if ($price_doscount && ((float)$price_doscount < (float)$arOldPrices['PRICE']))
						{
							$arOffers[$product_id]["OLD_PRICE"] = $arOldPrices['PRICE'];
						}
						elseif ($arProductPrice["PRICE"] && ((float)$arProductPrice["PRICE"] < (float)$arOldPrices['PRICE']))
						{
							$arOffers[$product_id]["OLD_PRICE"] = $arOldPrices['PRICE'];
						}
					}
				}
		}
	} elseif ($arParams["DISCOUNTS"] == "PRICE_ONLY")
	{
		function yenisite_yandex_GetOldPrice ($product_id, &$arOldPrices, &$arOffers,$arPruductPrices)
		{		
				foreach ($arPruductPrices as $arProductPrice) {
					if($arProductPrice['PRODUCT_ID'] == $product_id) 
					{
						if (empty($arOldPrices["CURRENCY_CONVERT"]))
						{
							$arOldPrices["CURRENCY_CONVERT"] = $arProductPrice['CURRENCY'];
						}
						else
						{
							$arProductPrice['CURRENCY_CONVERT'] = $arOldPrices["CURRENCY_CONVERT"];
							convert_price($arProductPrice);
						}
						convert_price ($arOldPrices);

						if ($arProductPrice["PRICE"] && ((float)$arProductPrice["PRICE"] < (float)$arOldPrices['PRICE']))
						{
							$arOffers[$product_id]["OLD_PRICE"] = $arOldPrices['PRICE'];
						}
					}
				}
		}
	} else {
		function yenisite_yandex_GetOldPrice ($product_id, &$arOldPrices, &$arOffers,$arPruductPrices)
		{
			$arPrice = CCatalogProduct::GetOptimalPrice($product_id, 1, $GLOBALS["USER"]->GetUserGroupArray(), "N", $arPrices, false, array());
			foreach ($arPruductPrices as &$arProductPrice)
			{	
				if($arProductPrice['PRODUCT_ID'] == $product_id) 
					{
						if (empty($arOldPrices["CURRENCY_CONVERT"]))
						{

							$arOldPrices["CURRENCY_CONVERT"] = $arProductPrice['CURRENCY'];
						}
						else
						{
							$arProductPrice['PRICE'] = $arPrice["DISCOUNT_PRICE"];
							$arProductPrice['CURRENCY_CONVERT'] = $arOldPrices["CURRENCY_CONVERT"];
							convert_price($arProductPrice);
						}
						convert_price($arOldPrices);

						if ($arProductPrice["PRICE"] && ($arProductPrice["PRICE"] < $arOldPrices['PRICE']))
						{
							$arOffers[$product_id]["OLD_PRICE"] = $arOldPrices['PRICE'];
						}
					}
			}
			CCatalogDiscount::ClearDiscountCache(array('PRODUCT'=>'Y'));
		}
	}
}

if ($arParams["DISCOUNTS"] == "PRICE_ONLY") {
	function yenisite_yandex_GetPrice ($product_id, &$arPrices, &$arOffers, $bConvert=false)
	{
		$arOffers[$product_id]["PRICE"] = 0;
		foreach ($arPrices as $arProductPrice)
		{
			if($arProductPrice['PRICE'] && ($arProductPrice['PRICE'] < $arOffers[$product_id]["PRICE"] || !$arOffers[$product_id]["PRICE"])) {
				$arOffers[$product_id]["PRICE"] = $arProductPrice['PRICE'];
				$arOffers[$product_id]["CURRENCY"] = $arProductPrice["CURRENCY"];
			}
		}
	}
}elseif ($arParams["DISCOUNTS"] == "DISCOUNT_CUSTOM") {
	$arUserGroups = $GLOBALS["USER"]->GetUserGroupArray();
	function yenisite_yandex_GetPrice ($product_id, &$arPrices, &$arOffers, $bConvert=false)
	{
		global $arUserGroups;
		$price = 0;
		$price_not_discount = 0;
		foreach ($arPrices as &$arProductPrice)
		{
			if($arProductPrice['PRICE'] && ($arProductPrice['PRICE'] < $price || !$price)) {
				$price = $arProductPrice['PRICE'];
				$arOffers[$product_id]["OLD_CURENCY"] = $arOffers[$product_id]["CURRENCY"];
				$arOffers[$product_id]["CURRENCY"] = $arProductPrice["CURRENCY"];
				$price_not_discount = $arProductPrice['PRICE'];
			}

			$arDiscounts = CCatalogDiscount::GetDiscountByProduct($product_id, $arUserGroups,  "N", $arProductPrice['CATALOG_GROUP_ID'], SITE_ID, array());
			foreach($arDiscounts as &$arDiscount)
			{
				switch ($arDiscount["VALUE_TYPE"]) {
					case 'P': $price_buf = $arProductPrice["PRICE"] - $arDiscount["VALUE"] * $arProductPrice["PRICE"] / 100;
						break;
					case 'F': $price_buf = $arProductPrice["PRICE"] - $arDiscount["VALUE"];
						break;
					default:  $price_buf = $arDiscount["VALUE"];
						break;
				}

				if($price_buf && ($price_buf < $price || !$price)) {
					$price = $price_buf;
					$arOffers[$product_id]["OLD_CURENCY"] = $arOffers[$product_id]["CURRENCY"];
					$arOffers[$product_id]["CURRENCY"] = $arProductPrice["CURRENCY"];
				}
			}
			$arDiscounts = null;
		}
		$arOffers[$product_id]["PRICE_NOT_DISCONT"] = $price_not_discount;
		$arOffers[$product_id]["PRICE"] = $price;
		CCatalogDiscount::ClearDiscountCache(array('PRODUCT'=>'Y'));
	}
}
else // if($arParams["DISCOUNTS"] == "DISCOUNT_API")
{
	global $baseCurrency;
	if ($bCurrency) {
		$baseCurrency = CCurrency::GetBaseCurrency();
	}

	function yenisite_yandex_GetPrice ($product_id, &$arPrices, &$arOffers, $bConvert=false)
	{
		global $bCurrency;
		global $baseCurrency;
		$price_not_discount = 0;
		$arPrice = CCatalogProduct::GetOptimalPrice($product_id, 1, $GLOBALS["USER"]->GetUserGroupArray(), "N", $arPrices, false, array());
		if (!$bConvert) {
			if ( $arPrice["PRICE"]["CURRENCY"] != $baseCurrency && $bCurrency) {
				$arPrice["DISCOUNT_PRICE"] = CCurrencyRates::ConvertCurrency($arPrice["DISCOUNT_PRICE"], $baseCurrency, $arPrice["PRICE"]["CURRENCY"] );
			}
			$arOffers[$product_id]["OLD_CURENCY"] = $arOffers[$product_id]["CURRENCY"];
			$arOffers[$product_id]["CURRENCY"] = $arPrice["PRICE"]["CURRENCY"];
		} else {
			$arOffers[$product_id]["OLD_CURENCY"] = $arOffers[$product_id]["CURRENCY"];
			$arOffers[$product_id]["CURRENCY"] = $baseCurrency;
		}
		foreach ($arPrices as &$arProductPrice)
		{	
			if($arProductPrice['PRICE'] && ($arProductPrice['PRICE'] < $price_not_discount || !$price_not_discount)) {
				$price_not_discount = $arProductPrice['PRICE'];
			}
		}

		$arOffers[$product_id]["PRICE_NOT_DISCONT"] = $price_not_discount;
		$arOffers[$product_id]["PRICE"] = $arPrice["DISCOUNT_PRICE"];
		CCatalogDiscount::ClearDiscountCache(array('PRODUCT'=>'Y'));
	}
}

/* Deprecated since 1.2.0 */

if ( !function_exists("yenisite_yandex_GetMinPrice") )
{
	function  yenisite_yandex_GetMinPrice ($product_id, $arPriceTypesID)
	{
		if (CModule::IncludeModule("catalog"))
		{
			$dbProductPrices = CPrice::GetList(array(), array("PRODUCT_ID" => $product_id, "CATALOG_GROUP_ID" => $arPriceTypesID)) ; // ->Fetch();
			$arPrices = array();
			while($arProductPrice = $dbProductPrices->Fetch())
			{
				$arPrices[] = $arProductPrice;
			}
			$arPrice = CCatalogProduct::GetOptimalPrice($product_id, 1, $GLOBALS["USER"]->GetUserGroupArray(), "N", $arPrices);
			return $arPrice['DISCOUNT_PRICE'];
		}
		return false;
	}
}

if ( !function_exists("yenisite_yandex_GetCurrencies") )
{
	function yenisite_yandex_GetCurrencies( $product_id, $arPriceTypesID )
	{
		if ( CModule::IncludeModule("catalog") ) 
		{
			$productPrice = CPrice::GetList (array(), array("PRODUCT_ID" => $product_id, "CATALOG_GROUP_ID" => $arPriceTypesID) )->Fetch();

			$currency = $productPrice["CURRENCY"];
		}
		return $currency;
	}
}

/* End of deprecated since 1.2.0 */


$bDesignMode = is_object($GLOBALS["USER"]) && $GLOBALS["USER"]->IsAdmin();


$APPLICATION->RestartBuffer();
$CHARSET = SITE_CHARSET;
if($arParams['FORCE_CHARSET']) {
	$CHARSET = $arParams['FORCE_CHARSET'];
}
//header("Content-Type: text/xml; charset=".$CHARSET);
//header("Pragma: no-cache");
//zimin


/*************************************************************************
			Work with cache
*************************************************************************/
$cache_id = serialize($arrFilter).serialize($arParams); //.$USER->GetGroups() ;
$cache_folder = '/ys-ym';

if ($arParams["CACHE_NON_MANAGED"] == 'Y') {
	$obCache = new CPHPCache;
	$bCache = $obCache->StartDataCache($arParams["CACHE_TIME"], $cache_id, $cache_folder);
} else {
	$bCache = $this->StartResultCache(false, $cache_id, $cache_folder);
}

if($bCache)
{
	$arResult["DATE"] = Date("Y-m-d H:i");
	$arResult["COMPANY"] = strip_tags(html_entity_decode($arParams["COMPANY"]));
    $arResult["SITE"] = $arParams["SITE"];
	$arResult["URL"]='http://'. htmlspecialcharsEx(COption::GetOptionString("main", "server_name", ""));

	// list of the element fields that will be used in selection
	$arSelect = array(
		"ID",
		"NAME",
		"IBLOCK_ID",
		"IBLOCK_SECTION_ID",
		"DATE_CREATE",
		"DATE_ACTIVE_FROM",
		"DATE_ACTIVE_TO",
		'TIMESTAMP_X',
		"DETAIL_PAGE_URL",
		"DETAIL_TEXT",

//		"DETAIL_TEXT_TYPE",
//		"DETAIL_PICTURE",
		'SORT',
		'HIT',
		'BESTSELLER',
		"PREVIEW_TEXT"
//		"PREVIEW_TEXT_TYPE",
//		"PREVIEW_PICTURE",
        //"PROPERTY_".$arParams["PRICE_CODE"],
		//"PROPERTY_*",
		//$arParams["PRICE_CODE"]
	);
	
	if ( !$bCatalog && !empty($arParams["PRICE_CODE"]) )
	{
		$arSelect[] = "PROPERTY_".$arParams["PRICE_CODE"];
	}

	if($arParams['MORE_PHOTO'])
	{
		$arSelect[] = "DETAIL_PICTURE" ;
		$arSelect[] = "PREVIEW_PICTURE" ;
	}		

	//  
	if(is_array($arProperty))
		$arSelect=array_merge($arProperty, $arSelect);
		
	$arFilter = array(
		"IBLOCK_LID" => SITE_ID,
                "IBLOCK_ID" => $arParams["IBLOCK_ID_IN"],
                "SECTION_ID" => $arParams["IBLOCK_SECTION"],
                "INCLUDE_SUBSECTIONS" => "Y",
		"IBLOCK_ACTIVE" => "Y",
		"ACTIVE_DATE" => "Y",
		"ACTIVE" => "Y",
		"CHECK_PERMISSIONS" => "Y",
		"SECTION_ACTIVE" => "Y", //New bitrix API can't fetch from IBLOCK root with this filter
		"SECTION_GLOBAL_ACTIVE" => "Y",
		//"!PROPERY_".$arParams["PRICE_CODE"] => false
	);

	if ($arParams['IBLOCK_AS_CATEGORY'] == 'Y') {
		unset($arFilter["SECTION_ACTIVE"]);
		unset($arFilter["SECTION_GLOBAL_ACTIVE"]);
	}

	if ( $arParams["DO_NOT_INCLUDE_SUBSECTIONS"] == "Y" )
		$arFilter["INCLUDE_SUBSECTIONS"] = "N";

	if ( (count($arParams["IBLOCK_SECTION"]) == 1 && $arParams["IBLOCK_SECTION"][0] == 0) ||
		!$arParams["IBLOCK_SECTION"] )
	{
		unset($arFilter["SECTION_ID"]);
	}

	$arSort = array(
		//$arParams["ELEMENT_SORT_FIELD"] => $arParams["ELEMENT_SORT_ORDER"],
		"ID" => "DESC",
	);


	$i=0;

	//EXECUTE

	if($arParams["IBLOCK_TYPE"]) {
		$rsIBlock = CIBlock::GetList(Array("sort" => "asc"), Array( "ID" => $arParams["IBLOCK_ID_IN"], "TYPE" => $arParams["IBLOCK_TYPE"], "ACTIVE"=>"Y"));
		$arFilter["IBLOCK_TYPE"] = $arParams["IBLOCK_TYPE"];
	}
	else {
		$rsIBlock = CIBlock::GetList(Array("sort" => "asc"), Array( "ID" => $arParams["IBLOCK_ID_IN"], "TYPE" => $arParams["IBLOCK_TYPE_LIST"], "ACTIVE"=>"Y"));
		$arFilter["IBLOCK_TYPE"] = $arParams["IBLOCK_TYPE_LIST"];
	}

	$arSKUiblockID = array();

	while($res = $rsIBlock->GetNext()) {
		if ($arParams['IBLOCK_AS_CATEGORY'] == 'Y') {
			$arResult["CATEGORIES"][$res["ID"]] = Array("ID" => $res["ID"], "NAME" => yandex_text2xml($res["NAME"], true));
		}
		
		if($bCatalog)
		{
			$rsSKU = CCatalog::GetList( array(), array("PRODUCT_IBLOCK_ID" => $res["ID"]),false, false, array("IBLOCK_ID") );
			if ($arSKUiBlock = $rsSKU->Fetch()) {
				$arSKUiblockID[$res["ID"]] = $arSKUiBlock["IBLOCK_ID"];
			}
			unset($rsSKU);
		}
	}
	unset($rsIBlock);

//fetch sections into categories list
	if((count($arParams["IBLOCK_SECTION"]) == 1 && $arParams["IBLOCK_SECTION"][0] == 0))
	{
		$filter = Array("IBLOCK_TYPE" => $arFilter["IBLOCK_TYPE"], "IBLOCK_ID"=>$arParams["IBLOCK_ID_IN"], "ACTIVE"=>"Y", "IBLOCK_ACTIVE"=>"Y", "GLOBAL_ACTIVE"=>"Y");
		$bSections = false;
	}
	else{
		$filter = Array("IBLOCK_TYPE" => $arFilter["IBLOCK_TYPE"], "IBLOCK_ID"=>$arParams["IBLOCK_ID_IN"], "ID" => $arParams["IBLOCK_SECTION"],  "ACTIVE"=>"Y", "IBLOCK_ACTIVE"=>"Y", "GLOBAL_ACTIVE"=>"Y");
		$bSections = true;
	}
/*
	if((count($arParams["IBLOCK_ID_IN"]) == 1 && $arParams["IBLOCK_ID_IN"][0] == 0) || !$arParams["IBLOCK_ID_IN"]){
		unset($filter["IBLOCK_ID"]);
	}
*/
	if ($arParams['IBLOCK_AS_CATEGORY'] == 'Y') {
		unset($filter['ACTIVE']);
		unset($filter['GLOBAL_ACTIVE']);
	}
	
	$db_acc = CIBlockSection::GetList(array("left_margin"=>"asc"), $filter, false, array("ID", "NAME", "IBLOCK_ID", "IBLOCK_SECTION_ID", 'SORT', "LEFT_MARGIN", "RIGHT_MARGIN", "DEPTH_LEVEL"));

	unset($filter["ID"]);
	unset($filter["IBLOCK_ID"]);

	while($arAcc = $db_acc->Fetch())
	{
		$id = $arAcc["IBLOCK_ID"].$arAcc["ID"];
		if (array_key_exists($id, $arResult["CATEGORIES"])) continue;

		$arResult["CATEGORIES"][$id] = Array(
			"ID" => $id,
			"NAME" => yandex_text2xml($arAcc["NAME"], true),
			"PARENT" => ($arParams['IBLOCK_AS_CATEGORY'] == 'Y') ? $arAcc["IBLOCK_ID"] : NULL,
			'sort' => $arAcc["SORT"]
			);

		if ($arParams["DO_NOT_INCLUDE_SUBSECTIONS"] != "Y" && $bSections) {
			$subFilter = array(
				'IBLOCK_ID' => $arAcc['IBLOCK_ID'],
				'>LEFT_MARGIN' => $arAcc['LEFT_MARGIN'],
				'<RIGHT_MARGIN' => $arAcc['RIGHT_MARGIN'],
				'>DEPTH_LEVEL' => $arAcc['DEPTH_LEVEL']
			);

			$db_sub = CIBlockSection::GetList(array("left_margin"=>"asc"), array_merge($filter, $subFilter), false, array("ID", "NAME", "IBLOCK_ID", "IBLOCK_SECTION_ID"));

			while($arAcc2 = $db_sub->Fetch())
			{
				$id2 = $arAcc2["IBLOCK_ID"].$arAcc2["ID"];
				$arResult["CATEGORIES"][$id2] = Array(
					"ID" => $id2,
					"NAME" => yandex_text2xml($arAcc2["NAME"], true),
					"PARENT" => ($arParams['IBLOCK_AS_CATEGORY'] == 'Y') ? $arAcc2["IBLOCK_ID"] : NULL
					);
				if (IntVal($arAcc2["IBLOCK_SECTION_ID"]) < 1) continue;

				$key2 = $arAcc2["IBLOCK_ID"] . $arAcc2["IBLOCK_SECTION_ID"];
				if (!array_key_exists($key2, $arResult["CATEGORIES"])) continue;

				$arResult["CATEGORIES"][$id2]["PARENT"] = $key2;
			}
			unset($db_sub);
		}
		if (IntVal($arAcc["IBLOCK_SECTION_ID"]) < 1) continue;

		$key = $arAcc["IBLOCK_ID"] . $arAcc["IBLOCK_SECTION_ID"];
		if (!array_key_exists($key, $arResult["CATEGORIES"])) continue;

		$arResult["CATEGORIES"][$id]["PARENT"] = $key;
	}
	unset($arAcc);
	unset($db_acc);

//fetch elements
	$rsElements = CIBlockElement::Getlist($arSort, array_merge($arrFilter, $arFilter), false, false, $arSelect);

	while($arOffer = $rsElements->GetNext())
	{	
		$arOfferID[] = $arOffer["ID"];
		$arOffer["SKU"] = array();

		//$arOffer['зхй'] = 'ss';

		$arOffers[$arOffer["ID"]] = $arOffer;
	}
	unset($rsElements);

//work with module 'catalog'

	if ($bCatalog && $arParams['PRICE_FROM_IBLOCK'] != 'Y') {
		if (empty($arSKUiblockID)) {
			$arAllID = $arOfferID; //ID of SKU and offers without any SKU
		} else {
			//fetch SKU
			$arOfferInOb = CIBlockElement::GetList(array($arParams['SKU_PROPERTY'] => 'DESC'),
				array("IBLOCK_ID" => $arSKUiblockID, $arParams['SKU_PROPERTY'] => $arOfferID, 'ACTIVE' => 'Y'), false, false, $arSelect);

			$arAllID = array(); //ID of SKU and offers without any SKU
			$productKey = $arParams['SKU_PROPERTY'] . '_VALUE';

			while($arOfferIn = $arOfferInOb->GetNext())
			{
				$arAllID[] = $arOfferIn["ID"];
				$productID = $arOfferIn[$productKey];
				$arOffers[$productID]["SKU"][] = $arOfferIn["ID"];
				$arOffers[$arOfferIn["ID"]] = $arOfferIn;
			}
			unset($arOfferInOb);

			foreach ($arOfferID as $offerID) {
				if (empty($arOffers[$offerID]["SKU"])) $arAllID[] = $offerID;
			}
		}

		//process catalog products
		$arProductSelect = array(
			"ID",
			"QUANTITY",
			"QUANTITY_TRACE"
		);

		$dbProducts = CCatalogProduct::GetList(array("ID" => "SORT"), array("@ID" => $arAllID), false, false, $arProductSelect);

		while ($tr = $dbProducts->Fetch()) {
			$arOffers[$tr["ID"]]["AVAIBLE"] = "true";
			$arOffers[$tr["ID"]]["QUANTITY"] = $tr["QUANTITY"];

			if( $tr["QUANTITY_TRACE"] == "N" ) continue;
			if( $tr["QUANTITY"] > 0 ) continue;

			$arOffers[$tr["ID"]]["AVAIBLE"] = "false";
		}
		unset($tr);

		//fetch old_price types 
		if ($arParams["OLD_PRICE_LIST"] == "TYPE_PRICE")
		{
			$dbOldPriceTypes = CCatalogGroup::GetList( array("SORT" => "ASC"), array("NAME" => $arParams["OLD_PRICE_CODE"]) );

			while ($arOldPriceType = $dbOldPriceTypes -> Fetch()) 
			{
				$arOldPriceTypesID[] = $arOldPriceType['ID'];
			}
			unset($dbOldPriceTypes);

			$dbAlldPriceTypes = CCatalogGroup::GetList( array("SORT" => "ASC"), array("NAME" => $arParams["PRICE_CODE"], "CAN_BUY" => "Y") );

			while ($arAllPriceType = $dbAlldPriceTypes -> Fetch()) 
			{
				$arAllPriceTypesID[] = $arAllPriceType['ID'];
			}
			unset($dbAlldPriceTypes);

		//fetch and process product old_prices

			$arPriceSelect = array('PRODUCT_ID', 'PRICE', 'CURRENCY', 'CATALOG_GROUP_ID');
			$dbOldProductPrices = CPrice::GetList(array("PRODUCT_ID" => "DESC"), array("@PRODUCT_ID" => $arAllID, "@CATALOG_GROUP_ID" => $arOldPriceTypesID), false, false, $arPriceSelect);
			$dbProdcutPrices = CPrice::GetList(array("PRODUCT_ID" => "DESC"), array("@PRODUCT_ID" => $arAllID, "@CATALOG_GROUP_ID" => $arAllPriceTypesID), false, false, $arPriceSelect);

			$arAllPricesHolder = array();
			while ($tmpPrice = $dbProdcutPrices->GetNext())
            {
                $arAllPricesHolder[]  = $tmpPrice;
				unset($tmpPrice);
            }
            while ($tmpPrice = $dbOldProductPrices->GetNext())
            {
                $arOldPricesHolder[]  = $tmpPrice;
				unset($tmpPrice);
            }
            foreach ($arOldPricesHolder as &$arOldPrice) {
            	if ($arParams['CURRENCIES_CONVERT'] != 'NOT_CONVERT')
				{
					$arOldPrice["CURRENCY_CONVERT"] = $arParams['CURRENCIES_CONVERT'] ;
				}
            	yenisite_yandex_GetOldPrice($arOldPrice["PRODUCT_ID"], $arOldPrice, $arOffers,$arAllPricesHolder);
            }
			unset($arAllPricesHolder);

		} 
		
		//fetch price types
		$dbPriceTypes = CCatalogGroup::GetList( array("SORT" => "ASC"), array("NAME" => $arParams["PRICE_CODE"], "CAN_BUY" => "Y") );

		while($arPriceType = $dbPriceTypes -> Fetch()) {
			$arPriceTypesID[] = $arPriceType['ID'];
		}
		unset($dbPriceTypes);

		//fetch and process product prices
		$arPriceSelect = array('PRODUCT_ID', 'PRICE', 'CURRENCY', 'CATALOG_GROUP_ID');
		$dbProductPrices = CPrice::GetList(array("PRODUCT_ID" => "DESC"), array("@PRODUCT_ID" => $arAllID, "@CATALOG_GROUP_ID" => $arPriceTypesID), false, false, $arPriceSelect);

		$bConvert = ($arParams['CURRENCIES_CONVERT'] != 'NOT_CONVERT');
		$arPrices = array();
		if (count($arPriceTypesID) > 1)
		{
			$arProductPrice = $dbProductPrices->GetNext();
			$product_id = $arProductPrice["PRODUCT_ID"];
			$arPrices[] = $arProductPrice;
			while ($arProductPrice = $dbProductPrices->GetNext())
			{
				if ($arProductPrice["PRODUCT_ID"] != $product_id) {
					yenisite_yandex_GetPrice($product_id, $arPrices, $arOffers, $bConvert);

					$product_id = $arProductPrice["PRODUCT_ID"];
					$arPrices = array();
				}
				$arPrices[] = $arProductPrice;
			}
			yenisite_yandex_GetPrice($product_id, $arPrices, $arOffers, $bConvert);
		}
		else if ($arParams["DISCOUNTS"] == 'PRICE_ONLY')
		{
			while ($arPrice = $dbProductPrices->GetNext())
			{
				$arOffers[$arPrice["PRODUCT_ID"]]["PRICE"] = $arPrice["PRICE"];
				$arOffers[$arPrice["PRODUCT_ID"]]["CURRENCY"] = $arPrice["CURRENCY"];
			}
		}
		else
		{
            $arAllPricesHolder = array();
            while ($tmpPrice = $dbProductPrices->GetNext())
            {
                $arPrices[0]["PRODUCT_ID"]=$tmpPrice["PRODUCT_ID"];
                $arPrices[0]["PRICE"]=$tmpPrice["PRICE"];
                $arPrices[0]["CURRENCY"]=$tmpPrice["CURRENCY"];
				$arPrices[0]["CATALOG_GROUP_ID"]=$tmpPrice["CATALOG_GROUP_ID"];
                $arAllPricesHolder[] = $arPrices;
				unset($tmpPrice);
            }
			unset($arPrices);
			
            $arr_length = count($arAllPricesHolder);
            for ($i=0;$i<$arr_length;$i++) {
                yenisite_yandex_GetPrice($arAllPricesHolder[$i][0]["PRODUCT_ID"], $arAllPricesHolder[$i], $arOffers, $bConvert);
            }
			unset($arAllPricesHolder);
		}
		unset($dbProductPrices);

		CCatalogDiscount::ClearDiscountCache(array('SECTIONS' => 'Y', 'SECTION_CHAINS' => 'Y'));

		//Format price decimal part for currencies
		if ($bCurrency) {
			$arCurrencies = array();
			$obCurrencies = CCurrency::GetList($by = 'sort', $order = 'asc');
			while ($arCurrency = $obCurrencies->Fetch()) {
				$arCurrency['DECIMALS'] = intval($arCurrency['DECIMALS']);
				if ($arCurrency['DECIMALS'] < 0) $arCurrency['DECIMALS'] = 0;
				$arCurrencies[$arCurrency['CURRENCY']] = $arCurrency;
			}
		}
	}

	$arResult['OFFER'] = array();
	$arResult['CURRENCIES'] = array();

	if ($arParams["OLD_PRICE_LIST"] == "FROM_DISCOUNT") {
		$dbPriceTypes = CCatalogGroup::GetList( array("SORT" => "ASC"), array("NAME" => $arParams["PRICE_CODE"], "CAN_BUY" => "Y") );
		while ($arPriceType = $dbPriceTypes -> Fetch()) 
		{
			$arPriceType["CAN_VIEW"] = true;
			$arPriceTypes[] = $arPriceType;
		}
		unset($arPriceType);
	}


//zimin

$dbProductPrices = CPrice::GetList( 
	array("PRODUCT_ID" => "DESC"), 
	array(
		"@PRODUCT_ID" => $arAllID, 
		"@CATALOG_GROUP_ID" => array( 6,7,8,9 )
	)
);

while( $arProductPrice = $dbProductPrices->GetNext() ) 
{
	$product_id = $arProductPrice["PRODUCT_ID"];

	$arOffers[$product_id][ 'price' . $arProductPrice['CATALOG_GROUP_ID'] ] .= round($arProductPrice['PRICE'], 0 );

	$arOffers[$product_id][ 'price' . $arProductPrice['CATALOG_GROUP_ID'] . 'currency'] .= $arProductPrice['CURRENCY'];	
}

/*
//$dbProductPrices = CPrice::GetList( array("PRODUCT_ID" => "DESC"), array("@PRODUCT_ID" => $arAllID, "@CATALOG_GROUP_ID" => $arPriceTypesID), false, false, $arPriceSelect);

$dbProductPrices = CPrice::GetList( array("PRODUCT_ID" => "DESC"), array("@PRODUCT_ID" => $arAllID, "@CATALOG_GROUP_ID" => array( 7,8,9 ) ), false, false, $arPriceSelect);

while ($arProductPrice = $dbProductPrices->GetNext()) 
{
	$product_id = $arProductPrice["PRODUCT_ID"];

	foreach ($arPriceTypes as $priceType) 
	{

		if (!empty($arOffer["PRICE_NOT_DISCONT"]))
		{
			

			$arOffers[$product_id]['CATALOG_PRICE_'.$priceType["ID"]] = $arOffers[$product_id]["PRICE_NOT_DISCONT"];
		} else {

			//$arOffers[$product_id]['bla'.$priceType["ID"]] = $arProductPrice;
			$arOffers[$product_id]['CATALOG_PRICE_'.$priceType["ID"]] = $arOffers[$product_id]["PRICE"];
		}

			$arOffers[$product_id]['CATALOG_CURRENCY_'.$priceType["ID"]] = $arOffers[$product_id]["CURRENCY"];
			unset($priceType);
	}
}
*/

/*
	//include PARAMETRS [CATALOG_PRICE_ID, CATALOG_CURRENCY_ID] for oldprices from discounts adn Create Old Price for not SKU
	//============================================================================================================================
	if ($arParams["OLD_PRICE_LIST"] == "FROM_DISCOUNT")
	 {
		$dbProductPrices = CPrice::GetList(array("PRODUCT_ID" => "DESC"), array("@PRODUCT_ID" => $arAllID, "@CATALOG_GROUP_ID" => $arPriceTypesID), false, false, $arPriceSelect);
		while ($arProductPrice = $dbProductPrices->GetNext()) 
		{
			$product_id = $arProductPrice["PRODUCT_ID"];

			foreach ($arPriceTypes as $priceType) {
				if (!empty($arOffer["PRICE_NOT_DISCONT"]))
				{
					$arOffers[$product_id]['CATALOG_PRICE_'.$priceType["ID"]] = $arOffers[$product_id]["PRICE_NOT_DISCONT"];
				} else {
					$arOffers[$product_id]['CATALOG_PRICE_'.$priceType["ID"]] = $arOffers[$product_id]["PRICE"];
				}
					$arOffers[$product_id]['CATALOG_CURRENCY_'.$priceType["ID"]] = $arOffers[$product_id]["CURRENCY"];
					unset($priceType);
			}

			$rsItemPrices = CIBlockPriceTools::GetItemPrices(false,$arPriceTypes,$arOffers[$product_id],false,array(),0,SITE_ID);
			$arValue = reset($rsItemPrices);

			if ((float)$arValue["VALUE"] >= (float)$arValue["DISCOUNT_VALUE"] && (float)$arOffers[$product_id]["PRICE"] < (float)$arValue["VALUE"])
			{
				$arOffers[$product_id]['OLD_PRICE'] = $arValue["VALUE"];	
			}
			elseif ((float)$arOffers[$product_id]["PRICE"] < (float)$arOffers[$product_id]["PRICE_NOT_DISCONT"])
			{
				$arOffers[$product_id]['OLD_PRICE'] =  $arOffers[$product_id]["PRICE_NOT_DISCONT"];
			} 

			if ($arParams['CURRENCIES_CONVERT'] != 'NOT_CONVERT')
			{
				$arOffers[$product_id]["OLD_PRICE"] = CCurrencyRates::ConvertCurrency( $arOffers[$product_id]["OLD_PRICE"], $arOffers[$product_id]["CURRENCY"], $arParams["CURRENCIES_CONVERT"] );
				$arOffers[$product_id]["OLD_PRICE"] = round( $arOffers[$product_id]["OLD_PRICE"], $arCurrencies[$arParams["CURRENCIES_CONVERT"]]['DECIMALS'] );
			}
		}	
	}

	if ($arParams["OLD_PRICE_LIST"] == "PROP_PRICE")
	{
		$dbProductPrices = CPrice::GetList(array("PRODUCT_ID" => "DESC"), array("@PRODUCT_ID" => $arAllID, "@CATALOG_GROUP_ID" => $arPriceTypesID), false, false, $arPriceSelect);
		while ($arProductPrice = $dbProductPrices->GetNext()) 
		{
			$product_id = $arProductPrice["PRODUCT_ID"];

			$arOldPrice = array();
			if (!empty($arParams['OLD_PRICE_CODE']))
			{
				$rs = CIBlockElement::GetProperty($arOffers[$product_id]["IBLOCK_ID"], $arOffers[$product_id]["ID"], array("sort" => "asc"), Array("CODE" => $arParams['OLD_PRICE_CODE']) );
				$arProps = $rs->Fetch();
				$arOldPrice = $arProps["VALUE_ENUM"] ? $arProps["VALUE_ENUM"] : $arProps["VALUE"];
				if ((float)$arOffers[$product_id]['PRICE'] < (float)$arOldPrice){
					$arOffers[$product_id]['OLD_PRICE'] = $arOldPrice;
				}
				unset($arProps, $rs);
			}
		}
	}
*/

//*************************************************************
//******************** OFFER ITERATION ************************
//*************************************************************

	foreach ($arOfferID as &$offerID)
	{
		$arOffer = & $arOffers[$offerID];

		if( $bCatalog && empty($arOffer["SKU"]) && $arParams['PRICE_FROM_IBLOCK'] != 'Y' ) {
			if( floatval($arOffer["PRICE"]) <= 0 && $arParams['PRICE_REQUIRED'] != 'N')
				continue;
			if( $arParams["IBLOCK_ORDER"] != "Y" && $arOffer["AVAIBLE"] == "false" )
				continue;
		}

		if ( $arParams["CURRENCIES_CONVERT"] != "NOT_CONVERT" && $arOffer["OLD_CURENCY"] != $arParams["CURRENCIES_CONVERT"] 
			&& $arParams["OLD_PRICE_LIST"] == "FROM_DISCOUNT")
		{
			$arOffer["PRICE"] = CCurrencyRates::ConvertCurrency( $arOffer["PRICE"], $arOffer["CURRENCY"], $arParams["CURRENCIES_CONVERT"] );
			$arOffer["CURRENCY"] = $arParams["CURRENCIES_CONVERT"];
		}
		$arOffer["PRICE"] = round( $arOffer["PRICE"], $arCurrencies[$arOffer["CURRENCY"]]['DECIMALS'] );

		//setting offer pictures
		if( $arOffer["DETAIL_PICTURE"] )
		{
			$db_file = CFile::GetByID($arOffer["DETAIL_PICTURE"]);
			if ($ar_file = $db_file->Fetch())
				$arOffer["PICTURE"] = $ar_file["~src"] ? $ar_file["~src"] : "http://".$_SERVER["SERVER_NAME"]."/".( COption::GetOptionString("main", "upload_dir", "upload"))."/".$ar_file["SUBDIR"]."/".implode("/", array_map("rawurlencode", explode("/", $ar_file["FILE_NAME"])) );
			unset($ar_file);
			unset($db_file);
		}


		if( $arOffer["PREVIEW_PICTURE"] && !$arOffer["PICTURE"] )
		{
			$db_file = CFile::GetByID($arOffer["PREVIEW_PICTURE"]);
			if ($ar_file = $db_file->Fetch())
				$arOffer["PICTURE"] = $ar_file["~src"] ? $ar_file["~src"] : "http://".$_SERVER["SERVER_NAME"]."/".(COption::GetOptionString("main", "upload_dir", "upload"))."/".$ar_file["SUBDIR"]."/".implode("/", array_map("rawurlencode", explode("/", $ar_file["FILE_NAME"])));
			unset($ar_file);
			unset($db_file);
		}

		//zimin
		//only this works for pictures
		if( isset( $arParams["MORE_PHOTO"] ) && $arParams["MORE_PHOTO"] != "YS_EMPTY" )
		{
			$ph = CIBlockElement::GetProperty( $arOffer["IBLOCK_ID"], $arOffer["ID"], array("value_id" => "asc"), Array("CODE" => $arParams["MORE_PHOTO"]) );
			$arOffer["MORE_PHOTO"] = array();

			//zimin
			while( ($ob = $ph->GetNext()) && count($arOffer["MORE_PHOTO"]) < 10 )
			{
				$arOffer["MORE_PHOTO"][] = $ob["VALUE"];

			/*	$arFile = CFile::GetFileArray( $ob["VALUE"] );
				if ( !empty( $arFile ) )
				{
					if ( strpos( $arFile["SRC"], "http" ) === false )
					{
						$pic = "http://".$_SERVER["SERVER_NAME"].implode( "/", array_map( "rawurlencode", explode( "/", $arFile["SRC"] ) ) );
					}	
					else
					{
						$ar = explode( "http://", $arFile["SRC"] );
						$pic = "http://".implode( "/", array_map( "rawurlencode", explode( "/", $ar[1] ) ) );	
					}
					$arOffer["MORE_PHOTO"][] = $arFile;
				} */
				unset($ob);
			}
		/*	while( ($ob = $ph->GetNext()) && count($arOffer["MORE_PHOTO"]) < 10 )
			{
				$arFile = CFile::GetFileArray( $ob["VALUE"] );
				if ( !empty( $arFile ) )
				{
					if ( strpos( $arFile["SRC"], "http" ) === false )
					{
						$pic = "http://".$_SERVER["SERVER_NAME"].implode( "/", array_map( "rawurlencode", explode( "/", $arFile["SRC"] ) ) );
					}	
					else
					{
						$ar = explode( "http://", $arFile["SRC"] );
						$pic = "http://".implode( "/", array_map( "rawurlencode", explode( "/", $ar[1] ) ) );	
					}
					$arOffer["MORE_PHOTO"][] = $pic;
				}
				unset($ob);
			} */
			unset($ph);

			if (!$arOffer["PICTURE"] && is_array($arOffer["MORE_PHOTO"]))
				$arOffer['PICTURE'] = array_shift($arOffer["MORE_PHOTO"]);
			$arOffer["MORE_PHOTO"] = array_slice($arOffer["MORE_PHOTO"], 0, 9);
		}

		//offer URL
			$arOffer["URL"] = "http://".$_SERVER["SERVER_NAME"]. $arOffer["DETAIL_PAGE_URL"];

		//setting offer description
		if ($arOffer["PREVIEW_TEXT"])
		{
			$arOffer["PREVIEW_TEXT"]=yandex_text2xml(($arOffer["PREVIEW_TEXT_TYPE"]=="html"?preg_replace_callback("'&[^;]*;'", "yandex_replace_special", strip_tags($arOffer["~PREVIEW_TEXT"])) : $arOffer["~PREVIEW_TEXT"]), true);
		}

		if ($arOffer["DETAIL_TEXT"])
		{
			$arOffer["DETAIL_TEXT"]=yandex_text2xml(($arOffer["DETAIL_TEXT_TYPE"]=="html"?preg_replace_callback("'&[^;]*;'", "yandex_replace_special", strip_tags($arOffer["~DETAIL_TEXT"])) : $arOffer["~DETAIL_TEXT"]), true);
		}

		$arOffer["DESCRIPTION"] = $arOffer["PREVIEW_TEXT"] ? $arOffer["PREVIEW_TEXT"] : $arOffer["DETAIL_TEXT"];

		if ($arParams["DETAIL_TEXT_PRIORITET"] == "Y")
		{
			$arOffer["DESCRIPTION"] = $arOffer["DETAIL_TEXT"] ? $arOffer["DETAIL_TEXT"] : $arOffer["PREVIEW_TEXT"];
		}
			
			$arOffer["CATEGORY"] = $arOffer["IBLOCK_ID"] . $arOffer["IBLOCK_SECTION_ID"];

		if (!array_key_exists($arOffer["CATEGORY"], $arResult["CATEGORIES"]) && $arOffer["IBLOCK_SECTION_ID"])
		{
			$arGr = CIBlockElement::GetElementGroups($arOffer["ID"]);
			while ($ar_group = $arGr->Fetch()) {
				if (!array_key_exists($arOffer["IBLOCK_ID"].$ar_group["ID"], $arResult["CATEGORIES"])) continue;
				$arOffer["CATEGORY"] = $arOffer["IBLOCK_ID"] . $ar_group["ID"];
				break;
			}
		}
		if ($arParams['SECTION_AS_VENDOR'] == 'Y')
		{
			if (!empty($arOffer['IBLOCK_SECTION_ID']))
			{
				$arOffer["DEVELOPER"] = $arResult["CATEGORIES"][ $arOffer["IBLOCK_ID"].$arOffer['IBLOCK_SECTION_ID'] ]["NAME"];
			}
		}

		if ($arParams["MARKET_CATEGORY_CHECK"] == "Y")
		{
			if (!empty($arParams['MARKET_CATEGORY_PROP']))
			{
				$arProps = CIBlockElement::GetProperty( $arOffer["IBLOCK_ID"], $arOffer["ID"], array("sort" => "asc"), Array("CODE" => $arParams["MARKET_CATEGORY_PROP"]) )->Fetch();
		
				$arOffer["MARKET_CATEGORY"] = $arProps["VALUE_ENUM"] ? $arProps["VALUE_ENUM"] : $arProps["VALUE"];
				unset($arProps);
			}
			
			if (!$arOffer["MARKET_CATEGORY"])
			{
				$arGr = CIBlockElement::GetElementGroups($arOffer["ID"]);
				$ar_group = $arGr->Fetch();
				$groupid = $ar_group["ID"];
					
				$res = CIBlockSection::GetNavChain( false, $groupid );
				while ( $el = $res->GetNext() )
				{
					$arOffer["MARKET_CATEGORY"] .= $el['NAME'];
					$arOffer["MARKET_CATEGORY"] .= "/";
				}
				unset($res);
				unset($arGr);
				unset($ar_group);
				if ($arParams["IBLOCK_AS_CATEGORY"] == 'Y') {
					$arOffer["MARKET_CATEGORY"] = $arResult["CATEGORIES"][$arOffer["IBLOCK_ID"]]["NAME"]
					                            . '/'
												. $arOffer["MARKET_CATEGORY"];
				}
				$arOffer["MARKET_CATEGORY"] = substr($arOffer["MARKET_CATEGORY"], 0, -1);
			}
		}
		


		//setting offer name
		if (!empty($arParams['NAME_PROP']))
		{
			$arProps = CIBlockElement::GetProperty( $arOffer["IBLOCK_ID"], $arOffer["ID"], array("sort" => "asc"), Array("CODE" => $arParams['NAME_PROP']) )->Fetch();
			$arOffer["MODEL"] = $arProps["VALUE_ENUM"] ? $arProps["VALUE_ENUM"] : $arProps["VALUE"];
			unset($arProps);
		}

		if (empty($arOffer["MODEL"]))
		{
			$arOffer["MODEL"] = $arOffer["~NAME"];
		}
		
		//setting offer salse_notes
		$arSalse_notes = array();

		if ($arParams['SELF_SALES_NOTES'] == 'N')
		{	
			if (!empty($arParams['SALES_NOTES_NAMES']))
			{
				$rs = CIBlockElement::GetProperty( $arOffer["IBLOCK_ID"], $arOffer["ID"], array("sort" => "asc"), Array("CODE" => $arParams['SALES_NOTES_NAMES']) );
				$arProps = $rs->Fetch();
				$arSalse_notes = $arProps["VALUE_ENUM"] ? $arProps["VALUE_ENUM"] : $arProps["VALUE"];
				unset($arProps, $rs);
			}
		} else {
			if (!empty($arParams['SELF_SALES_NOTES_INPUT']))
			{
				$arSalse_notes = $arParams['SELF_SALES_NOTES_INPUT'];
			}
		};

		//work with offer SKU
		$flag = 0;
		foreach ($arOffer["SKU"] as &$arOfferInID)
		{
			$arOfferIn = & $arOffers[$arOfferInID];
			$flag = 1;

			//check available status
			if( $arParams["IBLOCK_ORDER"] != "Y" && $arOfferIn["AVAIBLE"] == "false" )
				continue;

			if ( floatval($arOfferIn["PRICE"]) <= 0 ) {
				if (floatval($arOffer['PRICE']) <= 0)
					continue;
				$arOfferIn['PRICE'] = $arOffer['PRICE'];
			}

			//setting offer salse_notes for offerIn
			if ($arParams['SELF_SALES_NOTES'] == 'N')
			{	
				if (!empty($arParams['SALES_NOTES_NAMES']))
				{
					$rs = CIBlockElement::GetProperty( $arOfferIn["IBLOCK_ID"], $arOfferIn["ID"], array("sort" => "asc"), Array("CODE" => $arParams['SALES_NOTES_NAMES']) );
					$arProps = $rs->Fetch();
					$arSalse_notes = $arProps["VALUE_ENUM"] ? $arProps["VALUE_ENUM"] : $arProps["VALUE"];
					unset($arProps, $rs);

					if (empty($arSalse_notes))
					{
						$rs = CIBlockElement::GetProperty( $arOffer["IBLOCK_ID"], $arOffer["ID"], array("sort" => "asc"), Array("CODE" => $arParams['SALES_NOTES_NAMES']) );
						$arProps = $rs->Fetch();
						$arSalse_notes = $arProps["VALUE_ENUM"] ? $arProps["VALUE_ENUM"] : $arProps["VALUE"];
						unset($arProps, $rs);
					}
				}
			} else {
				if (!empty($arParams['SELF_SALES_NOTES_INPUT']))
				{
					$arSalse_notes = $arParams['SELF_SALES_NOTES_INPUT'];
				}
			};

			
			//setting offer old_price

			if ($arParams["OLD_PRICE_LIST"] == "PROP_PRICE")
			{
				$arOldPrice = array();
				if (!empty($arParams['OLD_PRICE_CODE']))
				{
					$rs = CIBlockElement::GetProperty($arOfferIn["IBLOCK_ID"], $arOfferIn["ID"], array("sort" => "asc"), Array("CODE" => $arParams['OLD_PRICE_CODE']) );
					$arProps = $rs->Fetch();
					$arOldPrice = $arProps["VALUE_ENUM"] ? $arProps["VALUE_ENUM"] : $arProps["VALUE"];
					if (!empty($arOldPrice) && (float)$arOfferIn['PRICE'] < (float)$arOldPrice)
					{
						$arOfferIn['OLD_PRICE'] = $arOldPrice;
					}
					else
					{
						$rs = CIBlockElement::GetProperty($arOffer["IBLOCK_ID"], $arOffer["ID"], array("sort" => "asc"), Array("CODE" => $arParams['OLD_PRICE_CODE']) );
						$arProps = $rs->Fetch();
						$arOldPrice = $arProps["VALUE_ENUM"] ? $arProps["VALUE_ENUM"] : $arProps["VALUE"];

						if (!empty($arOldPrice) && (float)$arOfferIn['PRICE'] < (float)$arOldPrice)
						{
							$arOfferIn['OLD_PRICE'] = $arOldPrice;
						}
					}
					unset($arProps, $rs);
				}
			}		

			if ( $arParams["CURRENCIES_CONVERT"] != "NOT_CONVERT" && $arOfferIn["CURRENCY"] != $arParams["CURRENCIES_CONVERT"] )
			{
				$arOfferIn["PRICE"] = CCurrencyRates::ConvertCurrency( $arOfferIn["PRICE"], $arOfferIn["CURRENCY"], $arParams["CURRENCIES_CONVERT"] );
				if ($arParams["OLD_PRICE_LIST"] != "TYPE_PRICE")
				{
					$arOfferIn["OLD_PRICE"] = CCurrencyRates::ConvertCurrency( $arOfferIn["OLD_PRICE"], $arOfferIn["CURRENCY"], $arParams["CURRENCIES_CONVERT"] );
					$arOfferIn["OLD_CURENCY"] = $arOfferIn["CURRENCY"];
					$arOfferIn["OLD_PRICE"] = round( $arOfferIn["OLD_PRICE"], $arCurrencies[$arOfferIn["CURRENCY"]]['DECIMALS'] );
				}
				$arOfferIn["CURRENCY"] = $arParams["CURRENCIES_CONVERT"];
			}
			$arOfferIn["PRICE"] = round( $arOfferIn["PRICE"], $arCurrencies[$arOfferIn["CURRENCY"]]['DECIMALS'] );
			

			
			if ( !in_array($arOfferIn["CURRENCY"], $arResult["CURRENCIES"]) )
			$arResult["CURRENCIES"][] = $arOfferIn["CURRENCY"];
			
			$arOfferIn["CATEGORY"] = $arOffer["CATEGORY"];
			
			$tmpName = $arOffer["MODEL"];

			switch($arParams["SKU_NAME"])
			{
				case "PRODUCT_NAME":
					$arOfferIn["MODEL"] = yandex_text2xml($tmpName, true);
				break;

				case "SKU_NAME":
					$arOfferIn["MODEL"] = yandex_text2xml(empty($arOfferIn["~NAME"]) ? $tmpName : $arOfferIn["~NAME"], true);
				break;

				default:
					if (!empty($arOfferIn["~NAME"])) $tmpName .= " / " . $arOfferIn["~NAME"];
					$arOfferIn["MODEL"] = yandex_text2xml($tmpName, true);
				break;
			}
			
			
			if(!$arOfferIn["DETAIL_PAGE_URL"])
			{
				$arOfferIn["URL"] = $arOffer["URL"]."#".$arOfferIn["ID"];
			}
			else
				$arOfferIn["URL"] = "http://".$_SERVER["SERVER_NAME"]. $arOfferIn["DETAIL_PAGE_URL"];

			if($arOfferIn["DETAIL_PICTURE"])
			{
				$db_file = CFile::GetByID( $arOfferIn["DETAIL_PICTURE"] );
				if ($ar_file = $db_file->Fetch())
					$arOfferIn["PICTURE"] = $ar_file["~src"] ? $ar_file["~src"] : "http://".$_SERVER["SERVER_NAME"]."/".(COption::GetOptionString("main", "upload_dir", "upload"))."/".$ar_file["SUBDIR"]."/".implode("/", array_map("rawurlencode", explode("/", $ar_file["FILE_NAME"])));
				unset($ar_file);
				unset($db_file);
			}
				
			if($arOfferIn["PREVIEW_PICTURE"] && !$arOfferIn["PICTURE"])
			{
				$db_file = CFile::GetByID($arOfferIn["PREVIEW_PICTURE"]);
				if ($ar_file = $db_file->Fetch())
					$arOfferIn["PICTURE"] = $ar_file["~src"] ? $ar_file["~src"] : "http://".$_SERVER["SERVER_NAME"]."/".(COption::GetOptionString("main", "upload_dir", "upload"))."/".$ar_file["SUBDIR"]."/".implode("/", array_map("rawurlencode", explode("/", $ar_file["FILE_NAME"])));
				unset($ar_file);
				unset($db_file);
			}

			if(isset($arParams["MORE_PHOTO"]) && $arParams["MORE_PHOTO"] != "YS_EMPTY"){
				
				$ph = CIBlockElement::GetProperty( $arOfferIn["IBLOCK_ID"], $arOfferIn["ID"], array("sort" => "asc"), Array("CODE" => $arParams["MORE_PHOTO"]) );
				$arOfferIn["MORE_PHOTO"] = array();

				while( ($ob = $ph->GetNext()) && count($arOfferIn["MORE_PHOTO"]) < 10)
				{
					$arFile = CFile::GetFileArray( $ob["VALUE"] );
					if ( !empty( $arFile ) )
					{
						if ( strpos( $arFile["SRC"], "http" ) === false )
						{
							$pic = "http://".$_SERVER["SERVER_NAME"].implode( "/", array_map( "rawurlencode", explode( "/", $arFile["SRC"] ) ) );
						}	
						else
						{
							$ar = explode( "http://", $arFile["SRC"] );
							$pic = "http://".implode( "/", array_map( "rawurlencode", explode( "/", $ar[1] ) ) );
							
						}
						$arOfferIn["MORE_PHOTO"][] = $pic;
					}
					unset($ob);
					unset($arFile);
				}
				unset($ph);
			}
			
			if(is_array($arOffer["MORE_PHOTO"]))
			foreach ($arOffer["MORE_PHOTO"] as $pic) {
				if (!in_array($pic, $arOfferIn["MORE_PHOTO"]) && count($arOfferIn["MORE_PHOTO"]) < 10)
					$arOfferIn["MORE_PHOTO"][] = $pic;
			}

			if(!$arOfferIn["PICTURE"])
			{
				if ($arOffer["PICTURE"]) $arOfferIn["PICTURE"] = $arOffer["PICTURE"];
				else
					if (is_array($arOfferIn["MORE_PHOTO"]))
						$arOfferIn["PICTURE"] = array_shift($arOfferIn["MORE_PHOTO"]);
			}
			$arOfferIn["MORE_PHOTO"] = array_slice($arOfferIn["MORE_PHOTO"], 0, 9);

			if($arOfferIn["PREVIEW_TEXT"])
			{
				$arOfferIn["PREVIEW_TEXT"] = yandex_text2xml(($arOfferIn["PREVIEW_TEXT_TYPE"]=="html"?preg_replace_callback("'&[^;]*;'", "yandex_replace_special", strip_tags($arOfferIn["~PREVIEW_TEXT"])) : $arOfferIn["~PREVIEW_TEXT"]), true);
			}
			
			if($arOfferIn["DETAIL_TEXT"])
			{
				$arOfferIn["DETAIL_TEXT"] = yandex_text2xml(($arOfferIn["DETAIL_TEXT_TYPE"]=="html"?preg_replace_callback("'&[^;]*;'", "yandex_replace_special", strip_tags($arOfferIn["~DETAIL_TEXT"])) : $arOfferIn["~DETAIL_TEXT"]), true);
			}
			
			$arOfferIn["DESCRIPTION"] = $arOfferIn["PREVIEW_TEXT"] ? $arOfferIn["PREVIEW_TEXT"] : $arOfferIn["DETAIL_TEXT"];
			
			if ($arParams["DETAIL_TEXT_PRIORITET"] == "Y")
			{
				$arOfferIn["DESCRIPTION"] = $arOfferIn["DETAIL_TEXT"] ? $arOfferIn["DETAIL_TEXT"] : $arOfferIn["PREVIEW_TEXT"];
			}

			if (!$arOfferIn["DESCRIPTION"])
			{
				$arOfferIn["DESCRIPTION"] = $arOffer["DESCRIPTION"];
			}
				
			// MARKET_CATEGORY
			//$nameIb = CIBlock::GetByID( $arOffer['IBLOCK_ID'] )->GetNext(); // name IB

			if ($arParams["MARKET_CATEGORY_CHECK"] == "Y")
			{
				$arOfferIn["MARKET_CATEGORY"] = $arOffer["MARKET_CATEGORY"];
			}
			
			// GROUP_ID
			$arOfferIn["GROUP_ID"] = $arOffer["ID"];
			// ID Ibloka cataloga
			$arOfferIn["IBLOCK_ID_CATALOG"] = $arOffer["IBLOCK_ID"];
			
			// ----------
			/* $arProps = CIBlockElement::GetProperty( $arOffer["IBLOCK_ID"], $arOffer["ID"], array("sort" => "asc"), Array("CODE" => $arParams["DEVELOPER"]) )->Fetch();
			$arOfferIn["DEVELOPER"] = $arProps["VALUE_ENUM"] ? $arProps["VALUE_ENUM"] : $arProps["VALUE"];
			
			$arProps = CIBlockElement::GetProperty( $arOffer["IBLOCK_ID"], $arOffer["ID"], array("sort" => "asc"), Array("CODE" => $arParams["COUNTRY"]) )->Fetch();
			$arOfferIn["COUNTRY"] = $arProps["VALUE_ENUM"] ? $arProps["VALUE_ENUM"] : $arProps["VALUE"]; */
			// ----------
			
			if ($arParams['SECTION_AS_VENDOR'] == 'Y')
			{
				if (!empty($arOffer['IBLOCK_SECTION_ID']))
				{
					$arOfferIn["DEVELOPER"] = $arOffer["DEVELOPER"];
				}
			}

			//include PARAMETRS [CATALOG_PRICE_ID, CATALOG_CURRENCY_ID] for oldprices from discounts adn Create Old Price
		/*	if ($arParams["OLD_PRICE_LIST"] == "FROM_DISCOUNT")
			 {
				foreach ($arPriceTypes as $priceType) {
					if (!empty($arOfferIn["PRICE_NOT_DISCONT"]))
					{
						$arOffer['CATALOG_PRICE_'.$priceType["ID"]] = $arOfferIn["PRICE_NOT_DISCONT"];
					} else {
						$arOffer['CATALOG_PRICE_'.$priceType["ID"]] = $arOfferIn["PRICE"];
					}

						$arOffer['CATALOG_CURRENCY_'.$priceType["ID"]] = $arOfferIn["CURRENCY"];
						unset($priceType);
				}
				$rsItemPrices = CIBlockPriceTools::GetItemPrices(false,$arPriceTypes,$arOffer,false,array(),0,SITE_ID);
				$arValue = reset($rsItemPrices);
				if ($arValue["VALUE"] >= $arValue["DISCOUNT_VALUE"] && (float)$arOfferIn["PRICE"] < (float)$arValue["VALUE"])
					$arOfferIn['OLD_PRICE'] = $arValue["VALUE"];
				
				if ($arParams['CURRENCIES_CONVERT'] != 'NOT_CONVERT')
				{
					$arOfferIn["OLD_PRICE"] = CCurrencyRates::ConvertCurrency( $arOfferIn["OLD_PRICE"], $arOfferIn["OLD_CURENCY"], $arParams["CURRENCIES_CONVERT"] );
					$arOfferIn["OLD_PRICE"] = round( $arOfferIn["OLD_PRICE"], $arCurrencies[$arOfferIn["CURRENCY"]]['DECIMALS'] );
				}	
			} */
			
			$arOfferIn['SALES_NOTES_OFFER'] = $arSalse_notes;
			
			
			$arResult["OFFER"][] = $arOfferIn;
			unset($arOldPrice);


		} // foreach ($arOffer["SKU"] as &$arOfferInID)

		if ($flag == 1) continue; //dalshe ne idem, a perehod k novomu tovaru
		
		if( !$bCatalog || $arParams['PRICE_FROM_IBLOCK'] == 'Y' )
		{	
			$arOffer["AVAIBLE"] = "true";
			if( isset( $arParams["IBLOCK_QUANTITY"] ) && $arParams["IBLOCK_QUANTITY"] != "YS_EMPTY" )
			{
				$av = CIBlockElement::GetProperty( $arOffer["IBLOCK_ID"], $arOffer["ID"], array("sort" => "asc"), Array("CODE" => $arParams["IBLOCK_QUANTITY"]) )->Fetch();
				if( IntVal($av["VALUE"]) > 0 )
					$arOffer["AVAIBLE"] = "true";
				else
				{
					if( $arParams["IBLOCK_ORDER"] == "Y" )
						$arOffer["AVAIBLE"] = "false";
					else
						continue;
				}
			}
		}
		
		if( $bCatalog && $arParams['PRICE_FROM_IBLOCK'] != 'Y')
		{
			if ( $arParams["CURRENCIES_CONVERT"] != "NOT_CONVERT" && $arOffer["CURRENCY"] != $arParams["CURRENCIES_CONVERT"] )
			{
				$arOffer["PRICE"] = CCurrencyRates::ConvertCurrency( $arOffer["PRICE"], $arOffer["CURRENCY"], $arParams["CURRENCIES_CONVERT"] );
				if ($arParams["OLD_PRICE_LIST"] != "TYPE_PRICE")
				{
					$arOffer["OLD_PRICE"] = CCurrencyRates::ConvertCurrency( $arOffer["OLD_PRICE"], $arOffer["CURRENCY"], $arParams["CURRENCIES_CONVERT"] );
					$arOffer["OLD_PRICE"] = round( $arOffer["OLD_PRICE"], $arCurrencies[$arOffer["CURRENCY"]]['DECIMALS'] );
				}
				$arOffer["CURRENCY"] = $arParams["CURRENCIES_CONVERT"];
			}
			$arOffer['PRICE'] = round($arOffer['PRICE'], $arCurrencies[$arOffer["CURRENCY"]]['DECIMALS']);

			if ( $arOffer['CURRENCY'] == "RUR" ) $arOffer['CURRENCY'] = "RUB";
			if ( !in_array($arOffer["CURRENCY"], $arResult["CURRENCIES"]) )
				$arResult["CURRENCIES"][] = $arOffer["CURRENCY"];
		}
		else
		{
			// $arOffer["PRICE"] = floatval(str_replace(" ", "", $arOffer["PROPERTY_".$arParams["PRICE_CODE"]."_VALUE"]));
			
			$arProps = CIBlockElement::GetProperty( $arOffer["IBLOCK_ID"], $arOffer['ID'], array("sort" => "asc"),
				Array("CODE" => $arParams["PRICE_CODE"]) )->Fetch();
				
			$arOffer["PRICE"] = $arProps["VALUE_ENUM"] ? $arProps["VALUE_ENUM"] : $arProps["VALUE"];
			$arOffer["PRICE"] = floatval(str_replace(" ", "", $arOffer["PRICE"]));
			unset($arProps);

			if( intval($arOffer["PRICE"]) <= 0 && $arParams['PRICE_REQUIRED'] != 'N' )
				continue;
			
			//$arOffer["CURRENCY"] = yenisite_yandex_GetCurrencies($arOffer['ID'], array() );
			
			if (!empty($arParams["CURRENCIES_PROP"]))
				$arProps = CIBlockElement::GetProperty( $arOffer["IBLOCK_ID"], $arOffer['ID'], array("sort" => "asc"), Array("CODE" => $arParams["CURRENCIES_PROP"]) )->Fetch();
			
			$arOffer["CURRENCY"] = empty($arProps["VALUE_XML_ID"]) ? $arParams["CURRENCY"] : $arProps["VALUE_XML_ID"];
			$arProps = null;
			
			if ( !in_array($arOffer["CURRENCY"], $arResult["CURRENCIES"]) )
				$arResult["CURRENCIES"][] = $arOffer["CURRENCY"];
		}
		
			// Need to work in result_modifer.php with $arParams['COND_PARAMS']
			// If no offers these parameters must be exist
			// --- Total bull shit. These parameters causes double calls of GetProperty. Fixed by Ilya F.
			// $arOffer["IBLOCK_ID_CATALOG"] = $arOffer["IBLOCK_ID"];
			// $arOffer["GROUP_ID"] = $arOffer["ID"];

		$arOffer["MODEL"] = yandex_text2xml($arOffer["MODEL"], true);
			
		$arOffer["SALES_NOTES_OFFER"] = yandex_text2xml($arSalse_notes, true);

		
		$arResult["OFFER"][]=$arOffer;


		$i++;
	}
	unset($arOffers);

	//fetc #arProps for PARAMS & COND_PARAMS
	$baseCur = getBaseCurrencyTempl();
	if ( !CModule::IncludeModule('currency') ) $baseCur = $arParams["CURRENCY"];
	$arCur = array();
	$arCur[0] = $baseCur;
	foreach( $arResult["CURRENCIES"] as $cur )
	{
		if ($cur == 'RUR')
		{
			$cur = 'RUB';
		}
		
		if ( !in_array( $cur, $arCur ) )
			$arCur[] = $cur;
	}

	$arResult["CURRENCIES"] = $arCur;

	if (!empty($arParams['COND_PARAMS'])){
		foreach($arParams['COND_PARAMS'] as $k=>$code) {
			if (empty($code)) continue;
			if ($code == "EMPTY") continue;

			foreach($arResult["OFFER"] as &$arOffer) {
				$props = CIBlockElement::GetProperty($arOffer["IBLOCK_ID"], $arOffer["ID"], array("sort" => "asc"), Array("CODE" => $code))->GetNext();
			
				$arOffer["CONDITION_PROPERTIES"][$code] = CIBlockFormatProperties::GetDisplayValue($arOffer, $props, "ym_out");
				$arOffer["CONDITION_PROPERTIES"][$code]["DISPLAY_VALUE"] = $arOffer["CONDITION_PROPERTIES"][$code]["VALUE_ENUM"] ? $arOffer["CONDITION_PROPERTIES"][$code]["VALUE_ENUM"] : strip_tags($arOffer["CONDITION_PROPERTIES"][$code]["DISPLAY_VALUE"]);
				$arOffer["CONDITION_PROPERTIES"][$code]["DISPLAY_NAME"] = $props["NAME"];
				$arOffer["CONDITION_PROPERTIES"][$code]["DISPLAY_DESCRIPTION"] = $props["DESCRIPTION"];
				unset($props);

				if (empty($arOffer['CONDITION_PROPERTIES'][$code]['DISPLAY_VALUE']) && !empty($arOffer['GROUP_ID'])) {
					$props = CIBlockElement::GetProperty($arOffer["IBLOCK_ID_CATALOG"], $arOffer["GROUP_ID"], array("sort" => "asc"), Array("CODE" => $code))->GetNext();
					$arOffer["CONDITION_PROPERTIES"][$code] = CIBlockFormatProperties::GetDisplayValue($arOffer, $props, "ym_out");
					$arOffer["CONDITION_PROPERTIES"][$code]["DISPLAY_VALUE"] = $arOffer["CONDITION_PROPERTIES"][$code]["VALUE_ENUM"] ? $arOffer["CONDITION_PROPERTIES"][$code]["VALUE_ENUM"] : strip_tags($arOffer["CONDITION_PROPERTIES"][$code]["DISPLAY_VALUE"]);
					$arOffer["CONDITION_PROPERTIES"][$code]["DISPLAY_NAME"] = $props["NAME"];
					$arOffer["CONDITION_PROPERTIES"][$code]["DISPLAY_DESCRIPTION"] = $props["DESCRIPTION"];
					unset($props);
				}
				$arOffer["CONDITION_PROPERTIES"][$code]["DISPLAY_DESCRIPTION"] = htmlspecialcharsBx($arOffer["CONDITION_PROPERTIES"][$code]["DISPLAY_DESCRIPTION"]);
				$arOffer["CONDITION_PROPERTIES"][$code]["DISPLAY_VALUE"] = htmlspecialcharsBx($arOffer["CONDITION_PROPERTIES"][$code]["DISPLAY_VALUE"]);
				$arOffer["CONDITION_PROPERTIES"][$code]["DISPLAY_NAME"] = htmlspecialcharsBx($arOffer["CONDITION_PROPERTIES"][$code]["DISPLAY_NAME"]);
			}

		}
	}

	if (!empty($arParams['PARAMS'])){
		foreach($arParams['PARAMS'] as $k=>$code) {
			if (empty($code)) continue;
			if ($code == "EMPTY") continue;
			
			foreach($arResult["OFFER"] as &$arOffer) {
				$props = CIBlockElement::GetProperty($arOffer["IBLOCK_ID"], $arOffer["ID"], array("sort" => "asc"), Array("CODE"=>$code))->GetNext();
				$arOffer["DISPLAY_PROPERTIES"][$code] = CIBlockFormatProperties::GetDisplayValue($arOffer, $props, "ym_out");
				$arOffer["DISPLAY_PROPERTIES"][$code]["DISPLAY_VALUE"] = $arOffer["DISPLAY_PROPERTIES"][$code]["VALUE_ENUM"]?$arOffer["DISPLAY_PROPERTIES"][$code]["VALUE_ENUM"]:strip_tags($arOffer["DISPLAY_PROPERTIES"][$code]["DISPLAY_VALUE"]);
				$arOffer["DISPLAY_PROPERTIES"][$code]["DISPLAY_NAME"] = $props["NAME"];
				unset($props);

				if (empty($arOffer["DISPLAY_PROPERTIES"][$code]["DISPLAY_VALUE"]) && !empty($arOffer['GROUP_ID'])) {
					$props = CIBlockElement::GetProperty($arOffer["IBLOCK_ID_CATALOG"], $arOffer["GROUP_ID"], array("sort" => "asc"), Array("CODE"=>$code))->GetNext();
					$arOffer["DISPLAY_PROPERTIES"][$code] = CIBlockFormatProperties::GetDisplayValue($arOffer, $props, "ym_out");
					$arOffer["DISPLAY_PROPERTIES"][$code]["DISPLAY_VALUE"] = $arOffer["DISPLAY_PROPERTIES"][$code]["VALUE_ENUM"]?$arOffer["DISPLAY_PROPERTIES"][$code]["VALUE_ENUM"]:strip_tags($arOffer["DISPLAY_PROPERTIES"][$code]["DISPLAY_VALUE"]);
					$arOffer["DISPLAY_PROPERTIES"][$code]["DISPLAY_NAME"] = $props["NAME"];
					unset($props);
				}
				$arOffer["DISPLAY_PROPERTIES"][$code]["DISPLAY_VALUE"] = htmlspecialcharsBx($arOffer["DISPLAY_PROPERTIES"][$code]["DISPLAY_VALUE"]);
				$arOffer["DISPLAY_PROPERTIES"][$code]["DISPLAY_NAME"] = htmlspecialcharsBx($arOffer["DISPLAY_PROPERTIES"][$code]["DISPLAY_NAME"]);
			}
		}
	}

	$this->IncludeComponentTemplate();

	if ($arParams["CACHE_NON_MANAGED"] == 'Y') {
		$obCache->EndDataCache();
	}
}

if(!$bDesignMode)
{
	$r = $APPLICATION->EndBufferContentMan();
	echo $r;
	if(defined("HTML_PAGES_FILE") && !defined("ERROR_404")) CHTMLPagesCache::writeFile(HTML_PAGES_FILE, $r);
	die();
}
?>
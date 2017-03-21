<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

header( 'Content-Type: text/html; charset=' . $CHARSET );


//var_dump( COption::SetOptionString( 'zimin.logic', 'date', 'qweqwe1' ) );

//var_dump( COption::GetOptionString( 'zimin.logic', 'date' ) );


//var_dump( COption::RemoveOption( 'zimin.logic', 'date' ) );

//var_dump( $arResult['CATEGORIES'] ); die;
//var_dump( $arResult['OFFER'][272] ); die;
//var_dump( $arResult['OFFER'][187] ); die;

$catalogTree = createTree( $arResult['CATEGORIES'], $arResult['OFFER'] );	

function createTree( $flatCategories, $flatProducts )
{
  $categoriesToOffers = Array();
  foreach( $flatProducts as $offer ) 
  {
    if( !isset( $categoriesToOffers[ $offer['CATEGORY'] ] ) ) $categoriesToOffers[ $offer['CATEGORY'] ] = Array();
    
    $categoriesToOffers[ $offer['CATEGORY'] ][] = Array(
      'ID' => $offer['ID'],
      'name' => $offer['MODEL'],
      'pictureId' => $offer['PICTURE'],
      'sort' => (int)$offer['SORT'],
      'prices' => Array(
        'opt1' => $offer['price7'],
        'opt2' => $offer['price8'],
        'opt3' => $offer['price9'],
        'mrc' => $offer['price6'],
      ),
      'details' => $offer['DETAIL_TEXT'],
      'type' => 'offer'
    );

    //обрабоать безкатегорийных, если такие есть вообще
    //if
  }

  function addBranches( &$list, $parents, $offers )
  {
    $branch = array();
      
      foreach( $parents as $category ) 
      {
          if( isset( $list[ $category['ID'] ] ) )
          {
            $category['subcategories'] = addBranches( $list, $list[ $category['ID'] ], $offers );
          }

          $category['offers'] = $offers[ $category['ID'] ];
          $branch[] = $category;
      } 
      
      return $branch;
  }

  $tree = array();
  foreach( $flatCategories as $category )
  {
    //проставить топовым элементам parent 0, если не стоит
    if( !isset( $category['PARENT'] ) ) 
      $category['PARENT'] = 0;
    
    $category['type'] = 'category';

    $tree[ $category['PARENT'] ][] = $category;
  }

  return addBranches( $tree, $tree[0], $categoriesToOffers );
}

global $stop;
$stop = false;

function printTreeAsTable( $items, $depth = 0 )
{
  global $stop;
  if( $stop ) return;

  foreach( $items as $item )
  {
    if( $item['type'] == 'offer' && !$item['price'] ) continue;

    ?>
      <tr class='level<?= $depth + 1 ?> <?= $item['type'] ?> id-<?= $item['ID'] ?>'>
<?
    if( $item['type'] == 'offer' )
    { 
      ?>
        <td class='picture'><? 

      if( $item['pictureId'] ) 
      { 
        $resizedPicture = CFile::ResizeImageGet( $item['pictureId'],
          array( 
            'width' => 100, 
            'height' => 100 
          ), 
          BX_RESIZE_IMAGE_PROPORTIONAL, false, false, false, 
          80 //jpg quality
        );

        ?><img src='<?= $resizedPicture['src']?>'><? 
      }

      ?></td>
        <td class='name'><?= $item['name'] ?></td>
        <td class='details'><?= $item['details'] ?></td>
        <td class='price'><? if( $item['price'] ) echo $item['price']; ?></td>
<?
    }
    else
    {
      ?>
        <td class='name' colspan='4'><?= $item['NAME'] ?></td>
<?
    }
  
  ?>
      </tr>
<?
    
    //stop early for testing
    //if( $item['ID'] == 12750 ) $stop = true;
    
    //print both that and that
    if( isset( $item['offers'] ) ) 
      printTreeAsTable( $item['offers'], $depth + 1 );
    if( isset( $item['subcategories'] ) ) 
      printTreeAsTable( $item['subcategories'], $depth + 1 );

  } 
}

function printTreeAsVitya( $topCats )
{
  function printCatalogSections( $section )
  {
    $protocolForUrls = ( CMain::IsHTTPS() ) ? 'https://' : 'http://';
    
    usort( $section['subcategories'], function( $a, $b )
    {
        if( $a['sort'] == $b['sort'] ) return 0;
        return ($a['sort'] < $b['sort']) ? -1 : 1;
    });


    foreach( $section['subcategories'] as $category )
    {
      ?>
    <h3><?= $category['NAME'] ?></h3>
<?
      if( isset( $category['offers'] ) && !empty( $category['offers'] ) )
      {
        ?>
    <table class="table-responsive">
      <thead>
        <tr>
          <th>Изображение</th>
          <th>Описание</th>
          <th>ОПТ3</th>
          <th>ОПТ2</th>
          <th>ОПТ1</th>
          <th>МРЦ</th>
          <th>Наличие</th>
        </tr>
      </thead>
      <tbody>
<?
       
        usort( $category['offers'], function( $a, $b )
        {
            if( $a['sort'] == $b['sort'] ) return 0;
            return ($a['sort'] < $b['sort']) ? -1 : 1;
        });
        

        foreach( $category['offers'] as $item )
        {
          ?>
        <tr>
          <td data-label="Изображение" class="img"><? 

          if( $item['pictureId'] ) 
          { 
            $resizedPicture = CFile::ResizeImageGet( $item['pictureId'],
              array( 
                'width' => 100, 
                'height' => 100 
              ), 
              BX_RESIZE_IMAGE_PROPORTIONAL, false, false, false, 
              80 //jpg quality
            );

            if( $resizedPicture['src'] ) 
            {
              ?><img d='sad' src='<?= $protocolForUrls . $_SERVER['HTTP_HOST'] . $resizedPicture['src'] ?>' width="200" height="200"><? 
            }
            
          }


          ?></td>
          <td data-label="Описание"><span class="product-name"><?= $item['name'] ?></span><!-- <span class="label label--green">Новинка!</span> <span class="label label--red">Хит продаж!</span> <span class="label">OMG! Вне конкуренции</span> -->
          <p><?= $item['details'] ?></p>
          </td>
          <td data-label="Цена ОПТ3" class="price-rub"><? if( $item['prices']['opt3'] ) echo $item['prices']['opt3']; ?></td>
          <td data-label="Цена ОПТ2" class="price-rub"><? if( $item['prices']['opt2'] ) echo $item['prices']['opt2']; ?></td>
          <td data-label="Цена ОПТ1" class="price-rub"><? if( $item['prices']['opt1'] ) echo $item['prices']['opt1']; ?></td>
          <td data-label="МРЦ" class="price-rub"><? if( $item['prices']['mrc'] ) echo $item['prices']['mrc']; ?></td>
          <td data-label="Наличие">
            <div class="available"><span class="available--many">•••••</span></div>
          </td>
<?
        }
    
        ?>  
      </tbody>
    </table>
<?
      }


      if( $category['subcategories'] )
      {
        printCatalogSections( $category );
      }
    }
  }

  foreach( $topCats as $topCat )
  {
    ?>
    <h2><?= $topCat['NAME'] ?></h2>
    <div class="price-list--section">
<?
    printCatalogSections( $topCat );
?>
    </div>
<?

    //stop early for testing
    //if( $topCat['NAME'] == 'Видеонаблюдение' ) return;
  } 
}

/*
<link href="css/<? if( isset( $_GET['print'] ) ) echo 'printstyle.css'; else echo 'htmlstyle.css'; ?>" rel="stylesheet" media="all">
*/
?>
<!DOCTYPE html>
<html class="no-js" lang="ru">

<head>
	<meta charset="utf-8">
	<title>Прайс-лист КАРКАМ® Электроникс™</title>
	<meta name="description" content="">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="css/print.css" rel="stylesheet" media="all">
	<link href="css/<? if( isset( $_GET['render'] ) ) echo 'renderstyle.css'; else echo 'style.css'; ?>" rel="stylesheet" media="all">
	<script>
	// Маркер работающего javascript
	document.documentElement.className = document.documentElement.className.replace('no-js', 'js');
	</script>

</head>

<body>
    <div class='page-header-for-print'><img src='img/page-header-for-print.png'></div>
     <header class="page-header">
      <div class="page-header--wriper">
          <div class="page-header--logo"><svg data-name="1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 340.75 112.05"><path d="M24.08 14.31a6 6 0 0 1 1.32 4 123.41 123.41 0 0 1-1 14l-1 8.4.38.93q7.12-11.62 11-17.63 9.33-14 12.3-15.67c1.27.38 3 1.88 5.22 4.48A9.23 9.23 0 0 1 54.4 16q-.28.72-12.4 22.32c-2.15 3.87-4 7.57-5.52 11.07l9.13 14.12q3.27 5.22 3.27 6.7 0 1.7-5.63 6.9t-7.35 5.2q-3.9 0-8.7-10.2c-1.07-2.25-3.1-6.77-6.1-13.58-.93.72-1.63 3.4-2.1 8q-1.7 16.6-2.3 17.58a29.44 29.44 0 0 1-9.12-1.72q-6.3-2.1-7.08-4.78 2.1-6 4.88-23.2l4.53-27.5q2.3-13.72 4.2-14.4a59 59 0 0 1 10 1.8zm76.22 2.1c-1.83-1.33-4.93-2.62-9.33-3.85a40.23 40.23 0 0 0-10.3-1.87 13.49 13.49 0 0 0-2.1.58q-5.37 6.7-21.38 50.3-6.1 16.7-6.1 19.63c0 1.52 1.4 2.87 4.2 4a23.49 23.49 0 0 0 10 1.57l1.52-.1c.45-3.13.95-6.28 1.47-9.48a45 45 0 0 1 2.4-9.12 43.92 43.92 0 0 1 5 .4 41 41 0 0 0 4.95.42h1.22a5.8 5.8 0 0 0 1.47-.18 66.08 66.08 0 0 0 1.32 12.4c1.28.92 3.28 1.37 6 1.37a22.36 22.36 0 0 0 6.4-1.17c2.8-1 4.57-2 5.32-3.23a179.17 179.17 0 0 1-2.93-34.27q0-4.12.72-12.37t.7-12.35a7.58 7.58 0 0 0-.48-2.68zM85.07 38.88l-.93 15.87q-3 2.3-10.1 2.1 8.53-26.73 11.13-29.55c.38 0 .58.93.58 2.78a30.23 30.23 0 0 1-.2 3.28c-.27 2.6-.42 4.43-.48 5.52zm64.55-6.8q0 12.8-15 23.2a105.68 105.68 0 0 1-12.2 7.13c-.45.85-.9 3.77-1.32 8.78q-1 13.93-4.3 13.92a29.48 29.48 0 0 1-8.1-1.62c-3.78-1.2-6.13-2.43-7.08-3.7q1.23-9.62 3.22-20.8.3-1.72 3.77-20.8 3-16.13 3.72-20.9c.12-.78 1.2-2.18 3.22-4.2q1-1.23 13.08-1.23a21.07 21.07 0 0 1 15 5.67 19.14 19.14 0 0 1 6 14.55zm-22-7.8l-5.1 25.33c2.67-1 5.15-3.77 7.42-8.3q3.07-6.3 3.08-11c0-3.45-1.82-5.45-5.42-6zm44.33-10a59 59 0 0 0-10-1.8q-1.9.68-4.2 14.4l-4.53 27.48q-2.8 17.2-4.88 23.2.78 2.67 7.08 4.78a29.44 29.44 0 0 0 9.12 1.72q.6-1 2.3-17.58c.47-4.62 1.17-7.3 2.1-8 3 6.82 5 11.33 6.1 13.58q4.8 10.2 8.7 10.2 1.7 0 7.35-5.2t5.63-6.9q0-1.47-3.27-6.7l-9.13-14.12c1.53-3.5 3.37-7.2 5.52-11.07q12.1-21.6 12.4-22.32a9.23 9.23 0 0 0-2.1-3.18c-2.22-2.6-4-4.1-5.22-4.48q-3 1.7-12.3 15.67-3.88 6-11 17.63l-.38-.93 1-8.4a123.41 123.41 0 0 0 1-14 6 6 0 0 0-1.32-4zm76.22 2.1a7.58 7.58 0 0 1 .48 2.68q0 4.1-.7 12.35t-.72 12.37a179.17 179.17 0 0 0 2.93 34.27c-.75 1.2-2.52 2.28-5.32 3.23a22.36 22.36 0 0 1-6.4 1.17c-2.73 0-4.73-.45-6-1.37a66.08 66.08 0 0 1-1.32-12.4 5.8 5.8 0 0 1-1.47.18h-1.22a41 41 0 0 1-4.95-.42 43.92 43.92 0 0 0-5-.4 45 45 0 0 0-2.4 9.12c-.52 3.2-1 6.35-1.47 9.48l-1.52.1a23.5 23.5 0 0 1-10-1.57c-2.8-1.13-4.2-2.48-4.2-4q0-3 6.1-19.63 16-43.6 21.38-50.3a13.49 13.49 0 0 1 2.1-.58 40.23 40.23 0 0 1 10.3 1.87c4.4 1.23 7.5 2.52 9.33 3.85zm-15.26 22.5c.07-1.08.22-2.92.48-5.52a30.23 30.23 0 0 0 .2-3.28c0-1.85-.2-2.78-.58-2.78q-2.6 2.85-11.13 29.55 7.12.2 10.1-2.1l.93-15.87zm58.1 41.6l9.18-42.38-.3-.68c-.68.45-2.12 3.65-4.28 9.57l-6.49 17.62q-3.62 9.55-6 12.3c-2.68 0-4.42-.47-5.23-1.42-.45-.52-1.08-5.87-1.9-16q-1.28-16.25-2.2-19.1c-.65.63-1.42 4.25-2.3 10.9l-2.48 19.72c-.95 7.07-1.93 11.3-3 12.7a8.59 8.59 0 0 1-2.2.4q-5.7 0-12.83-4.3a7.78 7.78 0 0 1-.4-1.08 10.52 10.52 0 0 1-.38-1.12q0-3.73 2.88-15.92 2.3-9.48 6.1-23.68 1.7-6 5-18.12c1-3.58 3.5-5.37 7.57-5.37a30.43 30.43 0 0 1 6.73.87c.92.2 3.12.83 6.6 1.92a17.62 17.62 0 0 1 .38 4.48c0 .82 0 2-.1 3.72s-.1 2.9-.1 3.72v12.58l.4 1.28q1.7-2.3 4.88-10.4 1.9-4.9 8.4-21.48a6.32 6.32 0 0 1 1.9-.5 27.94 27.94 0 0 1 14.9 4.68l1.32 2.4-.1 1.42q-.1 1.7-1.72 16.5-1.8 16.8-3.07 27.4-1.82 15-2.87 18c-.7 2-3.72 3-9.05 3-4.97-.01-8.05-1.23-9.23-3.63z" fill="#ef7c00" fill-rule="evenodd"/><path d="M331.77 0a9.1 9.1 0 0 1 4.43 1.16 8.32 8.32 0 0 1 3.34 3.31 9 9 0 0 1 0 9 8.53 8.53 0 0 1-3.32 3.32 9 9 0 0 1-8.94 0 8.52 8.52 0 0 1-3.32-3.32 9 9 0 0 1 0-9 8.32 8.32 0 0 1 3.34-3.31A9.1 9.1 0 0 1 331.73 0zm0 1.78a7.33 7.33 0 0 0-3.55.92 6.7 6.7 0 0 0-2.68 2.66 7.21 7.21 0 0 0 0 7.22 6.81 6.81 0 0 0 2.66 2.67 7.19 7.19 0 0 0 7.18 0 6.85 6.85 0 0 0 2.67-2.67 7.19 7.19 0 0 0 0-7.22 6.69 6.69 0 0 0-2.68-2.66 7.38 7.38 0 0 0-3.56-.92zm-4.06 12h1.94V9.87h.5a2.13 2.13 0 0 1 1.33.4 6.42 6.42 0 0 1 1.32 2l.79 1.55h2.4l-1.08-1.96c-.4-.68-.63-1.06-.7-1.16a4.77 4.77 0 0 0-.64-.73 2.39 2.39 0 0 0-.66-.36 3.07 3.07 0 0 0 1.88-.91 2.51 2.51 0 0 0 .67-1.76 2.67 2.67 0 0 0-.36-1.36 2.38 2.38 0 0 0-.89-.91 3.69 3.69 0 0 0-1.49-.38h-4.93v9.5zm1.94-5.42h.78a9.64 9.64 0 0 0 2.07-.13 1.22 1.22 0 0 0 .64-.44 1.19 1.19 0 0 0 .23-.72 1.15 1.15 0 0 0-.23-.7 1.25 1.25 0 0 0-.65-.44 9.33 9.33 0 0 0-2.06-.13h-.78v2.57z" fill="#1a1a18" fill-rule="evenodd"/><rect y="92.52" width="307.55" height="19.53" rx="2.83" ry="2.83" fill="#1a1a18"/><path d="M20.24 103.16c0-6.69-.66-8-5.7-8a31.31 31.31 0 0 0-5.63.45v1.63s2.49-.24 5.1-.22c3 0 3.48 0 3.52 4.88h-8v1.71h8c0 5.15-.15 5.15-3.56 5.19-2.66 0-5.21-.24-5.21-.24v1.63a30.93 30.93 0 0 0 5.76.46c5.46 0 5.76-1.3 5.76-7.5zm55.15 7.24v-1.85h-8.6v-4.94h7.79v-1.72h-7.79v-4.62h8.47v-1.85H64.13v15h11.26zm29 0l-5.87-7.63 5.61-7.33h-3.15l-4.84 6.36H93.7v-6.36h-2.66v15h2.66v-6.67h2.44l5 6.67h3.21zm26.27-13v-1.94h-12.37v1.94h4.86v13h2.66v-13h4.88zm27.48 2.64c0-4.44-2-4.58-5.21-4.58h-7.02v15h2.66v-6h4.55c3.37 0 5.1-.15 5.1-4.42zm-2.75 0c0 2.57-.37 2.57-2.9 2.57h-4v-5.28h3.94c2.51 0 3 0 3 2.71zm31 3.08c0-7.15-.81-7.9-6.49-7.9s-6.51.75-6.51 7.9c0 6.84.44 7.59 6.51 7.59s6.49-.75 6.49-7.59zm-2.71.09c0 5.54-.09 5.54-3.7 5.54h-.2c-3.61 0-3.67 0-3.67-5.54v-.2c0-5.83.29-5.83 3.67-5.83h.2c3.41 0 3.7 0 3.7 5.83v.2zm31.31 7.24v-15h-2.68v6.36h-7.17v-6.36h-2.66v15h2.66v-6.67h7.17v6.67h2.68zm28.84 0v-15h-2.57l-7.35 11.27V95.43h-2.57v15h2.57l7.37-11.29v11.29h2.57zm29.68 0l-5.87-7.63 5.61-7.33h-3.15l-4.84 6.36h-2.44v-6.36h-2.66v15h2.66v-6.67h2.44l5 6.67h3.21zm26.27-.2v-1.74s-2.55.26-5.21.24c-3.45 0-3.56 0-3.56-5.52 0-6 .33-6 3.52-6.05 2.64 0 5.13.22 5.13.22v-1.79a31.73 31.73 0 0 0-5.7-.44c-5 0-5.68 1.3-5.68 8 0 6.2.31 7.5 5.76 7.5a30.81 30.81 0 0 0 5.76-.47z" fill="#fff" fill-rule="evenodd"/><path fill="#1a1a18" fill-rule="evenodd" d="M49.35 95.44h-2.77l-4.49 13-4.53-13h-2.8l5.28 14.96h4.05l5.26-14.96"/><path fill="#fff" fill-rule="evenodd" d="M34.47 110.26h2.77l4.49-13.01 4.53 13.01h2.8L43.78 95.3h-4.05l-5.26 14.96"/></svg></div>
          <nav class="page-header--site-menu site-menu">
              <ul>
                  <li><a href="/">Главная</a></li>
                  <li><a href="/about/">О компании</a></li>
                  <li><a href="/buy/">Где купить?</a></li>
                  <li><a href="/cooperation.php">ОПТ</a></li>
                  <li><a href="/contact/">Контакты</a></li>
                  <li><a href="/support/">Поддержка</a></li>
                  <li><a href="/delivery/">Доставка и оплата</a></li>
                  <li><a href="http://camcloud.ru/" class="camcloud-link">Camсloud <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 width="16px" height="16px" viewBox="0 0 430.117 430.117" style="enable-background:new 0 0 430.117 430.117;"
	 xml:space="preserve">
<g>
	<path id="logo-camcloud" d="M430.117,275.749c0,46.983-37.812,85.071-84.437,85.071H84.422C37.798,360.82,0,322.732,0,275.749
		c0-42.433,30.826-77.585,71.156-84.017c-0.511-2.978-0.793-6.042-0.793-9.196c0-29.769,23.952-53.898,53.506-53.898
		c13.18,0,25.258,4.802,34.576,12.792c16.612-37.715,37.908-72.134,97.652-72.134c72.456,0,106.797,56.303,106.797,115.715
		c0,2.485-0.104,4.947-0.271,7.383C401.135,200.301,430.117,234.613,430.117,275.749z"/>
</g>
</svg>
</a></li>
              </ul>
          </nav>

          <div class="page-header--phone">
              <div class="phone">
                  <a href="tel:88005556808" class="phone--number">8&nbsp;800&nbsp;555-6-808</a><span class="phone--distr">Ежедневно, без выходных</span>
              </div>

          </div>
          <form action="/search/catalog.php" method="get" id="search_form" _lpchecked="1" class="page-header--search search" accept-charset="windows-1251">
              <input type="text" name="q" id="title-search-input" placeholder="Я ищу" tabindex="1" class="search--text search__focus" autofocus>
              <!--div class="search--select__wriper">
                  <select tabindex="2" class="search--select__input search__focus">
                      <option class="search-option">Товары</option>
                      <option class="search-option">Новости</option>
                      <option class="search-option">Везде</option>
                  </select>
              </div-->
              <input type="submit" tabindex="3" value="" class="search--submit search__focus">
              <p class="search--label">Например, <a href="/autoelectronica/radardetek/carcam-combo-3.html">КАРКАМ Комбо 3</a></p>
          </form>

      </div>
      <nav class="page-header--catalog-menu catalog-menu clearfix">
          <ul>
              <li><a href="/autoelectronica/">Автоэлектроника</a></li>
              <li><a href="/video-monitoring/">Видеонаблюдение</a></li>
              <li><a href="/security-systems/">Сигнализация</a></li>
              <li><a href="/digital-technology/">Радиосвязь</a></li>
              <li><a href="/personal-security/">Личная безопастность</a></li>
              <li><a href="/access-control/">Контроль доступа</a></li>
              <li><a href="/sport/">Спорт и отдых</a></li>
          </ul>
      </nav>

  </header>

	<main>
    	<article class="price-list">
		<h1 class="site-name">Прайс-лист</h1>
<?
/*
Прайс-лист на <? echo date($DB->DateFormatToPHP( CSite::GetDateFormat("FULL")), time()); ?>
*/

$catalogTree = $catalogTree[0]['subcategories'];

printTreeAsVitya( $catalogTree );

?>
	</article>

	</main>
  <footer class="page-footer">
      <div class="page-footer--payment-method">
          <img src="img/payment-method.png" alt="Способы оплаты">
      </div>
      <div class="page-footer--wrp clearfix">
          <nav class="page-footer__footer-nav footer-nav">
              <div class="footer-nav--wrp">
                  <p class="footer-nav__name">Покупателям</p>
                  <ul>
                      <li><a href="/">Главная</a></li>
                      <li><a href="/about/">О компании</a></li>
                      <li><a href="/buy/">Где купить?</a></li>
                      <li><a href="/contact/">Контакты</a></li>
                      <li><a href="/support/">Поддержка</a></li>
                      <li><a href="/verify/">Черный список</a></li>
                      <li><a href="/delivery/">Доставка и оплата</a></li>
                      <li><a href="http://camcloud.ru/">Camcloud</a></li>
                  </ul>
              </div>
              <div class="footer-nav--wrp">
                  <p class="footer-nav__name">Каталог товаров</p>
                  <ul>
                      <li><a href="/autoelectronica/">Автоэлектроника</a></li>
                      <li><a href="/video-monitoring/">Видеонаблюдение</a></li>
                      <li><a href="/security-systems/">Сигнализация</a></li>
                      <li><a href="/digital-technology/">Радиосвязь</a></li>
                      <li><a href="/personal-security/">Личная безопасность</a></li>
                      <li><a href="/access-control/">Контроль доступа</a></li>
                      <li><a href="/sport/">Спорт и отдых</a></li>
                      <li><a href="/pricelist/">Скачать прайс-лист</a></li>
                  </ul>
              </div>
              <div class="footer-nav--wrp">
                  <p class="footer-nav__name">Сотруднечество</p>
                  <ul>
                      <li><a href="/vacancy/">Вакансии</a></li>
                      <li><a href="/cooperation.php">ОПТ</a></li>
                  </ul>
              </div>
              <div class="footer-nav--wrp footer-nav--wrp--last">
                  <p class="footer-nav__name">Контактная информация</p>
                  <div class="footer-nav__phone">
                      <div class="phone">
                          <a href="tel:88005556808" class="phone--number">8&nbsp;800&nbsp;555-6-808</a><span class="phone--distr">Ежедневно, без выходных</span>
                      </div>

                  </div>
              </div>
          </nav>
          <div class="page-footer__footer-subscription footer-subscription clearfix">
              <form class="footer-subscription__form-subscription form-subscription" method="POST" action="https://cp.unisender.com/ru/subscribe?hash=53se5rddhhpd6xcjnu3ufqmghxhar7ufy8u4xcirrfyysshrypgco" name="subscribtion_form">
                  <label for="name" class="footer-subscription--label">Подписка на рассылку</label>
                  <input class="footer-subscription--email-input" type="text" name="email" placeholder="email" tabindex="3" value="">
                  <input type="submit" value="Подписатся" class="footer-subscription--button" tabindex="4" />
                  <span class="footer-subscription--caption">100%&nbsp;без&nbsp;спама. 3&nbsp;раза&nbsp;в&nbsp;месяц</span>


                  <input type="hidden" name="charset" value="Windows-1251">
                  <input type="hidden" name="default_list_id" value="8246374">
                  <input type="hidden" name="overwrite" value="2">
                  <input type="hidden" name="is_v5" value="1">

              </form>
              <div class="footer-subscription__social clearfix">
                  <ul>
                      <li><a href="https://www.youtube.com/user/KAPKAMTB"><svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 90.677 90.677"><path d="M82.287 45.907c-.937-4.07-4.267-7.074-8.275-7.52-9.49-1.06-19.098-1.066-28.66-1.06-9.566-.006-19.173 0-28.665 1.06-4.006.447-7.334 3.45-8.27 7.52C7.083 51.704 7.067 58.032 7.067 64c0 5.97 0 12.297 1.334 18.094.937 4.07 4.265 7.073 8.273 7.52 9.49 1.062 19.097 1.066 28.662 1.062 9.566.005 19.17 0 28.664-1.06 4.005-.45 7.335-3.452 8.27-7.522C83.605 76.297 83.61 69.97 83.61 64c0-5.97.01-12.296-1.323-18.093zM28.9 50.4h-5.54v29.438h-5.146V50.4h-5.44v-4.822H28.9V50.4zm13.977 29.44h-4.63v-2.786c-1.838 2.108-3.584 3.136-5.285 3.136-1.49 0-2.517-.604-2.98-1.897-.252-.772-.408-1.994-.408-3.796V54.31H34.2v18.796c0 1.084 0 1.647.04 1.8.112.717.463 1.08 1.083 1.08.928 0 1.898-.714 2.924-2.165V54.31h4.63v25.53zm17.573-7.663c0 2.36-.16 4.062-.468 5.144-.618 1.9-1.855 2.87-3.695 2.87-1.646 0-3.234-.914-4.78-2.824v2.474H46.88V45.578h4.626v11.19c1.494-1.84 3.08-2.77 4.78-2.77 1.84 0 3.08.97 3.696 2.88.31 1.027.468 2.715.468 5.132v10.167zm17.457-4.26h-9.25v4.526c0 2.363.772 3.543 2.362 3.543 1.138 0 1.8-.62 2.065-1.855.043-.25.104-1.278.104-3.133h4.718v.675c0 1.49-.057 2.518-.1 2.98-.154 1.024-.518 1.953-1.08 2.77-1.28 1.855-3.178 2.77-5.594 2.77-2.42 0-4.262-.872-5.6-2.615-.98-1.278-1.484-3.29-1.484-6.003v-8.94c0-2.73.447-4.726 1.43-6.016C66.816 54.87 68.657 54 71.02 54c2.32 0 4.16.87 5.457 2.618.97 1.29 1.432 3.286 1.432 6.015v5.285h-.003z"/><path d="M70.978 58.163c-1.546 0-2.32 1.18-2.32 3.54v2.363h4.624v-2.362c0-2.36-.774-3.54-2.304-3.54zM53.812 58.163c-.762 0-1.534.36-2.307 1.125v15.56c.772.773 1.545 1.14 2.307 1.14 1.334 0 2.012-1.14 2.012-3.446V61.646c0-2.302-.678-3.483-2.012-3.483zM56.396 34.973c1.705 0 3.48-1.036 5.34-3.168v2.814h4.675V8.82h-4.674v19.718c-1.036 1.464-2.018 2.188-2.953 2.188-.626 0-.994-.37-1.096-1.095-.057-.152-.057-.72-.057-1.816V8.82h-4.66v20.4c0 1.822.156 3.055.414 3.836.47 1.307 1.507 1.917 3.012 1.917zM23.85 20.598v14.02h5.185V20.6L35.27 0h-5.24L26.49 13.595 22.812 0h-5.455c1.093 3.21 2.23 6.434 3.323 9.646 1.663 4.828 2.7 8.468 3.17 10.952zM42.22 34.973c2.34 0 4.16-.88 5.452-2.64.98-1.292 1.45-3.326 1.45-6.068V17.23c0-2.757-.468-4.773-1.45-6.076-1.29-1.765-3.11-2.646-5.453-2.646-2.33 0-4.15.88-5.444 2.646-.993 1.303-1.463 3.32-1.463 6.077v9.035c0 2.742.47 4.776 1.463 6.067 1.293 1.76 3.113 2.64 5.443 2.64zm-2.232-18.68c0-2.386.724-3.576 2.23-3.576 1.508 0 2.23 1.19 2.23 3.577v10.852c0 2.387-.722 3.58-2.23 3.58-1.506 0-2.23-1.193-2.23-3.58V16.294z"/></svg>
</a></li>
                      <li><a href="https://vk.com/carcamofficial"><svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 96.496 96.496"><path d="M92.5 65.178c-2.874-3.446-6.255-6.387-9.454-9.51-2.886-2.815-3.068-4.448-.748-7.697 2.532-3.545 5.255-6.955 7.81-10.485 2.385-3.3 4.823-6.59 6.078-10.54.796-2.512.092-3.622-2.485-4.062-.443-.077-.902-.08-1.354-.08l-15.29-.02c-1.882-.027-2.923.794-3.59 2.463-.898 2.256-1.825 4.51-2.896 6.687-2.43 4.936-5.144 9.707-8.95 13.747-.838.89-1.766 2.017-3.168 1.553-1.754-.64-2.27-3.53-2.242-4.507l-.015-17.647c-.34-2.522-.9-3.646-3.402-4.136l-15.882.003c-2.12 0-3.182.82-4.314 2.145-.653.766-.85 1.263.492 1.517 2.636.5 4.12 2.205 4.515 4.848.632 4.223.588 8.463.224 12.703-.107 1.24-.32 2.474-.81 3.63-.77 1.817-2.01 2.187-3.638 1.07-1.476-1.013-2.512-2.44-3.526-3.875-3.81-5.382-6.848-11.186-9.326-17.285-.716-1.762-1.95-2.83-3.818-2.86-4.587-.072-9.175-.084-13.762.005-2.76.052-3.583 1.392-2.46 3.894C5.486 37.85 11.047 48.655 18.306 58.497c3.727 5.05 8.006 9.51 13.534 12.67 6.264 3.582 13.008 4.66 20.11 4.328 3.327-.156 4.326-1.02 4.48-4.336.104-2.268.36-4.523 1.48-6.56 1.098-2 2.76-2.382 4.678-1.138.96.623 1.767 1.416 2.53 2.252 1.872 2.048 3.677 4.158 5.62 6.137 2.437 2.48 5.324 3.946 8.954 3.647l14.052.003c2.264-.148 3.438-2.924 2.138-5.45-.913-1.77-2.11-3.347-3.383-4.872z"/></svg>
</a></li>
                      <li><a href="https://instagram.com/officialcarcam/"><svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 169.063 169.063"><path d="M122.406 0H46.654C20.93 0 0 20.93 0 46.655v75.752c0 25.726 20.93 46.655 46.654 46.655h75.752c25.727 0 46.656-20.93 46.656-46.655V46.655C169.062 20.93 148.132 0 122.406 0zm31.657 122.407c0 17.455-14.2 31.655-31.656 31.655H46.654C29.2 154.062 15 139.862 15 122.407V46.655C15 29.2 29.2 15 46.654 15h75.752c17.455 0 31.656 14.2 31.656 31.655v75.752z"/><path d="M84.53 40.97c-24.02 0-43.562 19.542-43.562 43.563 0 24.02 19.542 43.56 43.563 43.56s43.564-19.54 43.564-43.56c0-24.02-19.542-43.563-43.563-43.563zm0 72.123c-15.748 0-28.562-12.812-28.562-28.56 0-15.75 12.813-28.564 28.563-28.564s28.564 12.812 28.564 28.562c0 15.75-12.814 28.56-28.563 28.56zM129.92 28.25c-2.89 0-5.728 1.17-7.77 3.22-2.05 2.04-3.23 4.88-3.23 7.78 0 2.892 1.18 5.73 3.23 7.78 2.04 2.04 4.88 3.22 7.77 3.22 2.9 0 5.73-1.18 7.78-3.22 2.05-2.05 3.22-4.89 3.22-7.78 0-2.9-1.17-5.74-3.22-7.78-2.04-2.05-4.88-3.22-7.78-3.22z"/></svg>
</a></li>
                      <li><a href="https://www.facebook.com/officialcarcam/"><svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 96.227 96.227"><path d="M73.1 15.973l-9.06.004c-7.1 0-8.476 3.375-8.476 8.328v10.92h16.938l-.006 17.107H55.564v43.895H37.897V52.332h-14.77V35.226h14.77V22.612C37.897 7.972 46.84 0 59.9 0l13.2.02v15.953z"/></svg>
</a></li>
                      <li><a href="https://ok.ru/group/53146108690665"><svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 95.481 95.481"><path d="M43.04 67.254c-7.4-.772-14.075-2.595-19.79-7.064-.708-.556-1.44-1.092-2.087-1.713-2.5-2.402-2.753-5.153-.774-7.988 1.692-2.427 4.534-3.076 7.488-1.683.572.27 1.117.607 1.64.97 10.648 7.316 25.277 7.518 35.966.328 1.06-.812 2.19-1.474 3.503-1.812 2.55-.655 4.93.282 6.3 2.514 1.563 2.55 1.543 5.037-.384 7.016-2.956 3.034-6.51 5.23-10.46 6.76-3.736 1.45-7.827 2.178-11.876 2.662.61.665.9.992 1.28 1.376 5.5 5.525 11.02 11.026 16.5 16.567 1.868 1.888 2.258 4.23 1.23 6.425-1.124 2.4-3.64 3.98-6.107 3.81-1.563-.108-2.782-.886-3.865-1.977-4.15-4.175-8.376-8.273-12.44-12.527-1.184-1.237-1.753-1.003-2.797.07-4.174 4.298-8.416 8.53-12.683 12.736-1.916 1.89-4.196 2.23-6.418 1.15-2.362-1.145-3.865-3.556-3.75-5.98.08-1.638.887-2.89 2.012-4.013C30.97 79.45 36.395 74 41.823 68.56c.36-.363.694-.747 1.217-1.306z"/><path d="M47.55 48.33c-13.205-.046-24.033-10.993-23.956-24.22C23.67 10.74 34.504-.036 47.84 0c13.362.036 24.087 10.967 24.02 24.478-.068 13.2-10.97 23.897-24.31 23.85zm12-24.187c-.022-6.567-5.252-11.795-11.806-11.8-6.61-.008-11.886 5.315-11.835 11.942.048 6.542 5.323 11.733 11.895 11.71 6.552-.024 11.768-5.286 11.746-11.852z"/></svg>
</a></li>
                      <li><a href="https://plus.google.com/105300135578829343674"><svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 512 512"><path d="M319.317 213.333H153.6v76.8h83.345c-11.204 31.855-38.357 51.2-73.054 51.2-46.386 0-87.09-39.893-87.09-85.367 0-45.44 40.704-85.3 87.09-85.3 22.18 0 40.927 7.084 54.222 20.472l6.033 6.084 57.148-57.148-6.212-6.033c-27.05-26.282-65.492-40.174-111.188-40.174C71.996 93.866 0 165.07 0 255.966c0 90.93 71.996 162.167 163.89 162.167 84.71 0 144.897-49.698 157.074-129.698 1.587-10.377 2.398-21.3 2.406-32.47 0-12.26-1.05-25.565-2.807-35.575l-1.246-7.057zM460.843 213.292v-51.158h-59.776v51.2h-51.2v59.734h51.2v51.2h59.776v-51.2H512v-59.776"/></svg>
</a></li>
                      <li><a href="https://www.linkedin.com/company/9187983"><svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 93.06 93.06"><path d="M11.185.08C5.005.08 0 5.092 0 11.26c0 6.172 5.003 11.183 11.186 11.183 6.166 0 11.176-5.01 11.176-11.184 0-6.17-5.01-11.18-11.177-11.18zM1.538 30.926h19.287V92.98H1.538zM69.925 29.383c-9.382 0-15.673 5.144-18.248 10.022h-.258v-8.48h-18.5V92.98h19.27v-30.7c0-8.092 1.54-15.93 11.575-15.93 9.89 0 10.022 9.255 10.022 16.45v30.178H93.06V58.942c0-16.707-3.605-29.56-23.135-29.56z"/></svg>
</a></li>
                      <li class="hide"></li>
                  </ul>
                  
              </div>
          </div>
      </div>

  </footer>
      <div class="page-footer__copyright copyright">
          2010 - 2017 г. © КАРКАМ® Электроникс™
      </div>
  <div class="floating-button">
      <a href="javascript:window.print()"><div class="floating-button--print">Распечатать</div></a>
<?

if( CUser::IsAdmin() )
{
  ?>
      <a target="_blank" href="pdf?new"><div class="floating-button--download-pdf" style='background: green;'>Cгенерировать новый PDF</div></a>
<?
}   

?>  
      <a target="_blank" href="pdf"><div class="floating-button--download-pdf">Скачать PDF</div></a>
      <!--<div class="floating-button--download-excel">Скачасть XML</div>-->
  </div>
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
  <script>window.jQuery || document.write('<script src="js/jquery.3.1.1.min.js"><\/script>')</script>
  <script src="js/script.min.js"></script>

</body>
</html>
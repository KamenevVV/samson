<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
header('Content-Type: text/html; charset=utf-8');

function db2html( int $id )
{
    // установим соедиение с базой данных и кодировку
    $mysqli = new mysqli('localhost', 'mysql', 'mysql', 'test_samson');
    if (mysqli_connect_errno())
    {
    printf("Не удалось подключиться: %s\n", mysqli_connect_error());
    exit();
    }
    $query = $mysqli->query('set names utf8');
    
    $where = '';
    $query = "
    SELECT  `product_id`,
            `product_code`,
            `product_name`,
            `pra`.`price`,
            `pro`.`property`,
            `cat`.`category`
        FROM `a_product`
        LEFT JOIN (
            SELECT `product_id`, 
		            GROUP_CONCAT('\"',`price_id`,'\":\{\"',`price_type`,'\":',`price`,'}') `price`
                FROM  `a_price`
                $where
                GROUP BY `product_id`) `pra`
              USING (`product_id`)
        LEFT JOIN (
            SELECT `product_id`, 
		            GROUP_CONCAT('\"',`property_id`,'\":\{\"',`property_name`,'\":\"',`property_value`,' | ',`property_unit`,'\"}') `property`
                FROM  `a_property`
                $where
                GROUP BY `product_id`) `pro`
              USING (`product_id`)
        LEFT JOIN (
            SELECT `product_id`,
		            GROUP_CONCAT('\"',`category_id`,'\":\"',`category_name`,'\"') `category`
		        FROM `a_prod_cat`
		            LEFT JOIN `a_category` USING (`category_id`)
                        $where
                        GROUP BY `product_id`) `cat`
                    USING (`product_id`)
        $where
        ORDER BY `product_id`";
        
    $stmt = $mysqli->prepare( $query );
	$stmt->execute();
    $stmt->store_result();
    $stmt->bind_result( $product_id,
                        $product_code,
                        $product_name,  
                        $price,
                        $property,
                        $category);
    if ( $stmt->num_rows )
    {
        while( $stmt->fetch() )
        {
            $product[$product_id]['Код'] = $product_code;
            $product[$product_id]['Название'] = $product_name;
            $product[$product_id]['Цена'] = json_decode('{'.$price.'}',true);
            $product[$product_id]['Свойства'] = json_decode('{'.$property.'}',true);
            $product[$product_id]['Раздел'] = json_decode('{'.$category.'}',true);
        }
    }
    // собираем таблицу
    $table = "
<table class=\"table table-striped\">
 <thead class=\"thead-dark\">
  <tr>
    <th>Код</th><th>Название</th><th>Цена</th><th>Свойства</th></th><th>Раздел</th>
  </tr>
 </thead>
  ";
        foreach ( $product as $key=>$value )
        {
        $table .=
  "<tr>
    <td>". $product[$key]['Код'] ."</td>
    <td>". $product[$key]['Название'] ."</td>
    <td>";
            foreach ( $product[$key]['Цена'] as $key2=>$value2 )
            {
                foreach ( $value2 as $key3=>$value3 )
                {
                    $table .= "<div>". $key3  ." ". $value3 ."</div>";
                }
            }
        $table .="</td>
    <td>";
            foreach ( $product[$key]['Свойства'] as $key2=>$value2 )
            {
                foreach ( $value2 as $key3=>$value3 )
                {
                    $vaun = explode( ' | ', $value3 );
                    $table .= "<div>". $key3  ." ". $vaun[0] . $vaun[1]. "</div>";
                }
            }
        $table .="</td>
    <td>";
            foreach ( $product[$key]['Раздел'] as $key2=>$value2 )
            {

                    $table .= "<div>". $value2 ."</div>";
            }
        $table .="</td>
  ";
        }

 $table .= "</tr>
</table>";
    
//    print_r($product);
    return $table;
}


?>
<html>
<head>
<title>Блок №2</title>
<link rel="stylesheet" 
      href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" 
      integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" 
      crossorigin="anonymous">
</head>
<body>
  <div class="container">
    <div class="row">
       <div class="col-10 text-right"><h1>Список товаров</h1></div> 
    </div>
    <div class="row">
      <div class="col-12">
<?php
echo $content = db2html(1);


?>
      </div>
    </div>
  </div>
</body>
</html>
<?php









/*
SELECT `product_id`,
            `pra`.`price`,
            `pro`.`property`
        FROM `a_product`
        LEFT JOIN (SELECT  `product_id`, 
		                    GROUP_CONCAT('\"',`price_id`,'\":\{\"',`price_type`,'\":',`price`,'}') `price`
                        FROM  `a_price`
                   		WHERE `product_id` = 1
                        GROUP BY `product_id`) `pra`
                USING (`product_id`)
        LEFT JOIN (SELECT  `product_id`, 
		                GROUP_CONCAT('\"',`property_id`,'\":\{\"',`property_name`,'\":\"',`property_value`,'\"}') `property`
                        FROM  `a_property`
                   		WHERE `product_id` = 1
                        GROUP BY `product_id`) `pro`
                USING (`product_id`)
        WHERE `product_id` = 1
        
        
SELECT `product_id`,
            `pra`.`price`,
            `pro`.`property`,
            `cat`.`category`
        FROM `a_product`
        LEFT JOIN (SELECT  `product_id`, 
		                    GROUP_CONCAT('\"',`price_id`,'\":\{\"',`price_type`,'\":',`price`,'}') `price`
                        FROM  `a_price`
                   		WHERE `product_id` = 1
                        GROUP BY `product_id`) `pra`
                USING (`product_id`)
        LEFT JOIN (SELECT  `product_id`, 
		                GROUP_CONCAT('\"',`property_id`,'\":\{\"',`property_name`,'\":\"',`property_value`,'\"}') `property`
                        FROM  `a_property`
                   		WHERE `product_id` = 1
                        GROUP BY `product_id`) `pro`
                USING (`product_id`)
        LEFT JOIN (SELECT `product_id`,
		                GROUP_CONCAT('\"',`category_id`,'\":\"',`category_name`,'\"') `category`
		                FROM `a_prod_cat`
		                LEFT JOIN `a_category` USING (`category_id`)
                        WHERE `product_id` = 1
                        GROUP BY `product_id`) `cat`
                USING (`product_id`)
        WHERE `product_id` = 1
*/        
/*
SELECT  `product_id`, `product_code`, `product_name`, 
        `price_id`, `price_type`, `price`, 
        `property_id`, `property_name`, `property_value`, `property_unit` 
    FROM (SELECT `product_id` 
            FROM `a_prod_cat` 
            WHERE `category_id` IN("
            . implode(',', array_fill( 1, count( $num ), '?'  ) ) 
            ." )) pc
    LEFT JOIN `a_product` USING(`product_id`)
    LEFT JOIN `a_price` USING(`product_id`)
    LEFT JOIN `a_property` USING(`product_id`)
    ORDER BY `product_name`

function importXml2()
{
$str = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<ServiceResult>
  <ErrorCode>RequestLimitReached</ErrorCode>
  <Message>Sorry, you do not have enough service units left in your account for this request. You will get more free units in: 6 days 23 hours</Message>
  <ErrorCode>RequestLimitReached</ErrorCode>
</ServiceResult>
XML;
$xml = simplexml_load_string($str);
print_r($xml);
$xPaths=array(
    '/ErrorCode'=>'hello', //ничего не делаем
    '/mynode/filters/filter'=>'filter', //вызовем функцию filter()
    '/mynode/someStrangeThing'=>'someStrangeThing', //функцию someStrangeThing()
    '/mynode/pid'=>'pid'  //pid()
);
foreach ($xml->xpath('/ServiceResult/ErrorCode') as $path => $setting) {
echo $path . $setting;
        }
    }

//$import2 = importXml2();


$string = <<<XML
<Товары>
 <Товар Код="111" Название="Ноутбук">
  <Цена Тип="Базовая">23500</Цена>
  <Цена Тип="Акция">21300</Цена>
  <Свойства>
      <Платформа>Intell</Платформа>
  </Свойства>
 </Товар>
 <Товар Код="222" Название="Ноутбук">
  <Цена Тип="Базовая">27999</Цена>
  <Цена Тип="Акция">26499</Цена>
  <Свойства>
      <Платформа>AMD</Платформа>
  </Свойства>
 </Товар>
 <Товар Код="333" Название="Ноутбук">
  <Цена Тип="Базовая">27999</Цена>
  <Цена Тип="Акция">26499</Цена>
  <Свойства>
      <Платформа>AMD</Платформа>
  </Свойства>
 </Товар>
</Товары>
XML;

$xml = new SimpleXMLElement($string);

$xpath = $xml->xpath('//Товар');
foreach( $xpath as $key=>$tag){
        $product[$key]['Код'] = (string)$tag[@Код];
        $product[$key]['Название'] = (string)$tag[@Название];
        $product[$key]['Цена']['Тип'][(string)$tag->Цена[@Тип]] = (string)$tag->Цена;
}
echo '<pre>';
print_r($product);
echo '</pre>';
*/        

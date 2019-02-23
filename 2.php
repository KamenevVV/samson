<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
header('Content-Type: text/html; charset=utf-8');

/**
* функция преобразования строки
* если в строке $a содержится 2 и более подстроки $b, то 
* во втором месте заменить подстроку $b на инвертированную подстроку
* решение "в лоб"
*/
function convertString( string $a, string $b):string
{
    if ( $pos = strpos( $a, $b ) !== false  )
    if ( $pos = strpos( $a, $b, $pos += strlen( $b ) - 1 ) )
    return substr_replace( $a, strrev( $b ), $pos, strlen( $b ) );
    return $a;
}

function num_end($number, $titles){
	$cases = array (2, 0, 1, 1, 1, 2);
	return $titles[ ($number%100>4 && $number%100<20)? 2 : $cases[min($number%10, 5)] ];
}

/**
* функция преобразования строки
* массив $c содержит порядковые номера вхождения подстроки $b
* в строке $a которые необходимо заменить на инвертированную подстроку $b
*/
function convertString2( string $a, string $b, array $c )
{
    $pos = 0;
    $count = 1;
    $cb = strlen( $b );
    do
    {
        $pos = strpos( $a, $b, $pos );
        if ( $pos === false ) break;
        if (in_array( $count, $c )) 
        {
            $a = substr_replace( $a, strrev( $b ), $pos, strlen( $b ) );
        }
        $pos += strlen( $b ) - 1;
        $count++;

    }while ( $pos !== false );
    return $a;  
}

 /**
 * функция импорта товаров XML -> DB
 */
function importXml( string $a)
{
    if ( !is_file( $a ) ) return false;
    $xml = simplexml_load_file($a);
    
    // установим соедиение с базой данных и кодировку
    $mysqli = new mysqli('localhost', 'mysql', 'mysql', 'test_samson');
    if (mysqli_connect_errno())
    {
    printf("Не удалось подключиться: %s\n", mysqli_connect_error());
    exit();
    }
    $query = $mysqli->query('set names utf8');
    
    // добавим счётчик итераций, который будем использовать в качестве суррогатного первичного ключа товара
    // так как ни имя ни код товара не уникальны
    $surr = 0;             // ключ обеспечивающий уникальность товара и связи с параметрами товара на этапе вставки в БД 
    $product_bind[]  = ''; // первая строка массива параметров используемая для хранения литералов типа переменных для таблицы a_product
    $price_bind[]    = ''; // первая строка массива параметров используемая для хранения литералов типа переменных для таблицы a_price
    $property_bind[] = ''; // первая строка массива параметров используемая для хранения литералов типа переменных для таблицы a_property
    $category_bind[] = ''; // первая строка массива параметров используемая для хранения литералов типа переменных для таблицы a_category
    $prod_cat_bind[] = ''; // первая строка массива параметров используемая для хранения литералов типа переменных для таблицы связи a_prod_cat
    $category_unique = array();
    $report = '';
    
    // парсим XML файл и собираем массивы параметров
    foreach ( $xml->Товар as $p )
    {
        $surr++;
        $code = (int)$p['Код'] ?? NULL;
        $name = $mysqli->real_escape_string((string)$p['Название']);
        $product_bind[0] .= str_pad('', 2, 'is');
        $product_bind[] = $code;
        $product_bind[] = $name;
        $product[$surr] = $name; // этот массив будет содержать соответсвие суррогатного ключа реальному ключу из БД
        
        foreach ( $p->Цена as $c )
        {
            $type  = $mysqli->real_escape_string((string)$c['Тип']);
            $price = $mysqli->real_escape_string((string)$c);
            $price_bind[0] .= str_pad('', 3, 'isd');
            $price_bind[] = $surr;
            $price_bind[] = $type;
            $price_bind[] = $price;
        }
        foreach ( $p->Свойства->children() as $key=>$s )
        {
            $key = $mysqli->real_escape_string($key);
            $property = $mysqli->real_escape_string((string)$s);
            $property_bind[0] .= str_pad('', 3, 'iss');
            $property_bind[] = $surr;
            $property_bind[] = $key;
            $property_bind[] = $property;
        }
        foreach ( $p->Разделы->children() as $g )
        {
            $cat = $mysqli->real_escape_string((string)$g);
            $category_bind[0] .= str_pad('', 3, 'is');
            $category_bind[] = $surr;
            $category_bind[] = $cat;
            $category_unique[] = $cat;
            $category[][$surr] = $cat;
        }
    }
    // если добавлять нечего, закрываем соединение и выходим
    if ( empty( $product ) )
    {
        $mysqli->close();
        return false;
    }
    // начинаем транзакцию
    $mysqli->query( 'START TRANSACTION;' );
    // таблица категорий не имеет внешних ключей, добавление в БД начнём с неё, если есть что добавить
    if ( !empty( $category_unique ))
    {
        $category_unique = array_unique($category_unique);
        // сформируем запрос SELECT что бы знать какие разделы из файла XML уже записаны в БД
        $report .= '<h3>Таблица a_category</h3>';
        $query = "SELECT `category_id`,`category_name` 
                    FROM `a_category` 
                    WHERE `category_name` IN ("
                    . implode(',', array_fill( 1, count( $category_unique ), '?'  ) ) 
                    ." )";
        foreach($category_unique as $key => $value)
            $category_unique[$key] = &$category_unique[$key];
        array_unshift( $category_unique, str_pad('', count($category_unique), 's') );
        $stmt = $mysqli->prepare( $query );
        call_user_func_array([$stmt, 'bind_param'], $category_unique);
	    $stmt->execute();
        $stmt->store_result();
        unset( $category_unique[0] ); 
        if ( $stmt->num_rows ){
            // выводим информацию о запросе
            $report .= '<p>- уже содержит '
                    . $stmt->num_rows .' раздел'.num_end($stmt->num_rows, ['','а','ов'])
                    . ' из '. count( $category_unique ) .' добавля'
                    . num_end($stmt->num_rows, ['емого','емых','емых']).'</p>';
            $stmt->bind_result ( $category_id, $category_name );
            while ( $stmt->fetch() )
            {
                // сохраняем результат выборки, что бы не доставать эти записи повторно
                // этот массив будет использоваться для заполнения таблицы связи категорий и продуктов
                $in_db_category[$category_name] = $category_id;
                if ( $key = array_search( $category_name, $category_unique ) ) unset($category_unique[$key]);
            }
        }
        $stmt->close();
        if ( !empty( $category_unique ) )
        {
            // если остались не занесённые в БД разделы, то сформируем запрос INSERT IGNORE
            // на тот случай если кто-то другой произвёл вставку чуть раньше (поле `category_name` должно быть уникальным)
            $query = "INSERT IGNORE INTO `a_category` 
                            ( `category_name` ) 
                            VALUES ". implode(',', array_fill( 1, count( $category_unique ), '(?)'  ) );
            array_unshift( $category_unique, str_pad('', count($category_unique), 's') );
            $stmt = $mysqli->prepare( $query );
            call_user_func_array([$stmt, 'bind_param'], $category_unique);
		    $stmt->execute();
            $stmt->store_result();
            unset( $category_unique[0] );
            if ( $stmt->affected_rows )
            {
                $report .= '<p>- добавлено: '. $stmt->affected_rows .' раздел'. num_end($stmt->affected_rows, ['','а','ов']) .'</p>'; ;
            }
            $stmt->close();
            // снова производим выборку на этот раз "свежих идентификаторов"
            $query = "SELECT `category_id`,`category_name` 
                        FROM `a_category` 
                        WHERE `category_name` IN ("
                        . implode(',', array_fill( 1, count( $category_unique ), '?'  ) ) 
                        ." )";
            array_unshift( $category_unique, str_pad('', count($category_unique), 's') );
            $stmt = $mysqli->prepare( $query );
            call_user_func_array([$stmt, 'bind_param'], $category_unique);
		    $stmt->execute();
            $stmt->store_result();
            unset( $category_unique[0] );      
            $stmt->bind_result ( $category_id, $category_name );
            while ( $stmt->fetch() )
            {
                // добавляем "свежие" категории в массив
                $in_db_category[$category_name] = $category_id;
            }
            $stmt->close();
        }
    }
    
    // добавляем товары в базу данных 
    // поля `product_name` и `product_code` не уникальны
    $query = "INSERT INTO `a_product` 
                    ( `product_code`, `product_name` ) 
                    VALUES ". implode(',', array_fill( 1, count( $product_bind )/2, '(?,?)'  ) );
    foreach( $product_bind as $key => $value )
        $product_bind[$key] = &$product_bind[$key];
    $stmt = $mysqli->prepare( $query );
    call_user_func_array( [$stmt, 'bind_param'], $product_bind );
	$stmt->execute();
    $stmt->store_result();
    if ( $stmt->affected_rows == count( $product ) )
    {
        $first_id = $stmt->insert_id; // получаем идентификатор первой записи
        $stmt->close();
        // добавляем товару его идентификатор
        foreach ( $product as $value )
        {
              $prod_id[$first_id++] = $value;
        }
        $product = array_combine( array_keys( $product ), array_keys( $prod_id ) );
        // формируем массив параметров для запроса к таблице связи товар - раздел
        foreach ( $category as $value )
        {
            foreach ( $value as $key=>$val)
            {
                $prod_cat_bind[0] .= str_pad('', 2, 'ii');
                $prod_cat_bind[] = (int)$product[$key];
                $prod_cat_bind[] = (int)$in_db_category[$val];
            }
        }
    }
    else
    {
        // что то пошло не так при добавлении товара, отменяем транзакцию и выходим
        $report .= '<h4>Произошла ошибка. Откат изменений.</4>';
        echo $report;
        $mysqli->query( 'ROLLBACK;' );
        $mysqli->close();
        return false;
    }
    
    if ( !empty( $prod_cat_bind ) )
    {
        // добавляем информацию о принадлежности продукта к категории в таблицу связи
        $report .='<h3>Таблица a_prod_cat</h3>';
        $query = "INSERT IGNORE INTO `a_prod_cat`
                             ( `product_id`, `category_id` ) 
                             VALUES ". implode(',', array_fill( 1, (count( $prod_cat_bind )-1)/2, '(?,?)'  ) );
        foreach( $prod_cat_bind as $key => $value )
            $prod_cat_bind[$key] = &$prod_cat_bind[$key];
        $stmt = $mysqli->prepare( $query );
        call_user_func_array( [$stmt, 'bind_param'], $prod_cat_bind );
	    $stmt->execute();
        $stmt->store_result();
        if ( $stmt->affected_rows != (count( $prod_cat_bind )-1)/2 ) 
        {
            // что то пошло не так при добавлении товара, отменяем транзакцию и выходим
            $report .= '<h4>Произошла ошибка. Откат изменений.</4>';
            echo $report;
            $mysqli->query( 'ROLLBACK;' );
            $mysqli->close();
            return false;
        }
        $report .='<p>- добавлено: '. $stmt->affected_rows .' связ'. num_end($stmt->affected_rows, ['ь','и','ей']) .'</p>';
        $stmt->close();
    }
    
    if ( !empty( $price_bind ) )
    {
        // добавляем информацию о ценах на товары
        $report .='<h3>Таблица a_price</h3>';
        $query = "INSERT INTO `a_price` 
                         ( `product_id`, `price_type`, `price` ) 
                         VALUES ". implode(',', array_fill( 1, (count( $price_bind )-1)/3, '(?,?,?)'  ) );
        foreach( $price_bind as $key => $value )
            $price_bind[$key] = &$price_bind[$key];
        $stmt = $mysqli->prepare( $query );
        call_user_func_array( [$stmt, 'bind_param'], $price_bind );
	    $stmt->execute();
        $stmt->store_result();
        if ( $stmt->affected_rows != (count( $price_bind )-1)/3 )
            {
                // что то пошло не так при добавлении товара, отменяем транзакцию и выходим
                $report .= '<h4>Произошла ошибка. Откат изменений.</4>';
                echo $report;
                $mysqli->query( 'ROLLBACK;' );
                $mysqli->close();
                return false;
            }
        $report .='<p>- добавлено: '. $stmt->affected_rows .' связ'. num_end($stmt->affected_rows, ['ь','и','ей']) .'</p>';
        $stmt->close();
    }
    if ( !empty( $property_bind ) )
    {
        // добавляем информацию о свойствах товара
        $report .='<h3>Таблица a_property</h3>';
        $query = "INSERT INTO `a_property` 
                         ( `product_id`, `property_name`, `property_value` ) 
                         VALUES ". implode(',', array_fill( 1, (count( $property_bind )-1)/3, '(?,?,?)'  ) );
        foreach( $property_bind as $key => $value )
            $property_bind[$key] = &$property_bind[$key];
        $stmt = $mysqli->prepare( $query );
        call_user_func_array( [$stmt, 'bind_param'], $property_bind );
	    $stmt->execute();
        $stmt->store_result();
        if ( $stmt->affected_rows != (count( $property_bind )-1)/3 )
            {
              // что то пошло не так при добавлении товара, отменяем транзакцию и выходим
                $report .= '<h4>Произошла ошибка. Откат изменений.</4>';
                echo $report;
                $mysqli->query( 'ROLLBACK;' );
                $mysqli->close();
                return false;
            }
        $report .='<p>- добавлено: '. $stmt->affected_rows .' связ'. num_end($stmt->affected_rows, ['ь','и','ей']) .'</p>';
        $stmt->close();
    }
    $report .='<h4>Изменения успешно внесены в базу данных.</h4>';
    $mysqli->query( 'COMMIT;' );
    $mysqli->close();
    
    echo $report;
}

$import = importXml( 'Product.xml' );

?>
<html>
<head>
<title>Блок №2</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
</head>
<body>
<pre><div class="container">
<?php
//echo '<h3>convertString()</h3>'; echo convertString( 'abcdf abcdf abcdf', 'abcdf' );  echo '<hr />';
//echo '<h3>convertString2()</h3>'; echo convertString2( 'abcdf abcdf abcdf', 'abcdf', [ 1, 3 ] ); echo '<hr />';
?>
</div></pre>
</body>
</html>
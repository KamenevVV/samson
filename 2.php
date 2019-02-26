<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
header('Content-Type: text/html; charset=utf-8');
//header('Content-type: text/html; charset=WINDOWS-1251');

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
* $a – двумерный массив вида [['a'=>2,'b'=>1],['a'=>1,'b'=>3]] 
* $b – ключ вложенного массива. Результат ее выполнения: двумерный массив $a отсортированный 
* по возрастанию значений для ключа $b. В случае отсутствия ключа $b в одном из вложенных массивов, 
* выбросить ошибку класса Exception с индексом неправильного массива
* с использованием ksort
*/
function mySortForKey( array $a, string $b ):array
{
    foreach ( $a as $key=>$value )
    {
        if ( !array_key_exists( $b, $value ) ) throw new Exception( 'Вложенный массив с индексом ['.$key.'] не содержит ключ \''.$b.'\'' );
        $vs[$value[$b]] = $value;
    }
    ksort($vs);
    return array_values($vs);
}

/**
* $a – двумерный массив вида [['a'=>2,'b'=>1],['a'=>1,'b'=>3]] 
* $b – ключ вложенного массива. Результат ее выполнения: двумерный массив $a отсортированный 
* по возрастанию значений для ключа $b. В случае отсутствия ключа $b в одном из вложенных массивов, 
* выбросить ошибку класса Exception с индексом неправильного массива
* сортировка "пузырьком"
*/
function mySortForKey2( array $a, string $b ):array
{
    do{
        $flag = false;
        for ( $i=1; $i<count($a); $i++ )
        {
            if ( !array_key_exists( $b, $a[$i-1] ) ) 
                throw new Exception( 'Вложенный массив с индексом ['. $i-1 .'] не содержит ключ \''.$b.'\'' );
            if ( !array_key_exists( $b, $a[$i] ) )
                throw new Exception( 'Вложенный массив с индексом ['. $i .'] не содержит ключ \''.$b.'\'' );
            $fir = $a[$i-1];
            $sec = $a[$i];
            if ( $fir[$b] < $sec[$b] ) continue;
            $a[$i-1] = $a[$i];
            $a[$i] = $fir;
            $flag = true;
        }
        
    }while($flag);
    return $a;
}


/**
* вспомогательная функция для вывода окончаний
*/
function num_end($number, $titles){
	$cases = array (2, 0, 1, 1, 1, 2);
	return $titles[ ($number%100>4 && $number%100<20)? 2 : $cases[min($number%10, 5)] ];
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
    $product = array();
    $report = '';
    
    // парсим XML файл и собираем массивы параметров
    if ( isset($xml->Товар) )
    {
    foreach (  $xml->Товар as  $p )
    {
        $surr++;
        $code = (int)$p['Код'] ?? 0;
        $name = (string)$p['Название'] ?? '';
        $name = $mysqli->real_escape_string( $name );
        $product_bind[0] .= str_pad('', 2, 'is');
        $product_bind[] = $code;
        $product_bind[] = $name;
        $product[$surr] = $name; // этот массив будет содержать соответсвие суррогатного ключа реальному ключу из БД
        $allparse[$surr]['Код'] = $code;
        $allparse[$surr]['Название'] = $name;
        if (isset(  $p->Цена ) )
        {
        foreach ( $p->Цена as $c ) 
        {
            $type  = (string)$c['Тип'] ?? '';
            $price = (string)$c ?? '';
            $type  = $mysqli->real_escape_string( $type );
            $price = $mysqli->real_escape_string( $price );
            $prac[$surr]['Цена'][$type] = $price;
            $allparse[$surr]['Цена'][$type] = $price;
        }
        }
        if ( isset( $p->Свойства ) )
        {
        foreach ( $p->Свойства->children() as $key=>$s )
        {
            $key = ($key) ?? '';
            $key = $mysqli->real_escape_string($key);
            $atrr = ( $s['ЕдИзм'] ) ? $mysqli->real_escape_string( $s['ЕдИзм'] ) : '' ;
            $property = $mysqli->real_escape_string((string)$s);
            $prop[$surr]['Свойства'][$key][$property] = $atrr;
            $allparse[$surr]['Свойства'][$key][$property] = $atrr;
        }
        }
        if ( isset ( $p->Разделы ) )
        {
        foreach ( $p->Разделы->children() as $g )
        {
            $cat = (string)$g ?? '';
            $cat = $mysqli->real_escape_string((string)$g);
            $category_bind[0] .= str_pad('', 3, 'is');
            $category_bind[] = $surr;
            $category_bind[] = $cat;
            $category_unique[] = $cat;
            $category[][$surr] = $cat;
            $allparse[$surr]['Раздел'][$cat] = $cat;
        }
        }
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
    
    // добавляем информацию о ценах на товары
    if ( !empty( $price_bind ) )
    {
        $prac = array_combine( array_keys( $prod_id ), $prac );
        foreach ( $prac as $key=>$value )
        {
            foreach ( $value as $key2=>$value2 )
            {
                foreach ( $value2 as $key3=>$value3 )
                {
                        $price_bind[0] .= str_pad('', 3, 'isd');
                        $price_bind[] = $key;
                        $price_bind[] = $key3;
                        $price_bind[] = $value3;
                }
            }
        }
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
    
    // добавляем информацию о свойствах товара
    if ( !empty( $property_bind ) )
    {
        $prop = array_combine( array_keys( $prod_id ), $prop );
        foreach ( $prop as $key=>$value )
        {
            foreach ( $value as $key2=>$value2 )
            {
                foreach ( $value2 as $key3=>$value3 )
                {
                    foreach ( $value3 as $key4=>$value4 )
                    {
                        $property_bind[0] .= str_pad('', 4, 'isss');
                        $property_bind[] = $key;
                        $property_bind[] = $key3;
                        $property_bind[] = $key4;
                        $property_bind[] = $value4;
                    }
                }
            }
        }
        $report .='<h3>Таблица a_property</h3>';
        $query = "INSERT INTO `a_property` 
                         ( `product_id`, `property_name`, `property_value`, `property_unit` ) 
                         VALUES ". implode(',', array_fill( 1, (count( $property_bind )-1)/4, '(?,?,?,?)'  ) );
        foreach( $property_bind as $key => $value )
            $property_bind[$key] = &$property_bind[$key];
        $stmt = $mysqli->prepare( $query );
        call_user_func_array( [$stmt, 'bind_param'], $property_bind );
	    $stmt->execute();
        $stmt->store_result();
        if ( $stmt->affected_rows != (count( $property_bind )-1)/4 )
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
    return true;
}

/**
* вспомогательная функция для функции экспорта данных БД -> XML
* рекурсивно достаёт вложенные категории
* массив $array - можно использовать для вывода вложенных разделов (не реализовано)
*/
function parent_category( object $mysqli, int $id, &$arr )
{
    $query = "SELECT `category_id` FROM `a_category` WHERE `parent_id` = ?";
    $stmt = $mysqli->prepare( $query );
    $stmt->bind_param( 'i', $id );
	$stmt->execute();
    $stmt->store_result();
    $stmt->bind_result( $category_id );
    if ( $stmt->num_rows )
    {
        while( $stmt->fetch() )
        {
            $arr[] = $category_id;
//            $array[] = $category_id;
            if( $par = parent_category( $mysqli, $category_id, $arr ) )
            {
//                $array[$category_id] = $par;
            }
        }
        $stmt->close();
        return $array;
    }
    $stmt->close();
    return false;
}

/**
* экспорт данных БД -> XML
*/
function exportXml( string $a, string $b )
{
    $array = array();
    // установим соедиение с базой данных и кодировку
    $mysqli = new mysqli('localhost', 'mysql', 'mysql', 'test_samson');
    if (mysqli_connect_errno())
    {
    printf("Не удалось подключиться: %s\n", mysqli_connect_error());
    exit();
    }
    $query = $mysqli->query('set names utf8');
    // поскольку глубина вложенности рубрик не ограничена, используем вспомогательную рекурсивную функцию
    // вариант доставать всю таблицу и обрабатывать данные на уровне РНР мне кажется менее привлекательным
    $query = "SELECT `category_id` FROM `a_category` WHERE `category_name` = ? ";
    $stmt = $mysqli->prepare( $query );
    $stmt->bind_param( 's', $b );
	$stmt->execute();
    $stmt->store_result();
    $stmt->bind_result( $category_id );
    if ( $stmt->num_rows )
    {
        while( $stmt->fetch() )
        {
            $num[] = $category_id;
//            $array[] = $category_id;
            if( $par = parent_category( $mysqli, $category_id, $num ) )
            {
//                $array[$category_id] = $par;
            }
        }
    }
    // результат выборки можно кешировать, что бы не напрягать БД лишний раз рекурсией
    
    // формируем запрос на выборку всех товаров категории и вложенных в неё подкатегорий
    $query = "
    SELECT DISTINCT 
            `product_id`, 
            `product_code`, 
            `product_name`, 
            `price_id`, 
            `price_type`, 
            `price`, 
            `property_id`, 
            `property_name`, 
            `property_value`, 
            `property_unit`,
            `a_category`.`category_id`,
            `category_name` 
        FROM (SELECT `product_id`, 
                     `category_id` 
                FROM `a_prod_cat` 
                WHERE `category_id` IN("
                . implode(',', array_fill( 1, count( $num ), '?'  ) ) 
                .")) pc  
        LEFT JOIN `a_product` USING(`product_id`)
        LEFT JOIN `a_price` USING(`product_id`)
        LEFT JOIN `a_property` USING(`product_id`)
        LEFT JOIN `a_prod_cat` USING(`product_id`)
        LEFT JOIN `a_category` ON `a_prod_cat`.`category_id` = `a_category`.`category_id`
        ORDER BY `product_name`";
    
    foreach($num as $key => $value)
        $num[$key] = &$num[$key];
    array_unshift( $num, str_pad('', count($num), 'i') );
    $stmt = $mysqli->prepare( $query );
    call_user_func_array([$stmt, 'bind_param'], $num);
	$stmt->execute();
    $stmt->store_result();
    $stmt->bind_result( $product_id, 
                        $product_code, 
                        $product_name, 
                        $price_id, 
                        $price_type, 
                        $price, 
                        $property_id, 
                        $property_name, 
                        $property_value, 
                        $property_unit,
                        $category_id,
                        $category_name );
    if ( $stmt->num_rows )
    {
        while( $stmt->fetch() )
        {
            $product[$product_id]['Товар']['Код'] = $product_code;
            $product[$product_id]['Товар']['Название'] = $product_name;
            $product[$product_id]['Цена'][$price_type] = $price;
            $product[$product_id]['Свойства'][$property_name][$property_value] = $property_unit;
            $product[$product_id]['Раздел'][$category_id] = $category_name;
        }
    }

    // собираем XML
    $xml = "<?xml version=\"1.0\"  encoding=\"windows-1251\"?>
<Товары>\n";
    foreach ( $product as $prodvalue )
    {
        foreach ( $prodvalue as $prok=>$valval )
        {
            if ( $prok == 'Товар' )
            {
                $xml .=
"  <Товар";
                foreach ( $valval as $key=>$value )
                {
                    $xml .= ' '. $key .'="'. $value .'"';
                }
                $xml .= ">\n";
            }
            if ( $prok == 'Цена' )
            {
                foreach ( $valval as $key=>$value )
                {
                    $xml .= 
"    <Цена Тип=\"". $key ."\">". $value ."</Цена>\n";
                }
            }
            if ( $prok == 'Свойства' )
            {
                $xml .=
"    <Свойства>\n";
                foreach ( $valval as $key=>$value )
                {
                    foreach ( $value as $ak=>$atr )
                    {
                        $xml .=
"      <". $key .(($atr)?" ЕдИзм=\"". $atr ."\"":'').">". $ak ."</". $key .">\n";
                    }
                }
                $xml .=
"    </Свойства>\n";
            }
            if ( $prok == 'Раздел' )
            {
                // здесь если разделы будут иметь неограниченную вложенность нужна рекурсия
                $xml .=
"    <Раздел>\n";
                foreach ( $valval as $key=>$value )
                {
                    $xml .=
"      <Раздел>". $value ."</Раздел>\n";
                }
                $xml .=
"    </Раздел>\n";
            }

        }
        $xml .=
"  </Товар>\n";
    }
    $xml .='</Товары>';
//    echo htmlspecialchars($xml);

//echo mb_internal_encoding();
$xml = mb_convert_encoding($xml, "cp1251", "UTF-8");

//$xml = iconv("UTF-8", "WINDOWS-1251", $xml);
return file_put_contents( $a, $xml );
}

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
$import = importXml( 'Product.xml' );
//$export = exportXml( 'exportXml.xml', 'Бумага' );
/*
try{
    echo '<h3>mySortForKey()</h3>';
    print_r( $arr = [['a'=>2,'d'=>4],['b'=>1,'d'=>3],['d'=>1,'r'=>1]] );
    print_r( $sort = mySortForKey( $arr, 'd' ) );
    echo '<hr />';
} catch ( Exception $e ) {
    echo $e->getMessage();
}

try{
    echo '<h3>mySortForKey2()</h3>';
    print_r( $arr = [['a'=>2,'d'=>4],['d'=>7,'e'=>3],['c'=>1,'d'=>1]] );
    print_r( $sort = mySortForKey2( $arr, 'd' ) );
    echo '<hr />';
} catch ( Exception $e ) {
    echo $e->getMessage();
}
*/

?>
</div></pre>
</body>
</html>
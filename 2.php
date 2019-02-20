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
 * 
 */


?>
<html>
<head>
<title>Блок №2</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
</head>
<body>
<pre><div class="container">
<?php
echo '<h3>convertString()</h3>'; echo convertString( 'abcdf abcdf abcdf', 'abcdf' );  echo '<hr />';
//echo '<h3>convertString2()</h3>'; echo convertString2( 'abcdf abcdf abcdf', 'abcdf', [ 1, 3 ] ); echo '<hr />';
?>
</div></pre>
</body>
</html>
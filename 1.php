<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
header('Content-Type: text/html; charset=utf-8');

/**
* функция создаёт массив простых чисел методом перебора делителя
* 
* @param int $a
* @param int $b
* 
* @return array
*/
function findSimple( int $a, int $b ):array
{
	if ( $b < $a ) return array();
	if ( $a == 2 ) $c[] = 2;       // 2 простое число по определению
	if ( $a == 1 ) $a = 3;         // исключаем единицу
	for ( $i=$a; $i<=$b; $i++ )
	{
		if ( $i%2 == 0 ) continue; // чётное число больше 2 не может быть простым, пропускаем
		$sq = (int)sqrt( $i );
		$d = 3;
		while ( $d <= $sq )
		{
			if ( $i % $d == 0 ) continue 2; // число не является простым, выход на следующую итерацию цикла for
			$d = $d + 2;
		}
		$c[] = $i;
	}
	return $c;
}

/**
* функция создаёт многомерный массив с ассоциативными ключами из массива $a
* без использования стандартных функций
*/
function createTrapeze( array $a, array $key = [ 'a', 'b', 'c' ] ):array
{
    $count = count($key);
    $b = $c = 0;
    foreach ( $a as $value ) {
        $arr[( $b % $count )? $c : ++$c][$key[$b++ % $count]] = $value;
    }
    return $arr;
}
// с использованием функций array_chunk и array_combine
function createTrapeze2( array $a, array $key = [ 'a', 'b', 'c' ] ):array
{
    $count = count($key);
    $arr = array_chunk( $a, $count );
    foreach ( $arr as $num => $value ) {
        $arr[$num] = array_combine( $key, $value );
    }

    return $arr;
}

/**
* функция расчёта площади трапеции на основании данных многомерного массива $a
*/
function squareTrapeze( array $a ):array
{
    foreach ( $a as $key => $value ) {
        if ( !is_array( $value ) && count( $value ) < 3 ) continue;
        $a[$key]['s'] = ( ( $value['a'] + $value['b'] ) / 2 ) * $value['c'];
    }
    return $a;
}

/**
* функция извлечения массива из массива $a с максимальной площадью меньшей или равной $b
*/
function getSizeForLimit( array $a, int $b ):array
{
    $arr = array('s'=> 0 );
    foreach ( $a as $key => $value ) {
        $arr = ( $value['s'] <= $b && $value['s'] > $arr['s'] ) ? $value : $arr;
    }
    return $arr;
}

/**
* функция выборки минимального числа из ассоциативного массива $a
*/
function getMin( array $a ):int
{
    $b = array_pop( $a );
    foreach ( $a as $value ) {
        $b = ( $b < $value ) ? $b : $value;
    }
    return $b;
}

function getMin2( array $a ):int
{
    sort( $a, SORT_NUMERIC );
    return array_shift( $a );
}


/**
* отображение таблицы трапеции на основании массива $a
*/
function printTrapeze( array $a )
{
	$table = '
<table border=1>
  <thead>
    <tr>
      <th>основание a</th>
      <th>основание b</th>
      <th>высота c</th>
      <th>площадь s</th>
    </tr>
  </thead>
  <tbody>';
	foreach( $a as $value )
	{
		if ($value['s'] - floor( $value['s'] ) <> 0 )
		{
			$bgcolor =' bgcolor="#ffcc00"';
			$table .= '<tr' . $bgcolor . '><td>' . implode('</td><td>', $value) . '</td></tr>';
		}
		else
		{
			$bgcolor = ( (int)$value['s'] % 2 == 0 ) ? '' : ' bgcolor="#ffcc00"';
			$table .= '<tr' . $bgcolor . '><td>' . implode('</td><td>', $value) . '</td></tr>';
		}
		
	}
    $table .= '
  </tbody>
</table>';
	echo $table;
}

/**
* абстрактный класс BaseMath
*/
abstract
class BaseMath
{
    protected $value;

    protected function exp1( int $a, int $b, int $c )
    {
        return $a * ( $b ^ $c );
    }

    protected function exp2( int $a, int $b, int $c )
    {
        if ( $b == 0 ) throw new Exception('Ошибка: деление на ноль невозможно');
        return  ( $a / $b ) ^ $c ;
    }

    public function getValue()
    {
        return $this->value;
    }
}

class F1 extends BaseMath
{
    public function __construct( int $a, int $b, int $c )
    {
        $this->value = ($this->exp1($a,$b,$c) + ( ( $this->exp2($a,$b,$c) % 3 ) ^ min( $a, $b, $c ) ) );
    }

}

?>
<html>
<head>
<title>Блок №1</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
</head>
<body>
<pre><div class="container">
<?php
echo '<h3>findSimple()</h3>'; print_r( findSimple ( 1, 8 ) ); echo '<hr />';
//echo '<h3>findSimple2()</h3>'; print_r( findSimple2( 1, 8 ) ); echo '<hr />';
echo '<h3>createTrapeze()</h3>'; $arr = [1,2,3,4,5,6]; print_r( createTrapeze ( $arr ) ); echo '<hr />';
//echo '<h3>createTrapeze2()</h3>'; $arr = [1,2,3,4,5,6]; print_r( createTrapeze2( $arr ) ); echo '<hr />';
echo '<h3>squareTrapeze()</h3>'; $arr = [1,2,3,4,5,6]; print_r( squareTrapeze( createTrapeze( $arr ) ) ); echo '<hr />';
echo '<h3>getSizeForLimit()</h3>';$arr = [4,5,6,1,2,3,7,8,9]; print_r( getSizeForLimit( squareTrapeze( createTrapeze( $arr ) ), 67 ) ); echo '<hr />';
echo '<h3>getMin()</h3>';$arr = [4,5,6,2,3,7,8,1,9]; echo getMin ( $arr ); echo '<hr />';
//echo '<h3>getMin2()</h3>';$arr = [4,5,6,2,3,7,8,1,9]; echo getMin2( $arr ); echo '<hr />';
echo '<h3>printTrapeze()</h3>';$arr = array_map(function(){return rand(1,9);} , array_fill(0, 30, 0)); printTrapeze( squareTrapeze( createTrapeze( $arr ) ) ); echo '<hr />';

echo '<h3>F1</h3>';
try {
    $object = new F1( 7, 5, 15 );
    echo $object->getValue();
} catch ( Exception $e ) {
    echo $e->getMessage();
    //echo $e->__toString();
}

?>
</div></pre>
</body>
</html>
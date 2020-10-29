<?php    

$word_search['file'] = 'PagueDirecto';
$word_search['clave'] = '945';
$word_search['codigo'] = 'desc';

if ($word_search['file'] == 'PagueDirecto') {
    $cnn = sybase_connect('cepimeteo_II', 'everistdm', 'chi08le!', 'utf_8', 'tdm');          
    $query = "SELECT id, clave, tipo, valor FROM DATOS_TABLA_PARAMETROS WHERE clave=".$word_search['clave']. " AND tipo =". $word_search['codigo'];
    $result = sybase_query($query,$cnn);
    while ($row = sybase_fetch_object($result)) {
        var_dump ($row);
    } 
    sybase_close($cnn);
}

?>
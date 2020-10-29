<?php
require_once('nusoap-0.9.5/lib/nusoap.php');
# constantes
define('HOST', 'http://servicio-tablas.cl/');
define('PYTHON_HOST', 'http://localhost:8085/');
define('NOT_FOUND_CLAVE', null);
define('NOT_FOUND_CODIGO', null);
define('NOT_FOUND_SERVICE', 'El servicio para conseguir las tablas esta caido.');
define('EMPTY_FIELD_MESSAGE', 'El campo no puede estar vacio.');
define('DELIMITER_1', ';');
define('DELIMITER_URL2', ';url2');

$server = new soap_server();

// configuracion del webservice
$server->configureWSDL('Server Web', 'urn:server');

$server->wsdl->schemaTargetNamespace = 'urn:server';

// Parametros de entrada
$server->wsdl->addComplexType(
      'in_data',
      'complexType',
      'struct',
      'all',
      '',
      array(
            'file'   => array('name' => 'file', 'type' => 'xsd:string'), # path del archivo
            'clave'   => array('name' => 'clave', 'type' => 'xsd:string'), # convenio clave o id
            'codigo'   => array('name' => 'codigo', 'type' => 'xsd:string') # el campo a traer despues del '='
      )
);
// Parametros de Salida
$server->wsdl->addComplexType(
      'out_data',
      'complexType',
      'struct',
      'all',
      '',
      array(
            'parametro'   => array('name' => 'parametro', 'type' => 'xsd:string')
      )
);

$server->register(
      'get_url_from_file',
      array('in_data' => 'tns:in_data'),
      array('return' => 'tns:out_data'),
      'urn:server',
      'urn:server#get_url_from_file',
      'rpc',
      'encoded',
      'La siguiente funcion recibe parametro de id y retorna la url'
);

function get_url_from_file($word_search)
{
      # solo cuando es PagueDirecto.parametros se ira a buscar a sybase antes de el resto
      if ($word_search['file'] == 'PagueDirecto.parametros') {
            $cnn = sybase_connect('cepimeteo_II', 'everistdm', 'chi08le!', 'utf_8', 'tdm');          
            $query = "SELECT id, clave, tipo, valor FROM tdm.dbo.DATOS_TABLA_PARAMETROS WHERE clave='".$word_search['clave']. "' AND tipo ='". $word_search['codigo']."'";
            $result = sybase_query($query,$cnn);
            while ($row = sybase_fetch_object($result)) {
                var_dump ($row);
            } 
            sybase_close($cnn);
            return array(
                  'parametro' => 'bci lo mah grande'
            );
      }

      // instancian las constantes
      $constants =  get_defined_constants(true);
      
      // validando que venga la clave
      if (empty($word_search['clave'])) {
            return array(
                  'parametro' => $constants['user']['EMPTY_FIELD_MESSAGE']
            );
      }
      
      // buscando el path de la ubicacion del archivo, a traves de un servicio, escrito en python
      $cliente = curl_init();
	curl_setopt($cliente, CURLOPT_URL, $constants['user']['PYTHON_HOST'].'path_tabla/');
      curl_setopt($cliente, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($cliente, CURLOPT_POST, TRUE);
      curl_setopt($cliente, CURLOPT_POSTFIELDS, "file=".$word_search['file']);
      $file_path = curl_exec($cliente);
      curl_close($cliente);
      if (!$file_path) {
            return array(
                  'parametro' => $constants['user']['NOT_FOUND_SERVICE']
            );
      } elseif ($file_path == 'Archivo de tabla no existe') {
            #TODO: mejorar la respuesta cuando no encuentra el path del archivo
            $cliente = curl_init();
            curl_setopt($cliente, CURLOPT_URL, $constants['user']['PYTHON_HOST'].'actualizar_tablas');
            curl_setopt($cliente, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($cliente, CURLOPT_TIMEOUT, 1); 
            curl_setopt($cliente, CURLOPT_FORBID_REUSE, true);
            curl_setopt($cliente, CURLOPT_CONNECTTIMEOUT, 1);
            curl_setopt($cliente, CURLOPT_DNS_CACHE_TIMEOUT, 10);
            curl_exec($cliente);
            curl_close($cliente);
            return array(
                  'parametro' => $constants['user']['NOT_FOUND_CLAVE']
            );
      }

      try {
            $file_content = file_get_contents($file_path);

            $find = strpos($file_content, strval($word_search['clave']));
            if ($find === false) {
                  // para que se refresque el arbol de las tablas en la bd
                  $cliente = curl_init();
                  curl_setopt($cliente, CURLOPT_URL, $constants['user']['PYTHON_HOST'].'actualizar_tablas');
                  curl_setopt($cliente, CURLOPT_RETURNTRANSFER, true);
                  curl_setopt($cliente, CURLOPT_TIMEOUT, 1); 
                  curl_setopt($cliente, CURLOPT_FORBID_REUSE, true);
                  curl_setopt($cliente, CURLOPT_CONNECTTIMEOUT, 1);
                  curl_setopt($cliente, CURLOPT_DNS_CACHE_TIMEOUT, 10);
                  curl_exec($cliente);
                  curl_close($cliente);
                  return array(
                        'parametro' => $constants['user']['NOT_FOUND_CLAVE']
                  );
                  
            } else {
                  return search_url($file_content, $word_search);
            }
      } catch (\Throwable $th) {
            var_dump($th);
      }
}

function search_url($texto_completo, $data)
{
      $texto_completo = nl2br($texto_completo);
      $palabra_buscada = $data['clave'].';';
      $codigo_buscado = $data['codigo'].'=';
      $largo_palabra_buscada = strlen($palabra_buscada);  
      $largo_codigo_buscado = strlen($codigo_buscado);
      $constants =  get_defined_constants(true);
      $palabra_buscada_position = strpos($texto_completo, strval($palabra_buscada)); 
      $punto_de_partida = $palabra_buscada_position + $largo_palabra_buscada;
      $posicion_br = strpos($texto_completo,"<br />", $punto_de_partida);
      $posicion_codigo = strpos($texto_completo, $codigo_buscado, $punto_de_partida);

      if (!$posicion_codigo || ($posicion_codigo > $posicion_br)) {
            return array(
                  'parametro' => $constants['user']['NOT_FOUND_CODIGO']
            );
      }
      $posicion_inicio_url = $posicion_codigo + $largo_codigo_buscado;
      $posicion_fin_url = strpos($texto_completo, $constants['user']['DELIMITER_1'], $posicion_inicio_url);
      $largo_url = $posicion_fin_url - $posicion_inicio_url;
      $url = substr($texto_completo, $posicion_inicio_url,$largo_url);
      return array(
            'parametro' => $url
      );
}

@$server->service(file_get_contents('php://input'));

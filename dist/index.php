<?php
 
/*list of usual status code*/
$statusCodes = array(
  400=>'Bad Request',
  401=>'Unauthorized',
  403=>'Forbiden',
  404=>'Not Found',
  405=>'Method Not Allowed',
  440=>'Login Timeout',
  500=>'Internal Server Error',
  501=>'Not Implemented',
);

function auth(){
  if(isset($_SERVER[' request']['auth'])){
    auth_check($_SERVER[' request']['auth']);
  }
}
function error($code, $custom=''){
  global $statusCodes;
  if(isset($statusCodes[$code])){
    $header = $statusCodes[$code];
  }else{
    $header = json_encode($custom);
  }
  
  if($custom){
    $text = $custom;
  }else{
    $text = $header;
  }
  
  
  header("HTTP/1.1 $code $header");
  exit(json_encode(array('error'=>$text)));
  
}
function getJson(){
  if(isset($_SERVER[' request']['upload']) and $_SERVER[' request']['upload']){
    return;
  }
  if($raw = file_get_contents('php://input')){
      $_POST = json_decode($raw, true);
    if(is_null($_POST)){
      error('400','bad payload');
    }
  }
}
function matchUrl(){
  global $routes;

  $url = array_map('urldecode',explode('/',substr(strchr($_SERVER['REQUEST_URI'].'?','?',true),1)));
  $urllength = count($url)-1;
  $match = false;

  foreach($routes as $route=>$methods){
    $route = explode('/',substr($route,1));
    $routelength = count($route)-1;
    
    if($routelength!=$urllength){
      continue;
    }

    //parche para '/'
    if($route==''){
      if($url==''){
        $match=true;
        break;
      }else{
        continue;
      }
    }
    
    $params = array();

    foreach($route as $index=>$value){
      //parche para '...//...'
      if($value==''){
        continue;
      }
      if($value[0] == ':'){
        $params[substr($value,1)] = $url[$index];
        continue;
      }
      if($value==$url[$index]){
        continue;
      }
      if($index!=$routelength){
        continue 2;
      }
      if($value != '*'){
        continue 2;
      }
    }
    $match = true;
    break;
  }
  
  if(!$match){
    error(404);
  }
    
  $method = $_SERVER['REQUEST_METHOD'];


  if(!isset($methods[$method])){
    if(isset($methods[' default '])){
      $methods=' default ';
    }else{
      error(405);
    }
  }
  
    
  $_SERVER[' request']=$methods[$method];
  $_SERVER[' url']=array_merge($url,$params);
  
}
function testParams($name){
  if(!isset($_SERVER[' request'][$name])){
    return;
  }
  global $$name;
  $array = &$$name;

  $random = (isset($_SERVER[' request'][$name]['*']) and $_SERVER[' request'][$name]['*'] ==true);
  unset($_SERVER[' request'][$name]['*']);

  foreach($array as $key=>$value){
    if(!$random and !isset($_SERVER[' request'][$name][$key])){
      error(400, "unexpected parameter $key");
    }
    unset($_SERVER[' request'][$name][$key]);
  }

  foreach($_SERVER[' request'][$name] as $key=>$value){
    if($value){
      error(400, "missing parameter $key");      
    }
    $array[$key] = null;
  }
}
function response($array){
  exit(json_encode($array));
}
function run(){
  if(isset($_SERVER[' request']['require'])){
    require './usr/'.$_SERVER[' request']['require'];
  }
  if(isset($_SERVER[' request']['function'])){
    call_user_func($_SERVER[' request']['function']);
  }
}
function upload($to, $filename=false){
  
  if(isset($_FILES[$filename])){
    move_uploaded_file($_FILES[$filename]['tmp_name'], $to);
    return true;
  }
  
  $inputHandler = fopen('php://input', "rb");
  $fileHandler = fopen($to, "ab+");
  while(true) {
    $buffer = fread($inputHandler, 4096);
    if($buffer===false){
      unlink($to);
      return false;
    }
    if (strlen($buffer) ==0) {
        fclose($inputHandler);
        fclose($fileHandler);
        return true;
    }
    fwrite($fileHandler, $buffer);
  }
}

function safeFileExists($path){
  /*
   * Comprueba si un archivo existe.
   * Devuelve false si el archivo NO existe o si la ruta trata salir de basepath
   * 
   * de otro modo devuelve la ruta empezando por ./
   * */

  $path = './'.$path;
  
  # no puede contener /../ ni \..\ ni mezclas
  if (
    strpos($path,'/../')!==false and
    strpos($path,'\\..\\')!==false and
    strpos($path,'/..\\')!==false and
    strpos($path,'\\../')!==false
    ){
    return false;
  }

  if(file_exists($path)){
    return $path;
  }
  return false;
}
function isSafePath($path){
  /**
   * Devuelve false si path es sospechoso de querer salir del directorio actual
   * en caso contrario devuelve la ruta con ./ delante
   **/
  $path = './'.$path;
  
  # no puede contener /../ ni \..\ ni mezclas
  if (
    strpos($path,'/../')!==false and
    strpos($path,'\\..\\')!==false and
    strpos($path,'/..\\')!==false and
    strpos($path,'\\../')!==false
    ){
    return false;
  }
  
  return $path;

}
function sendFile($path, $name=false){
  if(!file_exists($path)){
    error(500);
  }
  if(!$name){
    $name=substr(strstr($path,'/'),1);
  }
  header("X-Sendfile: $path");
  header("Content-Type: image"); #TODO detectar el tipo y mandar un encabezado apropiado
  exit();
}


require './routes/init.php';


matchUrl();

getJson();

auth();
$query = &$_GET;
testParams('query');
$json = &$_POST;
testParams('json');
run();

$folder = "./routes/routes/{$_SERVER[' url'][0]}/{$_SERVER['REQUEST_METHOD']}/";
include $folder."control.php";
include $folder."model.php";
include $folder."view.php";

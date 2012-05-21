<?php
function pr($x) {
	print('<pre>');
	print_r($x);
	print('</pre>');
}

function debug() {
	return isset($GLOBALS['config']['core']['debug'])
		? $GLOBALS['config']['core']['debug']
		: true;
}

function assocFallback($a, $k, $f) {
	return isset($a[$k])
		? $a[$k]
		: $f;
}

function error404() {
	header('HTTP/1.0 404 Not Found');
	if(!file_exists('../errors/404.php')) {
		die('404 File not found');
	}
	$page = $_SERVER['REQUEST_URI'];
	require_once('../errors/404.php');
	die();
}

function error500() {
	header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error', true, 500);
	if(!file_exists('../errors/500.php')) {
		die('500 Internal server error');
	}
	require_once('../errors/500.php');
	die();
}

function noRoute($url) {
	die("There is no route defined for the url: $url");
}

function noSuchView($viewFile) {
	die("There is no such view file: $viewFile");
}

function noSuchModel($modelFile) {
	die("There is no such model file: $modelFile");
}

function noSuchLib($libFile) {
	die("There is no such library file: $libFile");
}

function noSuchConf($confFile) {
	die("There is no such config file: $confFile");
}

function confError($v, $confFile) {
	die("Config Error: there is no variable $$v defined in $confFile");
}

function getConfig($conf) {
	if(!isset($GLOBALS['config'][$conf])) {
		$confFile = "../config/$conf.php";
		if(!file_exists($confFile)) {
			if(debug()) { noSuchConf($confFile); }
			else { error500(); }
		}
		require_once("../config/$conf.php");
		$pieces = explode('/', $conf);
		$confName = array_pop($pieces);
		if(!isset(${$confName})) {
			if(debug()) { confError($confName, $confFile); }
			else { error500(); }
		}
		$GLOBALS['config'][$conf] = ${$confName};
	}
	return $GLOBALS['config'][$conf];
}

function getConfigVar($conf, $k, $fallback = null) {
	$a = getConfig($conf);
	return assocFallback($a, $k, $fallback);
}

function useLib($lib) {
	if(!isset($GLOBALS['lib'][$lib])) {
		$libFile = "../lib/$lib.php";
		if(!file_exists($libFile)) {
			if(debug()) { noSuchLib($libFile); }
			else { error500(); }
		}
		require_once("../lib/$lib.php");
		$GLOBALS['lib'][$lib] = true;
	}
}

function useModel($model) {
	if(!isset($GLOBALS['model'][$model])) {
		$modelFile = "../models/$model.php";
		if(!file_exists($modelFile)) {
			if(debug()) { noSuchModel($modelFile); }
			else { error500(); }
		}
		require_once($modelFile);
		$GLOBALS['model'][$model] = true;
	}
}

function redirect($url) { header("Location: $url"); }
function isPost() { return $_SERVER['REQUEST_METHOD'] === 'POST'; }
function isGet() { return $_SERVER['REQUEST_METHOD'] === 'GET'; }
function postVar($k, $f = null) { return assocFallback($_POST, $k, $f); }
function getVar($k, $f = null) { return assocFallback($_GET, $k, $f); }
function filesVar($k, $f = array()) { return assocFallback($_FILES, $k, $f); }
function sessionVar($k, $f = null) { return assocFallback($_SESSION, $k, $f); }
function url() { return getVar('url', ''); }

function method() {
	if(isGet()) {
		return 'get';
	}
	else if(isPost()) {
		return 'post';
	}
	else {
		if(debug()) { die('un-handled http request method'); }
		else { error500(); }
	}
}

function checkUploadedFile($file) {
	switch($file['error']) {
		case UPLOAD_ERR_OK:
			//Value: 0; There is no error, the file uploaded with success.
			return array(true, $file['error'], null);
		case UPLOAD_ERR_INI_SIZE:
			//Value: 1; The uploaded file exceeds the upload_max_filesize
			//directive in php.ini.
			return array(
				false,
				$file['error'],
				'The uploaded file exceeds the upload_max_filesize directive'.
					' in php.ini.'
			);
		case UPLOAD_ERR_FORM_SIZE:
			//Value: 2; The uploaded file exceeds the MAX_FILE_SIZE
			//directive that was specified in the HTML form.
			return array(
				false,
				$file['error'],
				'The uploaded file exceeds the MAX_FILE_SIZE directive that'.
					' was specified in the HTML form.'
			);
		case UPLOAD_ERR_PARTIAL:
			//Value: 3; The uploaded file was only partially uploaded.
			return array(
				false,
				$file['error'],
				'The uploaded file was only partially uploaded.'
			);
		case UPLOAD_ERR_NO_FILE:
			//Value: 4; No file was uploaded.
			return array(
				false,
				$file['error'],
				'No file was uploaded.'
			);
		case UPLOAD_ERR_NO_TMP_DIR:
			//Value: 6; Missing a temporary folder.
			return array(
				false,
				$file['error'],
				'Missing a temporary folder.'
			);
		case UPLOAD_ERR_CANT_WRITE:
			//Value: 7; Failed to write file to disk.
			return array(
				false,
				$file['error'],
				'Failed to write file to disk.'
			);
		case UPLOAD_ERR_EXTENSION:
			//Value: 8; A PHP extension stopped the file upload.
			return array(
				false,
				$file['error'],
				'A PHP extension stopped the file upload.'
			);
	}
}

function slug($phrase, $maxLength = 50) {
	$result = strtolower($phrase);
	$result = preg_replace("/[^a-z0-9\s-]/", "", $result);
	$result = trim(preg_replace("/[\s-]+/", " ", $result));
	$result = trim(substr($result, 0, $maxLength));
	$result = preg_replace("/\s/", "-", $result);
	return $result;
}

function ls($dir, $recursive = false, $extension = null, $prepend = '') {
	if($extension !== null) {
		$extension = strtolower($extension);
	}
	$contents = array();
	if($handle = opendir($dir)) {
		while(false !== ($entry = readdir($handle))) {
			if ($entry != "." && $entry != "..") {

				if($recursive && is_dir($dir . '/' . $entry)) {
					$recursedContents = ls($dir . '/' . $entry, true, $extension, $prepend . $entry . '/');
					$contents = array_merge($contents, $recursedContents);
				}
				else {

					if($extension !== null) {
						$pathinfo = pathinfo($dir . '/' . $entry);
						if(isset($pathinfo['extension']) && strtolower($pathinfo['extension']) == $extension) {
							$contents[] = $prepend . $entry;
						}
					}
					else {
						$contents[] = $prepend . $entry;
					}

				}
			}
		}
		closedir($handle);
	}
	return $contents;
}

function registerRoutes() {
	$controllers = ls('../controllers', true, 'php');
	foreach($controllers as $controller) {
		require_once("../controllers/$controller");
	}
}

function register($method, $route, $f) {
	$regexify = function($route) {
		$pieces = explode('/', $route);
		foreach($pieces as &$piece) {
			if(substr($piece, 0, 1) === ':') {
				$piece = '([-_A-Za-z0-9]+)';
			}
			if($piece === '*') {
				$piece = '(.+?)';
			}
		}
		return '#^' . implode('/', $pieces) . '/?$#';
	};

	if(substr($route, 0, 1) == '/') {
		$route = ($route === '/')
			? ''
			: substr($route, 1);
	}
	$regex = $regexify($route);
	$GLOBALS['routes'][$method][$regex] = $f;
}

function get($route, $f) {
	register('get', $route, $f);
};

function post($route, $f) {
	register('post', $route, $f);
};

function json($a) {
    header('Content-Type:application/json');
	return json_encode($a);
}

function dispatch($url) {
	$routes = $GLOBALS['routes'];
	foreach($routes[method()] as $pattern => $handler) {
		if(preg_match($pattern, $url, $matches) === 1) {
			array_shift($matches);
			$params = $matches;
			echo call_user_func_array($handler, $params);
			pr($GLOBALS);
			die();
		}
	}
}
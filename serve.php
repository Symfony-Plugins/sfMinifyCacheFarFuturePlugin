<?php
$configFilePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'minify_cahce_far_future.ini';

// Please do not edit after this line

function returnNotOk($statusCode, $errorMessage) {
    $statusMessages = array(
        304 => 'Not Modified',
        404 => 'Not Found',
        500 => 'Internal Server Error',
    );
    
    header('HTTP/1.1 ' . $statusCode . ' ' . $statusMessages[$statusCode]);
    if (!empty($errorMessage)) {
        echo $errorMessage;
    }
    exit;
}

function returnNotFoundUnless($condition, $message) {
    if ($condition === false) {
        header('HTTP/1.1 404 Not Found');
        echo $message;
        exit;
    }
}

function returnInternalServerErrorUnless($condition, $message) {
    if ($condition === false) {
        header('HTTP/1.1 500 Internal Server Error');
        echo $message;
        exit;
    }
}

function evalueateConfig(&$config) {
    foreach ($config as $sectionName => $section) {
        foreach ($section as $variable => $value) {
            if (strpos($value, '<?php') === 0) {
                $value = str_replace('<?php', '', $value);
                $value = str_replace('?>', '', $value);
                $config[$sectionName][$variable] = eval($value);
            } elseif ($value == 'true' || $value == 'on') {
                $config[$sectionName][$variable] = true;
            } elseif ($value == 'false' || $value == 'off') {
                $config[$sectionName][$variable] = false;
            }
        }
    }
}

function normalizePath($path) {
    if ((strpos($path, ':') === false) && (strpos($path, '/') !== 0)) {
        if (DIRECTORY_SEPARATOR == '\\') {
            $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
        } else {
            $path = str_replace('\\', DIRECTORY_SEPARATOR, $path);
        }
        return realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . $path);
    }
    
    return $path;
}

// Parse ini configuration
$configFilePath = realpath($configFilePath);
returnNotFoundUnless($configFilePath, 'Config file not found.');
$config = parse_ini_file($configFilePath, true);
evalueateConfig($config);
returnInternalServerErrorUnless($config, 'Config file in corrupt.');

// Process request
$thisFileBaseName = basename(__FILE__);
$pathInfoPosition = strpos($_SERVER['PHP_SELF'], $thisFileBaseName);
returnInternalServerErrorUnless($pathInfoPosition, 'Not ' . $thisFileBaseName . ' was called.');
$pathInfo = substr($_SERVER['PHP_SELF'], $pathInfoPosition + strlen($thisFileBaseName) + 1);

// Normalize direcories
$cacheDirPath = normalizePath($config['script_mode']['cache_dir']);
returnNotFoundUnless($cacheDirPath, 'Cache directory not found.');


$libDirPath = normalizePath($config['script_mode']['lib_dir']);
returnNotFoundUnless($libDirPath, 'Lib directory not found.');


set_include_path(get_include_path() . PATH_SEPARATOR . $libDirPath);

require_once 'sfMinifyCacheFarFutureURLParser.class.php';
$parser = new sfMinifyCacheFarFutureURLParser();
$parameters = array();
$parser->parse($parameters, $pathInfo);

require_once 'sfMinifyCacheFarFutureServe.class.php';
$server = new sfMinifyCacheFarFutureServe();

$encoder = null;
if ($config['encoding']['enabled']) {
    require_once 'sfMinifyCacheFarFutureEncoder.class.php';
    $encoder = new sfMinifyCacheFarFutureEncoder(
                       $_SERVER, 
                       $config['encoding']['preference_order'], 
                       $config['encoding']['deflate_level'], 
                       $config['encoding']['gzip_level']
                   );
}

$statusCode = 200;
$headers = array();
$errorMessage = '';

if (isset($parameters['cache'])) {
    $serveFilePath = $server->serveCached(
        $parameters['sf_format'],
        $cacheDirPath,
        $parameters['cache'],
        $encoder,
        $_SERVER,
        $headers,
        $statusCode,
        $errorMessage
    );
} else {
    // Normalize more direcories
    if (isset($config['script_mode']['web_dir']) && !empty($config['script_mode']['web_dir'])) {
        $webDirPath = normalizePath($config['script_mode']['web_dir']);
    } else {
        $webDirPath = dirname(__FILE__);
    }

    $type = $parameters['sf_format'];

    $compressor = null;
    if ($type == 'js' || $type == 'css') {
        // Include classes
        require_once 'sfMinifyCacheFarFutureCacheNameGenerator.class.php';
        require_once 'sfMinifyCacheFarFutureCompressor.class.php';
        require_once 'sfMinifyCacheFarFutureCache.class.php';
        
        if ($type == 'js') {
            require_once $config['javascript']['compressor'] . '.class.php';
            $level = $config['javascript']['level'];
            $compressor = new $config['javascript']['compressor']($level);
        } else {
            require_once $config['stylesheet']['compressor'] . '.class.php';
            $level = $config['stylesheet']['level'];
            $compressor = new $config['stylesheet']['compressor']($level);
        }
    }
    
    $serveFilePath = $server->serveNotCached(
        $type, 
        $webDirPath, 
        $parameters['path'] . DIRECTORY_SEPARATOR . $parameters['file'], 
        $compressor,
        $encoder,
        $cacheDirPath,
        $config['general']['modification_check'],
        $config['general']['force_reload_id'],
        $_SERVER,
        $headers,
        $statusCode,
        $errorMessage);
}

if ($statusCode != 200) {
    returnNotOk($statusCode, $errorMessage);
}

foreach ($headers as $header => $value) {
    header($header . ': ' . $value);
}

readfile($serveFilePath);

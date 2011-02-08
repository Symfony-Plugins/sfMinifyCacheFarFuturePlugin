<?php

class sfMinifyCacheFarFutureCache {
    private $compressor;
    private $cacheDirPath;
    
    public function __construct($compressor, $cacheDirPath, $modificationCheck, $reloadId) {
        $this->compressor = $compressor;
        $this->cacheDirPath = $cacheDirPath;
        $this->cacheFileNameGenerator = new sfMinifyCacheFarFutureCacheNameGenerator($modificationCheck, $reloadId);
    }
    
    public function cache($type, $filePaths) {
        if (!is_array($filePaths)) {
            $filePaths = array($filePaths);
        }
        
        $cacheDir = $this->cacheDirPath . DIRECTORY_SEPARATOR . 'minify_cache_far_future';
        
        if (!is_dir($cacheDir)) {
            $current_umask = umask(0000);
            @mkdir($cacheDir, 0777);
            umask($current_umask);
        }

        $cacheFileName = $this->cacheFileNameGenerator->getCacheFileName($filePaths, $this->compressor->getUniqueCode(), $type);
        $cacheFilePath = $cacheDir . DIRECTORY_SEPARATOR . $cacheFileName;
        
        if (!is_readable($cacheFilePath)) {
            if ($type == 'js') {
                $this->compressor->compressJavascriptFiles($cacheFilePath, $filePaths);
            } else {
                $this->compressor->compressStylesheetFiles($cacheFilePath, $filePaths);
            }
        }
        
        return $cacheFilePath;
    }
}
<?php

class sfMinifyCacheFarFutureCacheNameGenerator {
    const CHECK_CONTENT = 'content';
    const CHECK_DATE = 'date';

    const VERSION = '2';
    
    private $modificationCheck;
    private $reloadId;
    
    public function __construct($modificationCheck = self::CHECK_CONTENT, $reloadId = '') {
        $this->modificationCheck = $modificationCheck;
        $this->reloadId = ($reloadId == '') ? '' : '_r' . $reloadId;
    }

    public function getCacheFileName(array $filePaths, $compressorCode, $fileExtension) {
        $id = '';
        switch ($this->modificationCheck) {
            case self::CHECK_CONTENT:
                $id = 'h' . $this->hashFileContents($filePaths);
                break;
            case self::CHECK_DATE:
                $id = 'd' . md5($this->newestFileDate($filePaths) . ' ' . implode(', ', $filePaths));
                break;
            default:
                throw new Exception("Unknonw check method.");
        }
        
        $cacheFileName = $id . '_v' . self::VERSION . $this->reloadId . '_c' . $compressorCode . '.' . $fileExtension;
        
        return $cacheFileName;
    }
    
    private function hashFileContents(array $filePaths) {
        $hash = '';
        foreach ($filePaths as $filePath) {
            $hash .= md5_file($filePath);
        }
        return md5($hash);
    }
    
    private function newestFileDate(array $filePaths) {
        $newest = null;
        foreach ($filePaths as $filePath) {
            $current = filemtime($filePath);
            if (is_null($newest) || ($current > $newest)) {
                $newest = $current;
            }
        }
        
        return date('Ymd_His', $newest);
    }
    
}
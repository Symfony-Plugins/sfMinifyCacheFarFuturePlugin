<?php

class sfMinifyCacheFarFutureCompressorStore implements sfMinifyCacheFarFutureCompressor {
    private $level;
    public function __construct($level) {
        $this->level = $level;
    }

    public function getUniqueCode() {
        return 's';
    }
    
    private function compressFiles($targetPath, array $filePaths) {
        $currentUmask = umask(0000);
        $cacheFile = fopen($targetPath, "w+");
        foreach ($filePaths as $filePath) {
            fwrite($cacheFile, file_get_contents($filePath));
            fwrite($cacheFile, "\n");
        }
        
        fclose($cacheFile);
        umask($currentUmask);
        
        return true;
    }

    public function compressJavascriptFiles($targetPath, $filePaths) {
        if (is_string($filePaths)) {
            $filePaths = array($filePaths);
        }
        
        return $this->compressFiles($targetPath, $filePaths);
    }
    
    public function compressStylesheetFiles($targetPath, $filePaths) {
        if (is_string($filePaths)) {
            $filePaths = array($filePaths);
        }
        
        return $this->compressFiles($targetPath, $filePaths);
    }
    
}
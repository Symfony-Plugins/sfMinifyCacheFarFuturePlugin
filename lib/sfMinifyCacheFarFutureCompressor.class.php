<?php

interface sfMinifyCacheFarFutureCompressor {
    public function getUniqueCode();
    
    public function compressJavascriptFiles($targetPath, $filePaths);
    
    public function compressStylesheetFiles($targetPath, $filePaths);
    
}
<?php

class sfMinifyCacheFarFutureGrouper {
    
    public function __construct() {
    }
    
    private function computeLocalPath($fileRef, $dir, $fileExtension) {
        if (0 === strpos($fileRef, './')) {
            $fileRef = substr($fileRef, 2);
        }
        
        if (0 !== strpos($fileRef, '/')) {
            $filePath = sfConfig::get('sf_web_dir') . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $fileRef; 
        } else {
            $filePath = sfConfig::get('sf_web_dir') . $fileRef; 
        }
        
        $fileExtension = '.' . $fileExtension;
        if ($fileExtension !== substr($filePath, strlen($filePath) - strlen($fileExtension))) {
            $filePath .= $fileExtension;
        }
        
        return realpath($filePath);
    }

    private function getPublicPath($fileRef, $dir) {
        if (0 === strpos($fileRef, './')) {
            $fileRef = substr($fileRef, 2);
        }
        
        if (0 !== strpos($fileRef, '/')) {
            $fileRef = '/' . $dir . '/' . $fileRef; 
        }
        
        return $fileRef;
    }
    
    private function isOptionDifferent($key, $file1, $file2) {
        if (isset($file1['options'][$key])) {
            if (!isset($file2['options'][$key]) || $file1['options'][$key] != $file2['options'][$key]) {
                return true;
            }
        } elseif (isset($file2['options'][$key])) {
            if (!isset($file1['options'][$key]) || $file1['options'][$key] != $file2['options'][$key]) {
                return true;
            }
        }
        return false;
    }
    
    private function isInSameJavasriptGroup($file1, $file2) {
        if ($file1['pass'] || $file2['pass']) {
            return false;
        }

        if ($file1['separate'] || $file2['separate']) {
            return false;
        }
        
        if (dirname($file1['filePath']) != dirname($file2['filePath'])) {
            return false;
        }
        
        if ($this->isOptionDifferent('condition', $file1, $file2)) {
            return false;
        }
        
        return true;
    }

    
    private function isInSameStylesheetGroup($file1, $file2) {
        if ($file1['pass'] || $file2['pass']) {
            return false;
        }

        if ($file1['separate'] || $file2['separate']) {
            return false;
        }
        
        if (dirname($file1['filePath']) != dirname($file2['filePath'])) {
            return false;
        }
        
        if ($this->isOptionDifferent('condition', $file1, $file2)) {
            return false;
        }

        if ($this->isOptionDifferent('media', $file1, $file2)) {
            return false;
        }
        
        return true;
    }
    
    
    private function isInSameGroup($type, $file1, $file2) {
        if ($type === 'js') {
            return $this->isInSameJavasriptGroup($file1, $file2);
        } else {
            return $this->isInSameStylesheetGroup($file1, $file2);
        }
    }
    
    private function getGroupedFiles($type, array $files) {
        $groupedFiles = array();
        
        $groupIndex = -1;
        
        $filePathCount = array();
        $filePaths = array();
        foreach ($files as $fileRef => $options) {
            if (isset($filePaths[$fileRef])) {
                $filePath = $filePaths[$fileRef];
            } else {
                $filePath = $this->computeLocalPath($fileRef, $type, $type);
                $filePaths[$fileRef] = $filePath;
            }
            
            if (isset($filePathCount[$filePath])) {
                $filePathCount[$filePath]++;
            } else {
                $filePathCount[$filePath] = 1;
            }
        }
        
        foreach ($files as $fileRef => $options) {
            $filePath = $filePaths[$fileRef];
            if ($filePath === false) {
                $file = array('pass' => true, 
                              'separate' => true, 
                              'local' => false, 
                              'fileRef' => $fileRef, 
                              'options' => $options);
            } else {
                $publicPath = $this->getPublicPath($fileRef, $type);
                $publicPathPrefix = dirname($publicPath);
                
                $file = array('pass' => false, 
                              'separate' => ($filePathCount[$filePath] > 1), 
                              'local' => true, 
                              'publicPathPrefix' => $publicPathPrefix, 
                              'filePath' => $filePath, 
                              'options' => $options);
            }
            
            if (isset($file['options']['raw_name'])) {
                $file['pass'] = true;
            }
            
            if ($groupIndex === -1 || !$this->isInSameGroup($type, $groupedFiles[$groupIndex][$itemIndex], $file)) {
                $groupIndex++;
                $itemIndex = 0;
                $groupedFiles[$groupIndex] = array();
            } else {
                $itemIndex++;
            }
            
            $groupedFiles[$groupIndex][$itemIndex] = $file;
        }
        
        return $groupedFiles;
    }
    
    
    
    
    public function getGroupedJavascripts(array $useJavascripts) {
        return $this->getGroupedFiles('js', $useJavascripts);
    }
    
    public function getGroupedStylesheets(array $useStylesheets) {
        return $this->getGroupedFiles('css', $useStylesheets);
    }
    
}
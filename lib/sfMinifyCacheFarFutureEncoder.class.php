<?php

class sfMinifyCacheFarFutureEncoder {
    const ENCODING_DEFLATE = 'deflate';
    const ENCODING_GZIP = 'gzip';
    
    private $encoding = null;
    private $level;
    
    public function __construct($pathInfoArray, $preferenceOrder, $deflateLevel, $gzipLevel) {
        if (isset($pathInfoArray['HTTP_ACCEPT_ENCODING'])) {
            $acceptedDeflate = (strpos($pathInfoArray['HTTP_ACCEPT_ENCODING'], self::ENCODING_DEFLATE) !== false);
            $acceptedGzip = (strpos($pathInfoArray['HTTP_ACCEPT_ENCODING'], self::ENCODING_GZIP) !== false);

            $acceptedDeflate = $acceptedDeflate && function_exists('gzdeflate');
            $acceptedGzip = $acceptedGzip && function_exists('gzencode');
            
            if ($acceptedGzip && $acceptedDeflate) {
                $deflatePreferenceOrder = strpos($preferenceOrder, self::ENCODING_DEFLATE);
                $gzipPreferenceOrder = strpos($preferenceOrder, self::ENCODING_GZIP);
                
                if ($gzipPreferenceOrder === false) {
                    $this->encoding = self::ENCODING_DEFLATE;
                    $this->level = $deflateLevel;
                } elseif ($deflatePreferenceOrder === false) {
                    $this->encoding = self::ENCODING_GZIP;
                    $this->level = $gzipLevel;
                } else {
                    if ($deflatePreferenceOrder < $gzipPreferenceOrder) {
                        $this->encoding = self::ENCODING_DEFLATE;
                        $this->level = $deflateLevel;
                    } else {
                        $this->encoding = self::ENCODING_GZIP;
                        $this->level = $gzipLevel;
                    }
                }
            } elseif ($acceptedGzip) {
                $this->encoding = self::ENCODING_GZIP;
                $this->level = $gzipLevel;
            } elseif ($acceptedDeflate) {
                $this->encoding = self::ENCODING_DEFLATE;
                $this->level = $deflateLevel;
            }
        }
    }

    public function getEncoding() {
        return $this->encoding;    
    }
    
    public function isPossible() {
        return !is_null($this->encoding); 
    }
    
    public function encode($filePath) {
        switch ($this->encoding) {
            case self::ENCODING_DEFLATE: 
                $encodedFilePath = $filePath . '.l' . $this->level . '.deflate';
                if (!is_readable($encodedFilePath)) {
                    file_put_contents($encodedFilePath, gzdeflate(file_get_contents($filePath), $this->level));
                }
                return $encodedFilePath;
            case self::ENCODING_GZIP: 
                $encodedFilePath = $filePath . '.l' . $this->level . '.gz';
                if (!is_readable($encodedFilePath)) {
                    file_put_contents($encodedFilePath, gzencode(file_get_contents($filePath), $this->level));
                }
                
                return $encodedFilePath;
            default:
                throw new Exception("Unknown encoding: " . $this->encoding);  
        } 
    }
}
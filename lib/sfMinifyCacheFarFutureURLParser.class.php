<?php

class sfMinifyCacheFarFutureURLParser {
    public function parse(&$parameters, $pathInfo) {
        $lastSeparatorPosition = strrpos($pathInfo, '/');
        if ($lastSeparatorPosition === false) {
            $path = '';
            $files = explode(',', $pathInfo);
            $firstFile = array_shift($files);
        } else {
            $path = substr($pathInfo, 0, $lastSeparatorPosition);
            $files = explode(',', substr($pathInfo, $lastSeparatorPosition + 1));
            $firstFile = array_shift($files);
        }

        if ('/' !== DIRECTORY_SEPARATOR) {
            $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
        }
        
        $parameters['path'] = $path;
        if (count($files) > 0) {
            $parameters['cache'] = $firstFile;
            $parameters['files'] = $files;
        } else {
            $parameters['file'] = $firstFile;
        }

        if (strrpos($firstFile, '.') !== false) {
            $parameters['sf_format'] = substr($firstFile, strrpos($firstFile, '.') + 1);
        }
    }
}
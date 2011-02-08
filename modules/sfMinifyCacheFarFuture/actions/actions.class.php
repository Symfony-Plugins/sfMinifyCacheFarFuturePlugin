<?php
/**
 * Serves cached files forever.
 *
 * @package sfMinifyCacheFarFuturePlugin
 * @author  Balazs Matyas
 */

class sfMinifyCacheFarFutureActions extends sfActions {
    
    public function executeServe(sfWebRequest $request) {
        $this->forward404Unless($request->hasParameter('path'), "Missing path parameter");
        $this->forward404Unless($request->getRequestFormat(), "Missing format parameter");
        
        if (!($request->hasParameter('cache') || ($request->hasParameter('path') && $request->hasParameter('file')))) {
            $this->forward404Unless($fileParameter, "Missing file parameter");
        }

        $server = new sfMinifyCacheFarFutureServe();

        $config = sfMinifyCacheFarFutureFilter::getConfig();
            
        $encoder = null;
        if ($config['encoding']['enabled']) {
            $encoder = new sfMinifyCacheFarFutureEncoder(
                               $_SERVER, 
                               $config['encoding']['preference_order'], 
                               $config['encoding']['deflate_level'], 
                               $config['encoding']['gzip_level']
                           );
        }
        
        $headers = array();
        $statusCode = 200;
        $errorMessage = '';
        
        if ($request->hasParameter('cache')) {
            $serveFilePath = $server->serveCached(
                $request->getRequestFormat(),
                sfConfig::get('sf_cache_dir'),
                $request->getParameter('cache'),
                $encoder,
                $request->getPathInfoArray(),
                $headers,
                $statusCode,
                $errorMessage
            );
        } else {
            $type = $request->getRequestFormat();
            
            $compressor = null;
            if ($type == 'js') {
                $level = $config['javascript']['level'];
                $compressor = new $config['javascript']['compressor']($level);
            } elseif ($type == 'css') {
                $level = $config['stylesheet']['level'];
                $compressor = new $config['stylesheet']['compressor']($level);
            }
            
            $serveFilePath = $server->serveNotCached(
                $type, 
                sfConfig::get('sf_web_dir'), 
                $request->getParameter('path') . DIRECTORY_SEPARATOR . $request->getParameter('file'), 
                $compressor,
                $encoder,
                sfConfig::get('sf_cache_dir'),
                $config['general']['modification_check'],
                $config['general']['force_reload_id'],
                $request->getPathInfoArray(),
                $headers,
                $statusCode,
                $errorMessage);
        }
        
        $response = sfContext::getInstance()->getResponse();
        if ($statusCode != 200) {
            $response->setStatusCode($statusCode);
            echo $errorMessage;
            return sfView::HEADER_ONLY;
        }
        
        foreach ($headers as $header => $value) {
            $response->setHttpHeader($header, $value);
        }
        $response->sendHttpHeaders();
        
        readfile($serveFilePath);
        
        return sfView::HEADER_ONLY;
    }
}
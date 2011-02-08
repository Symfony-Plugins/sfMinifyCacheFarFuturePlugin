<?php

/**
 * ?.
 *
 * @package    symfony
 * @subpackage sfMinifyCacheFarFuturePlugin
 * @author     Balazs Matyas
 */
class sfMinifyCacheFarFutureFilter extends sfFilter {
    private static $config;
    
    private function cacheFiles($type, $group) {
        $filePaths = array();
        $fileNames = array();
        foreach ($group as $file) {
            $filePaths[] = $file['filePath'];
            $fileNames[] = basename($file['filePath']);
        }

        $config = self::$config;
        
        $compressor = null;
        if ($type == 'js') {
            $level = $config['javascript']['level'];
            $compressor = new $config['javascript']['compressor']($level);
        } elseif ($type == 'css') {
            $level = $config['stylesheet']['level'];
            $compressor = new $config['stylesheet']['compressor']($level);
        }

        $cache = new sfMinifyCacheFarFutureCache(
                         $compressor, 
                         sfConfig::get('sf_cache_dir'), 
                         $config['general']['modification_check'],
                         $config['general']['force_reload_id']
                     );
        $cacheFilePath = $cache->cache($type, $filePaths);
        $cacheFileName = basename($cacheFilePath);
        
        $relativeCacheFilePath = $group[0]['publicPathPrefix'] . '/' . $cacheFileName . ',' . implode(',', $fileNames);
        
        return $relativeCacheFilePath;
    }

    private static function evalueateConfig(&$config) {
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
    
    public static function getConfig() {
        if (!isset(self::$config)) {
            self::$config = parse_ini_file(sfConfig::get('sf_config_dir') . DIRECTORY_SEPARATOR . 'minify_cahce_far_future.ini', true);
            self::evalueateConfig(self::$config);
        }
        return self::$config;
    }
    
    public function getParameter($name, $default = null) {
        $environmentParameters = parent::getParameter('environment');
        $environment = sfConfig::get('sf_environment');
        
        if (isset($environmentParameters[$environment][$name])) {
            return $environmentParameters[$environment][$name];
        }
        
        return parent::getParameter($name, $default);
    }

    private function getServeUrlBase() {
        if (self::$config['general']['mode'] == 'module') {
            $serveUrlBase = $this->getContext()->getController()->genUrl('@minify_cache_far_future');
        } else {
            $serveUrlBase = $this->getContext()->getController()->genUrl('/') . self::$config['script_mode']['file'];
        }
        return $serveUrlBase;
    }
    
    /**
     * Executes this filter.
     *
     * @param sfFilterChain A sfFilterChain instance
     */
    public function execute($filterChain) {
        // execute next filter
        $filterChain->execute();

        if ($this->getParameter('enabled', true)) {
            
            $config = self::getConfig();
            
            $response = $this->getContext()->getResponse();
    
            if ($this->getParameter('javascript_enabled', true)) {
                $useJavascripts = $response->getJavascripts();
                if (count($useJavascripts) > 0) {
                    foreach ($useJavascripts as $fileRef => $options) {
                        $response->removeJavascript($fileRef);
                    }
                
                    $grouper = new sfMinifyCacheFarFutureGrouper();
                    $groups = $grouper->getGroupedJavascripts($useJavascripts);
                    foreach ($groups as $group) {
                        if ($group[0]['pass'] === true) {
                            $response->addJavascript($group[0]['fileRef'], '', $group[0]['options']);
                        } else {
                            $options = array();
                            if (isset($group[0]['options']['condition'])) {
                                $options['condition'] = $group[0]['options']['condition'];
                            }
                            
                            $relativeCacheFilePath = $this->cacheFiles('js', $group);
                            $url = $this->getServeUrlBase() . $relativeCacheFilePath;
                            $response->addJavascript($url, '', $options);
                        }
                    }
                }
            }
            
            if ($this->getParameter('stylesheet_enabled', true)) {
                $useStylesheets = $response->getStylesheets();
                if (count($useStylesheets) > 0) {
                    foreach ($useStylesheets as $fileRef => $options) {
                        $response->removeStylesheet($fileRef);
                    }
                
                    $grouper = new sfMinifyCacheFarFutureGrouper();
                    $groups = $grouper->getGroupedStylesheets($useStylesheets);
                    foreach ($groups as $group) {
                        if ($group[0]['pass'] === true) {
                            $response->addStylesheet($group[0]['fileRef'], '', $group[0]['options']);
                        } else {
                            $options = array();
                            if (isset($group[0]['options']['condition'])) {
                                $options['condition'] = $group[0]['options']['condition'];
                            }
        
                            if (isset($group[0]['options']['media'])) {
                                $options['media'] = $group[0]['options']['media'];
                            }
                            
                            $relativeCacheFilePath = $this->cacheFiles('css', $group);
                            $url = $this->getServeUrlBase() . $relativeCacheFilePath;
                            $response->addStylesheet($url, '', $options);
                        }
                    }
                }
            }
        }
    }
}
  
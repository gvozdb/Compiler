<?php

class Compiler
{
    /** @var modX $modx */
    public $modx;

    /**
     * @param modX $modx
     * @param array $config
     */
    function __construct(modX &$modx, array $config = array())
    {
        $this->modx =& $modx;

        $corePath = $this->modx->getOption('core_path').'components/compiler/';
        $assetsPath = $this->modx->getOption('assets_path').'components/compiler/';

        $this->config = array_merge(array(
            'basePath' => MODX_BASE_PATH,
            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            
            'scssDirFrom' => $this->cleanUrl('/' . $this->modx->getOption('compiler_scss_dir_from') . '/'),
            'scssDirTo' => $this->cleanUrl('/' . $this->modx->getOption('compiler_scss_dir_to') . '/'),
            'scssSkipUnderscore' => $this->modx->getOption('compiler_scss_skip_underscore', null, true),
            'scssWithSubdirs' => $this->modx->getOption('compiler_scss_with_subdirs', null, false),
            'scssMinify' => $this->modx->getOption('compiler_scss_minify', null, false),
            
            'muneeCache' => MODX_CORE_PATH . 'cache/default/compiler/munee/',
        ), $config);

        $this->modx->addPackage('compiler', $this->config['modelPath']);
        $this->modx->lexicon->load('compiler:default');
    }
    
    /**
     * Process files with Munee library
     * http://mun.ee
     * From https://github.com/bezumkin/MinifyX/blob/2325a83ca1e0f1d54593fdbfcec99daf66d05b13/core/components/minifyx/model/minifyx/minifyx.class.php#L101-L145
     *
     * @param string $files
     * @param array $options Array with options for Munee class
     *
     * @return string
     */
    public function Munee($files, $options = array())
    {
        if (!defined('WEBROOT')) {
            define('WEBROOT', MODX_BASE_PATH);
        }
        if (!defined('MUNEE_CACHE')) {
            define('MUNEE_CACHE', $this->getTmpDir());
        }
        if (!defined('SUB_FOLDER')) {
            define('SUB_FOLDER', '');
        }
        
        require_once $this->config['corePath'] . 'munee/vendor/autoload.php';
        
        try {
            $Request = new \Munee\Request($options);
            $Request->setFiles($files);
            foreach ($options as $k => $v) {
                $Request->setRawParam($k, $v);
            }
            $Request->init();
            /** @var \Munee\Asset\Type $AssetType */
            $AssetType = \Munee\Asset\Registry::getClass($Request);
            $AssetType->init();
            if (!empty($options['setHeaders'])) {
                if (isset($options['headerController']) && $options['headerController'] instanceof \Munee\Asset\HeaderSetter) {
                    $headerController = $options['headerController'];
                } else {
                    $headerController = new \Munee\Asset\HeaderSetter;
                }
                /** @var \Munee\Response $Response */
                $Response = new \Munee\Response($AssetType);
                $Response->setHeaderController($headerController);
                $Response->setHeaders(isset($options['maxAge']) ? $options['maxAge'] : 0);
            }
            return $AssetType->getContent();
        }
        catch (\Munee\ErrorException $e) {
            $error = $e->getMessage();
            if ($prev = $e->getPrevious()) {
                $error .= ': '. $e->getPrevious()->getMessage();
            }
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[Compiler] ' . $error);
            return '';
        }
    }
    
    /**
     * Checks and creates cache dir for storing prepared scripts and styles
     * From https://github.com/bezumkin/MinifyX/blob/2325a83ca1e0f1d54593fdbfcec99daf66d05b13/core/components/minifyx/model/minifyx/minifyx.class.php#L153-L166
     *
     * @return bool|string
     */
    public function prepareDir($fullpath = '')
    {
        if (empty($fullpath)) {
            return false;
        } elseif (substr($fullpath, -1) === '/' && file_exists($fullpath)) {
            return true;
        }
        
        $path = trim(str_replace(MODX_BASE_PATH, '', trim($fullpath)), '/');
        if (!file_exists(MODX_BASE_PATH . $path)) {
            $this->makeDir($path);
        }
        
        if (substr($path, -1) !== '/') {
            $path .= '/';
        }
        
        $fullpath = MODX_BASE_PATH . $path;
        
        return file_exists($fullpath);
    }
    
    /**
     * Recursive create of directories by specified path
     * From https://github.com/bezumkin/MinifyX/blob/2325a83ca1e0f1d54593fdbfcec99daf66d05b13/core/components/minifyx/model/minifyx/minifyx.class.php#L231-L249
     *
     * @param $path
     *
     * @return bool
     */
    public function makeDir($path = '')
    {
        if (empty($path)) {
            return false;
        } elseif (file_exists($path)) {
            return true;
        }
        
        $base = strpos($path, MODX_CORE_PATH) !== false
            ? MODX_CORE_PATH
            : MODX_BASE_PATH;
        $tmp = explode('/', str_replace($base, '', $path));
        $path = $base;
        
        foreach ($tmp as $v) {
            if (!empty($v)) {
                $path .= $v . '/';
                if (!file_exists($path)) {
                    mkdir($path);
                }
            }
        }
        
        return file_exists($path);
    }
    
    /**
     * Prepares and returns path to temporary directory for storing Munee cache
     * From https://github.com/bezumkin/MinifyX/blob/2325a83ca1e0f1d54593fdbfcec99daf66d05b13/core/components/minifyx/model/minifyx/minifyx.class.php#L284-L293
     *
     * @return bool
     */
    public function getTmpDir()
    {
        $dir = $this->cleanUrl($this->config['muneeCache']);
        if ($this->makeDir($dir)) {
            return $dir;
        } else {
            return false;
        }
    }
    
    /**
     * Приводит URL или PATH в порядок.
     */
    public function cleanUrl($url)
    {
        $slashes = array('////', '///', '//');

        return str_replace($slashes, '/', $url);
    }
}
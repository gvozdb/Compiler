<?php
if (!$modx->getService('compiler','Compiler', MODX_CORE_PATH.'components/compiler/model/compiler/')) {
    return false;
}

/** @var modX $modx */
switch ($modx->event->name) {
    case "OnFileManagerFileCreate":
    case "OnFileManagerFileUpdate":
        if (empty($path)) {
            return;
        }
        
        /** @var Compiler $Compiler */
        $Compiler = new Compiler($modx);
        
        //
        $pathinfo = pathinfo($path);
        $pathinfo['dirpath'] = $Compiler->cleanUrl($pathinfo['dirname'] . '/');
        $pathinfo['dirurl'] = $Compiler->cleanUrl('/' . str_replace(MODX_BASE_PATH, '', $pathinfo['dirname']) . '/');
        unset($pathinfo['dirname']);
        
        //
        if (
            $pathinfo['extension'] == 'scss'
            && strpos($pathinfo['dirurl'], $Compiler->config['scssDirFrom']) === 0
            && !($Compiler->config['scssSkipUnderscore'] && substr($pathinfo['filename'], 0, 1) === '_')
        ) {
            // Создаём директорию назначения
            if (!$Compiler->prepareDir($Compiler->config['scssDirTo'])) {
                $modx->log(modX::LOG_LEVEL_ERROR, '[Compiler] Could not create dir "' . $Compiler->config['scssDirTo'] . '"');
                return;
            }
            
            //
            $fileInnerDir = $Compiler->cleanUrl('/' . str_replace($Compiler->config['scssDirFrom'], '', $pathinfo['dirurl']) . '/');
            $fileInnerDirUrl = $Compiler->cleanUrl($Compiler->config['scssDirTo'] . $fileInnerDir);
            
            // Проверяем, можно ли компилить в подпапках.
            // Если нельзя и мы в подпапке - останавливаемся.
            if (!$Compiler->config['scssWithSubdirs'] && $fileInnerDir != '/') {
                return;
            }
            
            // Создаём подпапку назначения
            if (!$Compiler->makeDir($fileInnerDirUrl)) {
                $modx->log(modX::LOG_LEVEL_ERROR, '[Compiler] Could not create dir "' . $fileInnerDirUrl . '"');
                return;
            }
            
            // $options = array(
            //     'minify' => $Compiler->config['scssMinify'] ? 'true' : 'false',
            // );
            
            //
            $cssData = $Compiler->Munee($pathinfo['dirurl'] . $pathinfo['basename']);
            $cssData = $cssData ? $cssData : ' ';
            
            // Парсим SCSS файл для поиска подключаемых SCSS директивой @import
            $scssData = file_get_contents($pathinfo['dirpath'] . $pathinfo['basename']);
            if (preg_match_all('/@import ([^\'";,]*[\'"]+([^\'";]+)[\'"]+[^;]*);/is', $scssData, $import_matches)) {
                // Фильтруем из найденных только те, что нам подходят
                foreach ($import_matches[1] as $import_match) {
                    if (substr($import_match, 0, 3) !== 'url' &&
                        substr($import_match, 1, 5) !== 'http:' &&
                        substr($import_match, 1, 6) !== 'https:' &&
                        substr($import_match, -5, 4) !== '.css' &&
                        (
                            substr($import_match, -1) === '\'' ||
                            substr($import_match, -1) === '\"'
                        )
                    ) {
                        foreach (array_map('trim', explode(',', $import_match)) as $file) {
                            $file = str_replace(array('\'', '"'), '', $file);
                            // print $file . PHP_EOL;
                        }
                    }
                }
            }
            
            // Получаем путь до конечного CSS файла
            $fileOutPath = $Compiler->cleanUrl(MODX_BASE_PATH . $fileInnerDirUrl . $pathinfo['filename'] . '.css');
            
            // Сохраняем файл
            if (!file_put_contents($fileOutPath, $cssData)) {
                $modx->log(modX::LOG_LEVEL_ERROR, '[Compiler] Could not save cache file '. $fileOutPath);
                return false;
            }
        }
        break;
}
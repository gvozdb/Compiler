<?php
/** @var modX $modx */
/** @var array $sources */

$settings = array();

$tmp = array(
    'scss_skip_underscore' => array(
        'xtype' => 'combo-boolean',
        'area' => 'compiler_scss',
        'value' => true,
    ),
    'scss_with_subdirs' => array(
        'xtype' => 'combo-boolean',
        'area' => 'compiler_scss',
        'value' => false,
    ),
    // 'scss_minify' => array(
    //     'xtype' => 'combo-boolean',
    //     'area' => 'compiler_scss',
    //     'value' => false,
    // ),
    'scss_dir_from' => array(
        'xtype' => 'textfield',
        'area' => 'compiler_scss',
        'value' => '/assets/themes/default/src/scss/',
    ),
    'scss_dir_to' => array(
        'xtype' => 'textfield',
        'area' => 'compiler_scss',
        'value' => '/assets/themes/default/dist/css/',
    ),
);

foreach ($tmp as $k => $v) {
    /** @var modSystemSetting $setting */
    $setting = $modx->newObject('modSystemSetting');
    $setting->fromArray(array_merge(
        array(
            'key' => 'compiler_' . $k,
            'namespace' => PKG_NAME_LOWER,
        ), $v
    ), '', true, true);

    $settings[] = $setting;
}
unset($tmp);

return $settings;

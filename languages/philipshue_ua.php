<?php
/**
 * Russian language file
 *
 * @package MajorDoMo
 * @author Serge Dzheigalo <jey@tut.by> http://smartliving.ru/
 * @version 1.0
 */



$dictionary = array (
    'HUE_RESCAN_BRIDGE' => 'Пошук шлюза',
    'HUE_RESCAN_DEVICES' => 'Пошук пристроїв',
    'HUE_AUTH' => 'Авторизація'
);

foreach ($dictionary as $k => $v) {
    if (!defined('LANG_' . $k)) {
        define('LANG_' . $k, $v);
    }
}

<?php
/**
 * Russian language file
 *
 * @package MajorDoMo
 * @author Serge Dzheigalo <jey@tut.by> http://smartliving.ru/
 * @version 1.0
 */



$dictionary = array (
    'HUE_RESCAN_BRIDGE' => 'Search bridge',
    'HUE_RESCAN_DEVICES' => 'Device search',
    'HUE_AUTH' => 'Authorization'
);

foreach ($dictionary as $k => $v) {
    if (!defined('LANG_' . $k)) {
        define('LANG_' . $k, $v);
    }
}

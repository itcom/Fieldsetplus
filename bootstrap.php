<?php
/**
 * Fieldsetplus
 * 
 * @anthor     Yasuhiro Hayashida https://github.com/hayashida
 * @copyright  2012 Yasuhiro Hayashida
 * @license    MIT License
 */

Autoloader::add_core_namespace('Fieldsetplus');

Autoloader::add_classes(array(
    'Fieldsetplus\\Fieldsetplus'        => __DIR__ . '/classes/fieldsetplus.php',
    'Fieldsetplus\\Fieldsetplus_Field'  => __DIR__ . '/classes/fieldsetplus/field.php',
));

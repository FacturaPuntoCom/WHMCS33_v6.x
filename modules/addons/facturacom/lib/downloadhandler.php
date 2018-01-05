<?php

//extraemos el directorio root de la instalación de WHMCS
$root_path = str_replace("/modules/addons/facturacom/lib", "", __DIR__);

//namespace WHMCS\Module\Addon\Facturacom;
require_once dirname(__FILE__) . '/Admin/CoreModule.php';
require_once   $root_path . '/init.php';

/**
 * Handler for Json Ajax Calls from CUSTOMER AREA
 * @author Paul Soberanes  <@soberanees>
 * @copyright (c) Octuber 2015, Factura.com
 */
header('Access-Control-Allow-Methods:GET');
header('Access-Control-Allow-Origin: https://factura.com');
#header('Access-Control-Allow-Credentials : true');

$CoreModule = new CoreModule;

//print_r($_POST); die;
$CoreModule->getCFDI($_GET);

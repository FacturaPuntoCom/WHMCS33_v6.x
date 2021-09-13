<?php

//extraemos el directorio root de la instalación de WHMCS
$root_path = str_replace("/modules/addons/facturacom/lib", "", __DIR__);

//namespace WHMCS\Module\Addon\Facturacom;
require_once dirname(__FILE__) . '/Admin/CoreModule.php';
require_once $root_path . '/init.php';

/**
 * Handler for Json Ajax Calls from CUSTOMER AREA
 * @author Paul Soberanes  <@soberanees>
 * @copyright (c) Octuber 2015, Factura.com
 */
header('Access-Control-Allow-Methods:POST');
header('Access-Control-Allow-Origin: https://factura.com');
#header('Access-Control-Allow-Credentials : true');

if (isset($_POST['function'])) {
    $resultado = null;
    $func = $_POST['function'];
    $resultado = $func();
    echo json_encode($resultado);
} else {
    echo json_encode(array("Error" => "Fail"));
}

/**
 * Cancel invoice in Factura.com system
 *
 * @param Global $_POST
 * @return Array
 */
function cancelInvoice()
{

    $CoreModule = new CoreModule;
    $response = $CoreModule->cancelInvoice($_POST);

    return $response;
}

/**
 * Send invoice via email to customer
 *
 * @param Global $_POST
 * @return Array
 */
function sendInvoice()
{

    $CoreModule = new CoreModule;
    $response = $CoreModule->sendInvoiceEmail($_POST);

    return $response;
}

/**
 * Load and display in invoices table admin section
 *
 * @param Global $_POST
 * @return Array
 */
function loadInvoicesTable()
{
    $CoreModule = new CoreModule;
    return $CoreModule->getInvoicesFacturacomAll();
}

/**
 * Get location by postal code
 *
 * @param Global $_POST
 * @return Array
 */
function getLocation()
{
    $CoreModule = new CoreModule;
    return $CoreModule->getLocation($_POST['cp']);
}

/**
 * Get Factura.com client information
 *
 * @param Global $_POST
 * @return Array
 */
function getClient()
{
    $CoreModule = new CoreModule;
    return $CoreModule->getClientFacturacom($_POST['rfc']);
}

/**
 * Update client information and create Invoice
 *
 * @param Global $_POST
 * @return Array
 */
function createInvoice()
{

    $CoreModule = new CoreModule;
    $ItemsOrder = $CoreModule->getInvoiceItems($_POST['orderNum']);

    $orderNum = $_POST['orderNum'];
    $orderItems = $ItemsOrder;
    $clientData = $_POST['clientData'];
    $serieInvoices = $_POST['serieInvoices'];
    $clientW = $_POST['clientW'];
    $paymentMethod = $_POST['paymentMethod'];
    $numerocuenta = $_POST['numerocuenta'];
    $usoCFDI = $_POST['UsoCFDI'];

    return $CoreModule->createInvoice($orderNum, $orderItems, $clientData, $serieInvoices, $clientW, $paymentMethod, $numerocuenta, $usoCFDI);
}

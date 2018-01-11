<?php

include_once dirname(__FILE__) . '/Client.php';
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as Capsule;

class CoreModule
{

    public function __construct()
    {

    }

    public function getGonfiguration()
    {
        $setting = false;
        $settings = Capsule::table('tbladdonmodules')->where('module', 'facturacom')->get();

        if (!is_null($settings)) {
            foreach ($settings as $value) {
                # code...
                $setting[$value->setting] = $value->value;
            }
        }

        return $setting;
    }

    public function getURL($parameters)
    {
        if ($parameters['sandbox'] === 'on') {
            //return 'http://devfactura.in/api/v3/';
            return 'http://devfactura.in/api/';
        } else {
            return 'https://factura.com/api/';
        }
    }

    public function getWhmcsInvoicesAll($UserID)
    {
        if (!isset($UserID)) {
            return array(
                'Error' => 'No se ha recibido el id del cliente.',
            );
        }

        $configEntity = $this->getGonfiguration();
        $invoiceList = [];
        $facturaInvoiceList = [];
        $invoicesObj = Capsule::table('tblinvoices')
            ->where('tblinvoices.userid', $UserID)
            ->get();

        foreach ($invoicesObj as $key => $value) {
            $invoiceList[$value->id]["orderId"] = $value->id;
            $invoiceList[$value->id]["orderNum"] = $value->id;
            $invoiceList[$value->id]["clientId"] = $value->userid;
            $invoiceList[$value->id]["orderDate"] = date("d-m-Y", strtotime($value->date));
            $invoiceList[$value->id]["invoiceDueDate"] = date("d-m-Y", strtotime($value->duedate));
            $invoiceList[$value->id]["invoiceDatePaid"] = (!preg_match('/[1-9]/', $value->datepaid)) ? null : date("d-m-Y", strtotime($value->datepaid));
            $invoiceList[$value->id]["total"] = $value->total;
            $invoiceList[$value->id]["status"] = $value->status;
            $invoiceList[$value->id]["orderdata"] = $value->id;
            $invoiceList[$value->id]["sent"] = $configEntity['SendEmail'];
            $invoiceList[$value->id]["open"] = 'true';

            if ($value->status != "Paid") {
                $invoiceList[$value->id]["open"] = 'false';
            }

            // open
            /* validar que la factura esté dentro del mes +X días y a partir
            de la fecha de facturación configurada
             */
            $order_month = date("m", strtotime($value->datepaid));
            $order_year = date("Y", strtotime($value->datepaid));
            $current_day = date("d");
            $current_month = date("m");
            $current_year = date("Y");

            if (is_null($configEntity) && !is_array($configEntity)) {
                $invoiceList[$value->id]["open"] = 'false';
            }

            $arr = explode('/', $configEntity['activateDate']);

            /* formatear la fecha a dd-mm-aaaa porque la fecha datepaid
            tiene ese formato en WHMCS y deben tener el mismo formato para
            compararse. */
            $newDate = $arr[0] . '-' . $arr[1] . '-' . $arr[2];

            $activateDate = strtotime($newDate); //1 septiembre 2015
            $paidDate = strtotime($value->datepaid); //6 Octubre 2015

            // Validate plugin activation date vs payment date
            if ($paidDate < $activateDate) {
                $invoiceList[$value->id]["open"] = 'false';
            }

            //Validate invoice total is not zero
            if ($value->total <= 0) {
                $invoiceList[$value->id]["open"] = 'false';
            }

            //vamos sobre el tiempo de tolerancia para facturar.
            Carbon::setLocale('es');
            $strtotime = strtotime($value->datepaid);
            $fpago = explode("-", date("Y-m-d", $strtotime));

            if(is_null($strtotime) || $strtotime < 1) {
                $dt = Carbon::createFromDate(2000, 01, 01);
            } else {
                $dt = Carbon::createFromDate($fpago[0], $fpago[1], $fpago[2]);
            }


            //Sacamos la diferencia
            if($dt->diffInDays(Carbon::now()) > 30) {
                $diferenciaDicas = $dt->diffInDays(Carbon::now());
            } else {
                $diferenciaDicas =  ($dt->diffInDays(Carbon::now()) - $dt->daysInMonth);
            }

            //si la orden no está facturada y tiene dias entonces
            if (intval($diferenciaDicas) > $configEntity['DayOff']) {
                $invoiceList[$value->id]["open"] = false;
            }

        }

        return $invoiceList;
    }

    public function getWhmcsInvoices($UserID)
    {
        if (!isset($UserID)) {
            return array(
                'Error' => 'No se ha recibido el id del cliente.',
            );
        }

        $configEntity = $this->getGonfiguration();
        $invoiceList = [];
        $facturaInvoiceList = [];
        $invoicesObj = Capsule::table('tblinvoices')
            ->where('tblinvoices.userid', $UserID)
            ->get();

        foreach ($invoicesObj as $key => $value) {
            $invoiceList[$value->id]["orderId"] = $value->id;
            $invoiceList[$value->id]["orderNum"] = $value->id;
            $invoiceList[$value->id]["clientId"] = $value->userid;
            $invoiceList[$value->id]["orderDate"] = date("d-m-Y", strtotime($value->date));
            $invoiceList[$value->id]["invoiceDueDate"] = date("d-m-Y", strtotime($value->duedate));
            $invoiceList[$value->id]["invoiceDatePaid"] = (!preg_match('/[1-9]/', $value->datepaid)) ? null : date("d-m-Y", strtotime($value->datepaid));
            $invoiceList[$value->id]["total"] = $value->total;
            $invoiceList[$value->id]["status"] = $value->status;
            $invoiceList[$value->id]["orderdata"] = base64_decode($value->id);
            $invoiceList[$value->id]["sent"] = false;
            $invoiceList[$value->id]["open"] = true;

            if ($value->status != "Paid") {
                $invoiceList[$value->id]["open"] = false;
            }

            // open
            /* validar que la factura esté dentro del mes +X días y a partir
            de la fecha de facturación configurada
             */
            $order_month = date("m", strtotime($value->datepaid));
            $order_year = date("Y", strtotime($value->datepaid));
            $current_day = date("d");
            $current_month = date("m");
            $current_year = date("Y");

            if (is_null($configEntity) && !is_array($configEntity)) {
                $invoiceList[$value->id]["open"] = 'false';
            }

            $arr = explode('/', $configEntity['activateDate']);
            /* formatear la fecha a dd-mm-aaaa porque la fecha datepaid
            tiene ese formato en WHMCS y deben tener el mismo formato para
            compararse. */
            $newDate = $arr[0] . '-' . $arr[1] . '-' . $arr[2];

            $activateDate = strtotime($newDate); //1 septiembre 2015
            $paidDate = strtotime($value->datepaid); //6 Octubre 2015

            // Validate plugin activation date vs payment date
            if ($paidDate < $activateDate) {
                $invoiceList[$value->id]["open"] = false;
            }

            //Validate invoice total is not zero
            if ($value->total <= 0) {
                $invoiceList[$value->id]["open"] = false;
            }

            //vamos sobre el tiempo de tolerancia para facturar.
            Carbon::setLocale('es');
            $strtotime = strtotime($value->datepaid);
            $fpago = explode("-", date("Y-m-d", $strtotime));

            if(is_null($strtotime) || $strtotime < 1) {
                $dt = Carbon::createFromDate(2000, 01, 01);
            } else {
                $dt = Carbon::createFromDate($fpago[0], $fpago[1], $fpago[2]);
            }

            //Sacamos la diferencia
            if($dt->diffInDays(Carbon::now()) > 30) {
                $diferenciaDicas = $dt->diffInDays(Carbon::now());
            } else {
                $diferenciaDicas =  ($dt->diffInDays(Carbon::now()) - $dt->daysInMonth);
            }

            //si la orden no está facturada y tiene dias entonces
            if (intval($diferenciaDicas) > $configEntity['DayOff']) {
                $invoiceList[$value->id]["open"] = false;
            }

        }

        $facturaInvoices = $this->getInvoicesFacturacom($UserID)['data'];

        foreach ($facturaInvoices as $key => $value) {
            $facturaInvoiceList[$value['NumOrder']] = $value;
            if (array_key_exists($value['NumOrder'], $invoiceList)) {
                $invoiceList[$value['NumOrder']]["sent"] = true;
            }
        }

        $collection = array_diff_key($invoiceList, $facturaInvoiceList);
        return $collection;
    }

    public function getInvoicesFacturacom($UserID)
    {
        $Setting = $this->getGonfiguration();
        $uri_base = $this->getURL($Setting);
        $uri = $uri_base . 'v3/cfdi33/list?type_document=factura&client_reference=' . $UserID;

        $invoices_filtred = [];

        //Conectamos con api factura.com y tramos todas las facturas
        $restApi = new Client;
        $request = $restApi->get($uri, [
            'headers' => [
                'F-API-KEY' => $Setting['ApiKey'],
                'F-SECRET-KEY' => $Setting['ApiSecret'],
                'F-PLUGIN' => '60db2b9bf9790c1f4826429aee141291a6090c37',
                'Content-Type' => 'application/json',
            ],
        ]);

        return $request;
    }

    public function getInvoiceItems($invoiceId)
    {
        $itemsObj = Capsule::table('tblinvoiceitems')
            ->select("tblinvoiceitems.*", "tblhosting.id as hosting", "tblproducts.id as product", "tblhosting.packageid as package")
            ->join('tblinvoices', 'tblinvoices.id', '=', 'tblinvoiceitems.invoiceid')
            ->leftJoin('tblhosting', 'tblhosting.id', '=', 'tblinvoiceitems.relid')
            ->leftJoin('tblproducts', 'tblproducts.id', '=', 'tblhosting.packageid')
            ->where('tblinvoiceitems.invoiceid', $invoiceId)
            ->get();

        $itemsOrder = [];

        foreach ($itemsObj as $key => $value) {
            # code...
            $itemsOrder[$key] = $value;

            $configSat = Capsule::table('tblproductconfiggroups')
                ->select("tblproductconfigoptions.optionname as Nombre", "tblproductconfigoptionssub.optionname as Valor")
                ->join('tblproductconfiglinks', 'tblproductconfiggroups.id', '=', 'tblproductconfiglinks.gid')
                ->join('tblproductconfigoptions', 'tblproductconfigoptions.gid', '=', 'tblproductconfiggroups.id')
                ->join('tblproductconfigoptionssub', 'tblproductconfigoptionssub.configid', '=', 'tblproductconfigoptions.id')
                ->where('tblproductconfiglinks.pid', $value->product)
                ->get();

            if (count($configSat) > 0) {

                foreach ($configSat as $ksat => $valsat) {
                    if ($valsat->Nombre == 'ClaveProdServ') {
                        $itemsOrder[$key]->ClaveProdServ = $valsat->Valor;
                    }

                    if ($valsat->Nombre == 'ClaveUnidad') {
                        $itemsOrder[$key]->ClaveUnidad = $valsat->Valor;
                    }

                    if ($valsat->Nombre == 'Unidad') {
                        $itemsOrder[$key]->Unidad = $valsat->Valor;
                    }
                }

            } else {
                $itemsOrder[$key]->ClaveProdServ = $Setting['ClaveProdServ'];
                $itemsOrder[$key]->ClaveUnidad = $Setting['ClaveUnidad'];
                $itemsOrder[$key]->Unidad = $Setting['Unidad'];
            }
        }

        return $itemsOrder;
    }

    public function getClientFacturacom($rfc)
    {

        if (!isset($rfc)) {
            return array(
                'Error' => 'No se ha recibido el RFC del cliente.',
            );
        }

        $Setting = $this->getGonfiguration();
        $uri_base = $this->getURL($Setting);
        $uri = $uri_base . 'v1/clients/' . $rfc;

        //Conectamos con api factura.com y tramos todas las facturas
        $restApi = new Client;
        $request = $restApi->get($uri, [
            'headers' => [
                'F-API-KEY' => $Setting['ApiKey'],
                'F-SECRET-KEY' => $Setting['ApiSecret'],
                'F-PLUGIN' => '60db2b9bf9790c1f4826429aee141291a6090c37',
                'Content-Type' => 'application/json',
            ],
        ]);

        return $request;
    }

    public function getInvoicesFacturacomAll()
    {

        $Setting = $this->getGonfiguration();
        $uri_base = $this->getURL($Setting);
        $uri = $uri_base . 'v3/cfdi33/list?type_document=factura';

        //Conectamos con api factura.com y tramos todas las facturas
        $restApi = new Client;
        $request = $restApi->get($uri, [
            'headers' => [
                'F-API-KEY' => $Setting['ApiKey'],
                'F-SECRET-KEY' => $Setting['ApiSecret'],
                'F-PLUGIN' => '60db2b9bf9790c1f4826429aee141291a6090c37',
                'Content-Type' => 'application/json',
            ],
        ]);

        return $request;
    }

    public function sendClientFacturacom($params, $clientUID = false)
    {

        if (!isset($params)) {
            return array(
                'response' => 'error',
                'message' => 'Indica los parametros del cliente',
            );
        }

        $Setting = $this->getGonfiguration();
        $uri_base = $this->getURL($Setting);

        if ($clientUID === false || $clientUID == "false") {
            $uri = $uri_base . 'v1/clients/create';
        } else {
            $uri = $uri_base . 'v1/clients/' . $clientUID . '/update';
        }

        //Conectamos con api factura.com y tramos todas las facturas
        $restApi = new Client;
        $request = $restApi->post($uri, [
            'json' => $params,
            'headers' => [
                'F-API-KEY' => $Setting['ApiKey'],
                'F-SECRET-KEY' => $Setting['ApiSecret'],
                'F-PLUGIN' => '60db2b9bf9790c1f4826429aee141291a6090c37',
                'Content-Type' => 'application/json',
            ],
        ]);

        return $request;
    }

    public function getSystemURL()
    {
        $systemURL = Capsule::table('tblconfiguration')
            ->where('setting', 'SystemURL')
            ->first();

        return $systemURL->value;
    }

    public function getLocation($cp)
    {

        if (!isset($cp)) {
            return array(
                'Error' => 'No se ha recibido el Código Postal.',
            );
        }

        $Setting = $this->getGonfiguration();
        $uri_base = $this->getURL($Setting);
        $uri = $uri_base . 'v3/getCodPos?cp=' . $cp;

        //Conectamos con api factura.com y tramos todas las facturas
        $restApi = new Client;
        $request = $restApi->get($uri, [
            'headers' => [
                'F-API-KEY' => $Setting['ApiKey'],
                'F-SECRET-KEY' => $Setting['ApiSecret'],
                'F-PLUGIN' => '60db2b9bf9790c1f4826429aee141291a6090c37',
                'Content-Type' => 'application/json',
            ],
        ]);

        return $request;
    }

    /**
     * Update client information and create Invoice
     *
     * @param Int $orderNum
     * @param Array $orderItems
     * @param Array $clientData
     * @param String $serieInvoices
     * @param Int $clientW
     * @param String $paymentMethod
     * @return Array
     */
    public function createInvoice($orderNum, $orderItems, $clientData, $serieInvoices, $clientW, $paymentMethod, $numerocuenta)
    {

        /*if ($clientData['clientUID'] == "") {
        return array(
        'Error' => 'No se ha recibido el UID del cliente.',
        );
        }*/

        $Setting = $this->getGonfiguration();
        $uri_base = $this->getURL($Setting);
        $clientUID = $clientData["clientUID"] ?: false;
        $clientRFC = $clientData['fiscal-rfc'];
        $invoiceData = [];

        //si el uid de cliente no está vacio entonces...
        if (!empty($clientUID)) {
            $clientFactura = $this->getClientFacturacom($clientRFC);
        } else {
            //preparamos la inserción de cliente
            $params = array(
                'nombre' => $clientData["general-nombre"],
                'apellidos' => $clientData["general-apellidos"],
                'email' => $clientData["general-email"],
                'telefono' => $clientData["fiscal-telefono"],
                'razons' => $clientData["fiscal-nombre"],
                'rfc' => $clientData["fiscal-rfc"],
                'calle' => $clientData["fiscal-nombre"],
                'numero_exterior' => $clientData["fiscal-exterior"],
                'numero_interior' => $clientData["fiscal-interior"],
                'codpos' => $clientData["fiscal-cp"],
                'colonia' => $clientData["fiscal-colonia"],
                'estado' => $clientData["fiscal-municipio"],
                'ciudad' => $clientData["fiscal-estado"],
                'delegacion' => $clientData["fiscal-pais"],
                'save' => true,
                'client_reference' => $clientW,
            );

            $processClient = $this->sendClientFacturacom($params, $clientUID);

            if ($processClient->response != 'success') {
                return [
                    'response' => 'error',
                    'message' => 'Ha ocurrido un error. Por favor revise sus datos e inténtelo de nuevo.',
                ];
            }

            $clientFactura = $processClient;
        }

        $itemsCollection = $orderItems;
        $invoiceConcepts = [];
        //print_r($orderItems); die;

        //Adding concepts to invoice
        foreach ($itemsCollection as $value) {
            $productPrice = 0;

            if ($Setting["IVA"] == 'on') {
                $productPrice = $value->amount / 1.16;
            } else {
                $productPrice = $value->amount;
            }

            $importeImpuesto = round(($productPrice * 0.16), 2);

            $product = [
                'ClaveProdServ' => $value->ClaveProdServ,
                'Cantidad' => '1',
                'ClaveUnidad' => $value->ClaveUnidad,
                'Unidad' => $value->Unidad,
                'ValorUnitario' => $productPrice,
                'Descripcion' => $value->description,
                'Descuento' => '0',
                'Impuestos' => [
                    'Traslados' => [
                        ['Base' => $productPrice, 'Impuesto' => '002', 'TipoFactor' => 'Tasa', 'TasaOCuota' => '0.16', 'Importe' => $importeImpuesto],
                    ],
                ],
            ];

            array_push($invoiceConcepts, $product);
        }

        if ($numerocuenta == '') {
            $num_cta = 'No Identificado';
        } else {
            $num_cta = $numerocuenta;
        }

        $invoiceData = [
            "Receptor" => ["UID" => $clientFactura['Data']['UID']],
            "TipoDocumento" => "factura",
            "UsoCFDI" => $Setting["UsoCFDI"],
            "Redondeo" => 2,
            "Conceptos" => $invoiceConcepts,
            "numerocuenta" => $numerocuenta,
            "FormaPago" => $paymentMethod,
            "MetodoPago" => 'PUE',
            "Moneda" => "MXN",
            "NumOrder" => $orderNum,
            "Serie" => $serieInvoices,
            "EnviarCorreo" => 'true',
        ];

        $uri = $uri_base . 'v3/cfdi33/create';

        //Conectamos con api factura.com y tramos todas las facturas
        $restApi = new Client;
        $createInvoice = $restApi->post($uri, [
            'json' => $invoiceData,
            'headers' => [
                'F-API-KEY' => $Setting['ApiKey'],
                'F-SECRET-KEY' => $Setting['ApiSecret'],
                'F-PLUGIN' => '60db2b9bf9790c1f4826429aee141291a6090c37',
                'Content-Type' => 'application/json',
            ],
        ]);

        return $createInvoice;
    }

    public function getCFDI($params)
    {

        if (!isset($params)) {
            return [
                'response' => 'error',
                'message' => 'No hemos recibido parametros para procesar',
            ];
        }

        $Setting = $this->getGonfiguration();
        $uri_base = $this->getURL($Setting);

        if ($params['version'] == '3.3') {
            $uri = $uri_base . 'v3/cfdi33/' . $params['uid'] . '/' . $params['type'];
        } else {
            $uri = $uri_base . 'publica/invoice/' . $params['uid'] . '/' . $params['type'];
            return header("Location: " . $uri);
        }

        //Conectamos con api factura.com y tramos todas las facturas
        $restApi = new Client;
        $request = $restApi->get($uri, [
            'headers' => [
                'F-API-KEY' => $Setting['ApiKey'],
                'F-SECRET-KEY' => $Setting['ApiSecret'],
                'F-PLUGIN' => '60db2b9bf9790c1f4826429aee141291a6090c37',
                'Content-Type' => 'application/json',
            ],
        ]);

        $filename = base64_decode($params['name']);

        switch ($params['type']) {
            case 'xml':
                header('Content-disposition: attachment; filename="' . $filename . '.xml"');
                header('Content-type: "text/xml"; charset="utf8"');
                echo $request;
                break;
            case 'pdf':

                header('Content-Type: application/pdf');
                header("Content-Transfer-Encoding: Binary");
                header('Content-disposition: attachment; filename="' . $filename . '.pdf"');
                echo $request;
                break;
        }
    }

    public function sendInvoiceEmail($params)
    {

        if (!isset($params)) {
            return [
                'response' => 'error',
                'message' => 'No hemos recibido parametros para procesar',
            ];
        }

        $Setting = $this->getGonfiguration();
        $uri_base = $this->getURL($Setting);

        if ($params['version'] == '3.3') {
            $uri = $uri_base . 'v3/cfdi33/' . $params['uid'] . '/email';
        } else {
            $uri = $uri_base . 'v1/invoice/' . $params['uid'] . '/email';
        }

        //Conectamos con api factura.com y tramos todas las facturas
        $restApi = new Client;
        $request = $restApi->get($uri, [
            'headers' => [
                'F-API-KEY' => $Setting['ApiKey'],
                'F-SECRET-KEY' => $Setting['ApiSecret'],
                'F-PLUGIN' => '60db2b9bf9790c1f4826429aee141291a6090c37',
                'Content-Type' => 'application/json',
            ],
        ]);

        return $request;
    }

    public function cancelInvoice($params)
    {

        if (!isset($params)) {
            return [
                'response' => 'error',
                'message' => 'No hemos recibido parametros para procesar',
            ];
        }

        $Setting = $this->getGonfiguration();
        $uri_base = $this->getURL($Setting);

        if ($params['version'] == '3.3') {
            $uri = $uri_base . 'v3/cfdi33/' . $params['uid'] . '/cancel';
        } else {
            $uri = $uri_base . 'v1/invoice/' . $params['uid'] . '/cancel';
        }

        //Conectamos con api factura.com y tramos todas las facturas
        $restApi = new Client;
        $request = $restApi->get($uri, [
            'headers' => [
                'F-API-KEY' => $Setting['ApiKey'],
                'F-SECRET-KEY' => $Setting['ApiSecret'],
                'F-PLUGIN' => '60db2b9bf9790c1f4826429aee141291a6090c37',
                'Content-Type' => 'application/json',
            ],
        ]);

        return $request;
    }
}

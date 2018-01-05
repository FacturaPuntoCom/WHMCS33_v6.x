<?php


require_once dirname(__FILE__) . '/CoreModule.php';
use Illuminate\Database\Capsule\Manager as Capsule;
use Smarty;

/**
 * Sample Admin Area Controller
 */
class Controller {

    /**
     * Index action.
     *
     * @param array $vars Module configuration parameters
     *
     * @return string
     */
    public function index($vars, $uri_base)
    {
        $CoreModule = new CoreModule;
        $systemURL = $CoreModule->getSystemURL();

        // Get common module parameters
        $modulelink = $vars['modulelink']; // eg. addonmodules.php?module=addonmodule
        $version = $vars['version']; // eg. 1.0
        $LANG = $vars['_lang']; // an array of the currently loaded language variables
        $uri = $uri_base . 'cfdi33/list?type_document=factura';
        $invoices_filtred = [];


        //Conectamos con api factura.com y tramos todas las facturas
        $request = $CoreModule->getInvoicesFacturacomAll();

        if($request['response'] == 'error') {
            echo $request['message']; die;
        } else {

            $invoices_filtred = $request;
            //echo "<pre>"; print_r($request); die;

            foreach ($request['data'] as $key => $register) {

                //checamos que la factura exista en ambos sistemas
                $chekInvoice = Capsule::table('tblinvoices')->where('id', $register['NumOrder'])->first();

                if(is_null($chekInvoice)) {
                    //unset($invoices_filtred['data'][$key]);
                }
            }
            //print_r($invoices_filtred);
            $smarty = new Smarty;
            $smarty->debugging = false;
            $smarty->caching = false;
            $smarty->cache_lifetime = 120;

            $smarty->assign("invoices", $invoices_filtred);
            $smarty->assign("systemURL", $systemURL);

            $smarty->display(str_replace("lib", "", dirname(__DIR__)) . 'templates/admin.tpl');
        }
    }

    /**
     * Show action.
     *
     * @param array $vars Module configuration parameters
     *
     * @return string
     */
    public function show($vars)
    {
        // Get common module parameters
        $modulelink = $vars['modulelink']; // eg. addonmodules.php?module=addonmodule
        $version = $vars['version']; // eg. 1.0
        $LANG = $vars['_lang']; // an array of the currently loaded language variables

        // Get module configuration parameters
        $configTextField = $vars['Text Field Name'];
        $configPasswordField = $vars['Password Field Name'];
        $configCheckboxField = $vars['Checkbox Field Name'];
        $configDropdownField = $vars['Dropdown Field Name'];
        $configRadioField = $vars['Radio Field Name'];
        $configTextareaField = $vars['Textarea Field Name'];


    }
}

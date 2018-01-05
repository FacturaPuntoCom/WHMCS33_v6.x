<?php

class Client
{

    /**
     * Execute curl call to api
     *
     * @param String $url
     * @param String $request
     * @param Array $params
     * @return Array
     */
    public function get($url, $params = null)
    {

        $ch = curl_init();
        $cabeceras = $this->parseHeaders($params['headers']);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $cabeceras);

        //Curl Logs
        $fp = fopen(dirname(__FILE__) . '/logs/errorlog.txt', 'a') or die('can not open log file');
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_STDERR, $fp);

        try {
            $data = curl_exec($ch);

            $Contentype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($data, 0, $header_size);
            $body = substr($data, $header_size);
            curl_close($ch);
        } catch (Exception $e) {
            print('Exception occured: ' . $e->getMessage());
        }

        //Api Logs
        $apiLogFile = dirname(__FILE__) . '/logs/apilog.txt';
        $fh = fopen($apiLogFile, 'a') or die('can not open log file');
        $logRow = '[' . date('d-m-Y h:s:i') . '] api_url: ' . $url
        . ' | api_request: ' . $request
        . ' | api_params: ' . json_encode($_GET)
        . ' | config_vars: ' . json_encode($params['headers'])
        . ' | api_response: ' . $data . "\n";
        fwrite($fh, $logRow);
        fclose($fh);
        
        if($Contentype == "application/json") {
            return json_decode($body, true);
        } else {
            return $body;
        }


    }

    public function post($url, $params = null)
    {

        $ch = curl_init();
        $cabeceras = $this->parseHeaders($params['headers']);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        if (!isset($params)) {
            $params = 'no data';
        }

        $dataString = json_encode($params['json']);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $cabeceras);

        //Curl Logs
        $fp = fopen(dirname(__FILE__) . '/logs/errorlog.txt', 'a')
        or die('can not open log file');
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_STDERR, $fp);

        try {
            $data = curl_exec($ch);
            curl_close($ch);
        } catch (Exception $e) {
            print('Exception occured: ' . $e->getMessage());
        }

        //Api Logs
        $apiLogFile = dirname(__FILE__) . '/logs/apilog.txt';
        $fh = fopen($apiLogFile, 'a')
        or die('can not open log file');
        $logRow = '[' . date('d-m-Y h:s:i') . '] api_url: ' . $url
        . ' | api_request: ' . $request
        . ' | api_params: ' . json_encode($params['json'])
        . ' | config_vars: ' . json_encode($params['headers'])
        . ' | api_response: ' . $data . "\n";
        fwrite($fh, $logRow);
        fclose($fh);

        //return array
        return json_decode($data, true);
    }

    private function parseHeaders($headers)
    {
        $header = [];
        foreach ($headers as $key => $value) {
            $header[] = $key . ": " . $value;
        }
        //print_r($header); die;
        return $header;
    }

}

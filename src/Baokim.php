<?php

namespace quangthinh\yii2\baokim;

use Exception;

class Baokim {

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const BAOKIM_TRANSACTION_STATUS_COMPLETED = 4;
    const BAOKIM_TRANSACTION_STATUS_TEMP_HOLDING = 13;
    const API_SELLER_INFO = '/payment/rest/payment_pro_api/get_seller_info'; // API lấy thông tin người bán
    const API_PAYMENT_PRO = '/payment/rest/payment_pro_api/pay_by_card'; // API thực hiện thanh toánons
    const BAOKIM_URL = 'https://www.baokim.vn/bpn/verify';
    const BAOKIM_SANDBOX_URL = 'http://sandbox.baokim.vn/bpn/verify';

    public $business;
    public $sandbox;
    public $privateKey;
    public $username;
    public $password;
    private $errors;

    public function __construct() {
        if (!$this->isSupported()) {
            throw new Exception(implode("\n", $this->getErrors()));
        }

        // init
    }

    public function isSupported() {

        $result = true;

        if (!$this->isCurlSupport()) {
            $this->errors[] = 'CUrl is not available';
            $result = false;
        }

        if (!$this->isOpenSSLSupport()) {
            $this->errors[] = 'OpenSSL is not available';
            $result = false;
        }

        return $result;
    }

    public function getErrors() {
        return $this->errors;
    }

    /**
     * gọi api lấy thông tin của business
     */
    public function getSellerInfo($business) {
        $result = $this->api(self::METHOD_GET, ['business' => $business], self::API_SELLER_INFO);
        if (isset($result['error']{3})) { // strlen > 2
            $this->errors[] = $result['error'];
            return false;
        }

        $seller_info = json_decode($result, true);
        if (isset($seller_info['error'])) {
            $this->errors[] = $seller_info['error'];
            return false;
        }

        return $seller_info;
    }

    public function listenerCallback($request) {

        // build query
        if (empty($request)) {
            $this->errors[] = 'invalid request to baokim';
            return false;
        }

        $baokim_url = $this->sandbox ? self::BAOKIM_URL : self::BAOKIM_SANDBOX_URL;
        $request = http_build_query($_POST);

        $ch = curl_init($baokim_url);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);


        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        if ((!empty($status)) && strstr($result, 'VERIFIED') && $status == 200) {
            return true;
        }

        $this->errors[] = $error;
        return false;
    }

    private function isCurlSupport() {
        return extension_loaded('curl');
    }

    private function isOpenSSLSupport() {
        return extension_loaded('openssl');
    }

    private function api($method, $params, $api) {
        if ($method != self::METHOD_GET && $method != self::METHOD_POST) {
            $this->errors[] = 'method is unsupported';
            return false;
        }

        $server_url = $this->sandbox ? self::BAOKIM_URL : self::BAOKIM_SANDBOX_URL;

        ksort($params);
        $post = $get = [];
        if ($method === self::METHOD_GET) {
            $get = $params;
        } else if ($method === self::METHOD_POST) {
            $post = $params;
        }

        $signature = $this->makeSignature($method, $api, $get, $post);
        $url = $server_url . $api . '?signature=' . $signature;

        if ($method === self::METHOD_GET) {
            $url .= $this->createRequestUrl($params);
        }


        // curl
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST | CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, implode(':', [$this->username, $this->password]));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // set post params
        if ($method === self::METHOD_POST) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }

        // call
        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        if (empty($result)) {
            return array(
                'status' => $status,
                'error' => $error
            );
        }

        return $result;
    }

    /**
     * Hàm thực hiện việc tạo chữ ký với dữ liệu gửi đến Bảo Kim
     *
     * @param $method
     * @param $url
     * @param array $getArgs
     * @param array $postArgs
     * @param $priKeyFile
     * @return string
     */
    private function makeSignature($method, $url, $getArgs = array(), $postArgs = array()) {
        if (strpos($url, '?') !== false) {
            list($url, $get) = explode('?', $url);
            parse_str($get, $get);
            $getArgs = array_merge($get, $getArgs);
        }

        ksort($getArgs);
        ksort($postArgs);


        $method = strtoupper($method);

        $data = $method . '&' . urlencode($url) . '&' . urlencode(http_build_query($getArgs)) . '&' . urlencode(http_build_query($postArgs));

        $pk = openssl_get_privatekey($this->privateKey);
        assert('$pk !== false');

        $x = openssl_sign($data, $signature, $pk, OPENSSL_ALGO_SHA1);
        assert('$x !== false');

        return urlencode(base64_encode($signature));
    }

    private function createRequestUrl($data) {
        $params = $data;
        ksort($params);
        $url_params = '';
        foreach ($params as $key => $value) {
            if ($url_params == '')
                $url_params .= $key . '=' . urlencode($value);
            else
                $url_params .= '&' . $key . '=' . urlencode($value);
        }
        return "&" . $url_params;
    }

}

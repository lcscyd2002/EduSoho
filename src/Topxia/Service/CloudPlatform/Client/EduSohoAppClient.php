<?php
namespace Topxia\Service\CloudPlatform\Client;

use Topxia\Service\CloudPlatform\Client\AppClient;
use Topxia\System;

class EduSohoAppClient implements AppClient
{

    protected $userAgent = 'Open Edusoho App Client 1.0';

    protected $connectTimeout = 5;

    protected $timeout = 5;

    private $apiUrl = 'http://open.edusoho.com/app_api';

    private $debug = false;

    public function __construct (array $options)
    {
        $this->accessKey = empty($options['accessKey']) ? 'Anonymous' : $options['accessKey'];
        $this->secretKey = empty($options['secretKey']) ? '' : $options['secretKey'];

        if (!empty($options['apiUrl'])) {
            $this->apiUrl = $options['apiUrl'];
        }

        $this->debug = empty($options['debug']) ? false : true;

        $this->tmpDir = empty($options['tmpDir']) ? '/tmp' : $options['tmpDir'];
    }

    public function getApps()
    {
        $args = array();
        return $this->callRemoteApi('GET', 'GetApps', $args);
    }

    public function checkUpgradePackages($apps)
    {
        $args = array('apps' => $apps);
        return $this->callRemoteApi('POST', 'CheckUpgradePackages', $args);
    }

    public function submitRunLog($log)
    {
        $args = array('log' => $log);
        return $this->callRemoteApi('POST', 'SubmitRunLog', $args);
    }

    public function downloadPackage($packageId)
    {
        $args = array('packageId' => (string)$packageId);
        list($url, $httpParams) = $this->assembleCallRemoteApiUrlAndParams('DownloadPackage', $args);
        $url = $url . (strpos($url, '?') ? '&' : '?') . http_build_query($httpParams);
        return $this->download($url);
    }

    public function checkDownloadPackage($packageId)
    {
        $args = array('packageId' => (string)$packageId);
        return $this->callRemoteApi('GET', 'CheckDownloadPackage', $args);
    }

    public function getPackage($id)
    {
        $args = array('packageId' => (string)$id);
        return $this->callRemoteApi('GET', 'GetPackage', $args);
    }

    public function repairProblem($token)
    {

    }

    private function callRemoteApi($httpMethod, $action, array $args)
    {
        list($url, $httpParams) = $this->assembleCallRemoteApiUrlAndParams($action, $args);

        $result = $this->sendRequest($httpMethod, $url, $httpParams);

        // var_dump($result);

        return json_decode($result, true);
    }

    private function assembleCallRemoteApiUrlAndParams($action, array $args)
    {
        $url = "{$this->apiUrl}?action={$action}";

        $edusoho = array(
            'edition' => 'opensource', 
            'host' => $_SERVER['HTTP_HOST'],
            'version' => System::VERSION, 
            'debug' => $this->debug ? '1' : '0',
        );
        $args['_edusoho'] = $edusoho;

        $httpParams = array();
        $httpParams['accessKey'] = $this->accessKey;
        $httpParams['args'] = $args;
        $httpParams['sign'] = hash_hmac('sha1', base64_encode(json_encode($args)), $this->secretKey);

        return array($url, $httpParams);
    }

    private function download($url)
    {
        // var_dump($url);
        $filename = md5($url) . '_' . time();
        $filepath = $this->tmpDir . '/' . $filename;

        $fp = fopen($filepath, 'w');

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_FILE, $fp);
        curl_exec($curl);
        curl_close($curl);

        fclose($fp);

        return $filepath;
    }

    private function sendRequest($method, $url, $params = array())
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_USERAGENT, $this->userAgent);

        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 0);

        if (strtoupper($method) == 'POST') {
            curl_setopt($curl, CURLOPT_POST, 1);
            $params = http_build_query($params);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        } else {
            if (!empty($params)) {
                $url = $url . (strpos($url, '?') ? '&' : '?') . http_build_query($params);
            }
        }

        curl_setopt($curl, CURLOPT_URL, $url );

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

}
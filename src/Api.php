<?php
declare(strict_types=1);

namespace YandexDiskApi;

use \LightweightCurl\Curl;
use \LightweightCurl\Request;

class Api
{
    private $tocken;

    public function __construct($tocken)
    {
        $this->tocken = $tocken;
    }

    public function upload(string $filename, string $dist): void
    {
        if (!is_readable($filename)) {
            throw new \Exception('file');
        }

        $curl = new Curl();

        $request = new Request();
        $request->setHeaders([
            'Authorization' => 'OAuth ' . $this->tocken,
            'User-Agent' => 'GitLab CI',
        ]);

        $url = 'https://cloud-api.yandex.net/v1/disk/resources/upload?path=' . urlencode($dist) . '&overwrite=true';
        $request->setUrl($url);
        $response = $curl->call($request);
        $data = json_decode($response->getData());
        if ($response->getHttpCode() < 200 || 300 <= $response->getHttpCode()) {
            throw new \Exception($data->error);
        }

        $request = new Request();
        $request->setUrl($data->href);
        $request->setMethod(Request::METHOD_PUT);

        $request->setFileForPutRequest($filename);
        $request->setHeaders([
            'Etag' => md5_file($filename),
            'Sha256' => hash_file('sha256', $filename),
        ]);

        $response = $curl->call($request);
        if ($response->getHttpCode() !== 201) {
            throw new \Exception('ErrorToUpload');
        }
    }
}

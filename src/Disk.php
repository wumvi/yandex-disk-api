<?php
declare(strict_types=1);

namespace YandexDiskApi;

use LightweightCurl\Curl;
use LightweightCurl\Request;
use YandexDiskApi\Exception\DiskException;

class Disk
{
    /**  */
    private const URL = 'https://cloud-api.yandex.net/v1/disk/resources/';

    /**
     * @var string
     */
    private $token;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * Disk constructor.
     *
     * @param string $token
     */
    public function __construct(string $token)
    {
        $this->token = $token;
        $this->curl = new Curl();
    }

    /**
     * @param string $serverFile
     * @param string $yandexFile
     *
     * @throws \Exception
     */
    public function upload(string $serverFile, string $yandexFile): void
    {
        if (!is_readable($serverFile)) {
            throw new DiskException('Upload file not found');
        }

        $request = new Request();
        $request->setHeaders([
            'Authorization' => 'OAuth ' . $this->token,
        ]);

        $url = self::URL . 'upload?path=' . urlencode($yandexFile) . '&overwrite=true';
        $request->setUrl($url);
        $response = $this->curl->call($request);
        $data = json_decode($response->getData());
        if ($response->getHttpCode() < 200 || 300 <= $response->getHttpCode()) {
            throw new DiskException($data->error);
        }

        $request = new Request();
        $request->setUrl($data->href);
        $request->setMethod(Request::METHOD_PUT);

        $request->setFileForPutRequest($serverFile);
        $request->setHeaders([
            'Etag' => md5_file($serverFile),
            'Sha256' => hash_file('sha256', $serverFile),
        ]);

        $response = $this->curl->call($request);
        if ($response->getHttpCode() !== 201) {
            throw new DiskException('ErrorToUpload');
        }
    }

    /**
     * @param string $yandexFile File on Yandex
     * @param string $serverFile Dist file on own server
     *
     * @throws DiskException
     */
    public function download(string $yandexFile, string $serverFile): void
    {
        $request = new Request();
        $request->setHeaders([
            'Authorization' => 'OAuth ' . $this->token,
        ]);

        $request->setUrl(self::URL . 'download?path=' . urlencode($yandexFile));
        $response = $this->curl->call($request);

        $data = json_decode($response->getData());
        if ($response->getHttpCode() < 200 || 300 <= $response->getHttpCode()) {
            throw new DiskException($data->error);
        }

        $request = new Request();
        $request->setUrl($data->href);
        $request->setOutFilename($serverFile);

        $response = $this->curl->call($request);

        if ($response->getHttpCode() !== 200) {
            throw new DiskException('ErrorToDownload');
        }
    }
}

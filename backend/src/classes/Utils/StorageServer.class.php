<?php

class StorageServer {
    const SERVICE_NAME = 'sstorage';
    const SERVICE_PORT = '228';

    const RESERVED_SPACE_PERCENT = 0.05;

    /**
     * @var string server hostname for internal use
     */
    protected $nameInt;
    /**
     * @var string server hostname visible from internet
     */
    protected $nameExt;

    public function __construct($nameInt, $nameExt)
    {
        Assert::isNotEmpty($nameInt, 'internal hostname empty');
        Assert::isNotEmpty($nameExt, 'external hostname empty');
        $this->nameInt = $nameInt;
        $this->nameExt = $nameExt;
    }

    /**
     * @return StorageServer
     * @throws IOException
     */
    public static function get()
    {
        $stats = self::request('http://' . static::SERVICE_NAME . ':' . self::SERVICE_PORT . '/stats');
        $items = $stats['peers'] + [ $stats['self'] ];
        $servers = [];
        $minPercent = 100;
        foreach ($items as $item) {
            $totalSpace = $item['disk']['total'];
            $availableSpace = $item['disk']['available'];
            $reservedSpace = min($totalSpace * self::RESERVED_SPACE_PERCENT, 1 * pow(1024, 3) );
            if ($totalSpace - $reservedSpace <= 0) {
                $percentFree = 0;
            } else {
                $percentFree = 100 * ($availableSpace - $reservedSpace) / ($totalSpace - $reservedSpace);
            }

            if ($percentFree > 0) {
                $servers[$item['nameInt']] = $percentFree;
                if ($percentFree < $minPercent) $minPercent = $percentFree;
            }
        }
        if (empty($servers)) {
            throw new IOException('Нет доступного хранилища');
        }

        // weighed random, granularity is 0.01%
        $totalWeight = array_sum($servers) * 10000;
        $random = rand(0, $totalWeight) / 10000;
        $selectedServer = null;
        foreach ($servers as $server => $weight) {
            if ($random <= $weight) {
                $selectedServer = $server;
                break;
            } else {
                $random -= $weight;
            }
        }

        $domainName = null;
        foreach ($items as $item) {
            if ($item['nameInt'] == $selectedServer) {
                $domainName = $item['nameExt'];
                break;
            }
        }

        return new self($selectedServer, $domainName);
    }

    public static function getByExtName($nameExt)
    {
        $stats = self::request('http://' . static::SERVICE_NAME . ':' . self::SERVICE_PORT . '/stats');
        $items = $stats['peers'];
        $items []= $stats['self'];
        foreach ($items as $item) {
            if ($item['nameExt'] == $nameExt) {
                return new self($item['nameInt'], $item['nameExt']);
            }
        }
        return null;
    }

    /**
     * @param $url
     * @return mixed
     * @throws IOException
     */
    protected static function request($url)
    {
        $response = file_get_contents($url);
        if ($response === false) {
            throw new IOException('request ' . $url . ' failed');
        }
        $data = json_decode($response, true);
        if ($data === false) {
            throw new IOException('decode failed: ' . $response);
        }
        return $data;
    }

    public function getStat()
    {
        return self::request('http://' . $this->nameInt . ':228/stat');
    }

    public function getNameInt()
    {
        return $this->nameInt;
    }

    public function getNameExt()
    {
        return $this->nameExt;
    }

    /**
     * @param $blob
     * @return \Psr\Http\Message\StreamInterface
     * @throws IOException
     * @throws NetworkException
     */
    public function uploadImage($blob)
    {
        $t = microtime(1);
        $client = new GuzzleHttp\Client();
        $response = $client->request(
            'POST',
            'http://' . $this->nameInt . ':228/image',
            [
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => $blob,
                        'headers' => [
                            'Content-Type' => 'application/octet-stream'
                        ],
                        'filename' => 'blob'
                    ]
                ]
            ]
        );
        if ($response->getStatusCode() != 200) {
            throw new NetworkException($response->getReasonPhrase());
        }
        $data = json_decode((string)$response->getBody(), true);
        if (!$data) {
            throw new IOException('could not decode: ' . $response->getBody());
        }
        if (!$data['ok']) {
            throw new IOException('error: ' . $data['error']);
        }
        return $data['result'] + [ 't' => microtime(1) - $t ];
    }

    public function deleteFile($filename)
    {
        $client = new GuzzleHttp\Client();
        $response = $client->request(
            'DELETE',
            'http://' . $this->nameInt . ':228/' . $filename
        );
        if ($response->getStatusCode() != 200) {
            throw new NetworkException($response->getReasonPhrase());
        }
        $data = json_decode((string)$response->getBody(), true);
        if (!$data) {
            throw new IOException('could not decode: ' . $response->getBody());
        }
        if (!$data['ok']) {
            throw new IOException('error: ' . $data['error']);
        }
        return $this;
    }
}
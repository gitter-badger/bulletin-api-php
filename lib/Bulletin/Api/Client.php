<?php
namespace Bulletin\Api;

class Client
{
    /**
     * The Bulletin API endpoint.
     *
     * @var string
     */
    private $apiEndpoint = 'https://www.bulletin.email/api/';

    /**
     * @var string
     */
    private $apiToken;

    /**
     * @param string $apiToken The Bulletin API token
     */
    public function __construct($apiToken)
    {
        $this->apiToken = $apiToken;
    }

    /**
     * Get a list of Bulletin Lists.
     *
     * @return   array
     * @throws   Exception
     */
    public function getLists()
    {
        return $this->request('GET', 'lists');
    }

    /**
     * Subscribe someone to a specific list.
     *
     * @param string $listId The list ID to which this person will be subscribed.
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function subscribe($listId, array $data)
    {
        return $this->request('POST', 'lists/'.$listId.'/subscribers', $data);
    }

    /**
     * Check the response and return when it's valid. When it's not throw an exception with the error from the Bulletin API.
     *
     * @param string $method
     * @param $endPoint
     * @param array $postData
     * @return mixed
     * @throws Exception
     */
    private function request($method = 'GET', $endPoint, array $postData = [])
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ["X-API-Token: $this->apiToken"],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_URL => $this->apiEndpoint.$endPoint
        ]);

        if ($method === 'POST') {
            curl_setopt_array($ch, [
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $postData
            ]);
        }

        $response = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $errNum = curl_errno($ch);
        $errorMsg = curl_error($ch);

        curl_close($ch);

        if ($errNum > 0) {
            throw new Exception(sprintf(
                'Error during request. CURL[%s]: %s',
                $errNum,
                $errorMsg
            ), $responseCode);
        }

        switch ($responseCode) {
            case 200:
                return json_decode($response, true);

            case 401:
                throw new Exception('API token not submitted', $responseCode);

            case 403:
                throw new Exception('Invalid API token used', $responseCode);

            default:
                $data = @json_decode($response, true);
                $message = is_array($data)
                    ? var_export($data['error'], true)
                    : 'Communication with Bulletin failed';

                throw new Exception($message, $responseCode);
        }
    }
}
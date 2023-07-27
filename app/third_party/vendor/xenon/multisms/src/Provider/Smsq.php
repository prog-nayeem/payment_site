<?php


namespace Xenon\Multisms\Provider;


use Xenon\Multisms\Handler\XenonException;
use Xenon\Multisms\Sender;

class Smsq extends AbstractProvider
{
    /**
     * Smsq constructor.
     * @param Sender $sender
     */
    public function __construct(Sender $sender)
    {
        $this->senderObject = $sender;
    }

    /**
     * Send Request To Api and Send Message
     */
    public function sendRequest(): array
    {
        $url = "https://api.smsq.global/api/v2/SendSMS";
        $number = $this->formatNumber($this->senderObject->getMobile());
        $text = $this->senderObject->getMessage();
        $config = $this->senderObject->getConfig();

        $data = [
            'SenderId'      => $config['sender_id'],
            'ApiKey'        => $config['api_key'],
            'ClientId'      => $config['client_id'],
            'MobileNumbers' => $number,
            'Message'       => $text
        ];
        $ch = curl_init(); // Initialize cURL
        curl_setopt($ch, CURLOPT_URL, $url . "?" . http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $smsResult = curl_exec($ch);
        curl_close($ch);
        return $this->generateReport($smsResult, $data);
    }

    /**
     * For mobile number
     * @param $mobile
     * @return string
     */
    private function formatNumber($mobile): string
    {
        if (mb_substr($mobile, 0, 2) == '01') {
            $number = $mobile;
        } elseif (mb_substr($mobile, 0, 2) == '88') {
            $number = str_replace('88', '', $mobile);
        } elseif (mb_substr($mobile, 0, 3) == '+88') {
            $number = str_replace('+88', '', $mobile);
        }
        return '88' . $number;
    }

    /**
     * @throws XenonException
     */
    public function errorException()
    {
        if (!is_array($this->senderObject->getConfig())) {
            throw new XenonException('Configuration is not provided. Use setConfig() in method chain');
        }
        if (!array_key_exists('sender_id', $this->senderObject->getConfig())) {
            throw new XenonException('sender_id key is absent in configuration');
        }
        if (!array_key_exists('api_key', $this->senderObject->getConfig())) {
            throw new XenonException('api_key key is absent in configuration');
        }

        if (!array_key_exists('client_id', $this->senderObject->getConfig())) {
            throw new XenonException('client_id key is absent in configuration');
        }

        if (strlen($this->senderObject->getMobile()) > 11 || strlen($this->senderObject->getMobile()) < 11) {
            throw new XenonException('Invalid mobile number. It should be 11 digit');
        }
        if (empty($this->senderObject->getMessage())) {
            throw new XenonException('Message should not be empty');
        }
    }

    /**
     * @param $result
     * @param $data
     * @return array
     */
    public function generateReport($result, $data): array
    {
        return [
            'status' => 'response',
            'response' => $result,
            'provider' => self::class,
            'send_time' => date('Y-m-d H:i:s'),
            'mobile' => $data['MobileNumbers'],
            'message' => $data['Message']
        ];
    }
}

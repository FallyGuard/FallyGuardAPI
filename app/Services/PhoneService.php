<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PhoneService
{
    public $basic;
    public $client;

    public function __construct()
    {
        $this->basic = new \Vonage\Client\Credentials\Basic("56218adf", "oZO5ohyjP0wRWZgN");
        $this->client = new \Vonage\Client($this->basic);
    }


    public function sendSMS($patient, $user)
    {
        $this->client->sms()->send(
            new \Vonage\SMS\Message\SMS($user->phone, "FallyGuard", "{$patient->name} has fallen, please check on them.")
        );
    }

    public function sendToWhatsapp($patient, $user)
    {
        Http::withBasicAuth('56218adf', 'oZO5ohyjP0wRWZgN')
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->post('https://messages-sandbox.nexmo.com/v1/messages', [
                'from' => '14157386102',
                'to' => $user->phone,
                'message_type' => 'text',
                'text' => "{$patient->name} has fallen, please check on them.",
                'channel' => 'whatsapp',
            ]);
    }
}
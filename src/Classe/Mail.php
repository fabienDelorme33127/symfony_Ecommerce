<?php

namespace App\Classe;

use Mailjet\Client;
use Mailjet\Resources;

class Mail
{
    private $api_key = 'e858c51d032c03ce26af33c10af2da66';
    private $api_key_secret = 'f9899b2597684e085cc15176f88ce93e';

    public function send($to_email, $to_name, $subject, $content)
    {
         $mj = new \Mailjet\Client($this->api_key,$this->api_key_secret,true,['version' => 'v3.1']);

         $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => "fabdelorme33@outlook.fr",
                        'Name' => "Me"
                    ],
                    'To' => [
                        [
                            'Email' => $to_email,
                            'Name' => $to_name
                        ]
                    ],
                    'Subject' => $subject,
                    'TextPart' => "Greetings from Mailjet!",
                    'HTMLPart' => $content
                ]
            ]
        ]; 

        $response = $mj->post(Resources::$Email, ['body' => $body]);
        $response->success() && dd($response->getData());

       
    }
}
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SendMail
{
    public function sendEmail($language, $code, $email, $data = null)
    {
        $response = Http::withOptions([
            'verify' => false
        ])->post(config('endpoint.mail').'/send-email', [
            'language' => $language,
            'code' => $code,
            'email' => $email,
            'data' => $data
        ]);
        return $response->json();
        // $body = array();        
        // $client = new Client(['verify' => false]);
        // $urlMail = env('MAIL_API_X3', 'https://mails.x3english.com/api');
        // $email = trim(str_replace('\u00a0','',$email));
        // $response = $client->post($urlMail.'/send-email', [
        //     'form_params' => array(
        //         'language' => $language,
        //         'code' => $code,
        //         'email' => $email,
        //         'data' => $data
        //     ),
        // ]);
        // $body = $response->getBody()->getContents();
        // $body = json_decode($body, TRUE);            
        // //}
        // return $body;
    }
}
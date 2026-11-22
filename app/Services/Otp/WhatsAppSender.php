<?php
namespace App\Services\Otp;

class WhatsAppSender implements OtpSender
{
    public function send(string $destination, string $code, string $purpose): void
    {
        \Http::withToken(config('services.whatsapp.token'))->post('https://graph.facebook.com/v18.0/'.config('services.whatsapp.phone_id').'/messages', [
            'messaging_product'=>'whatsapp','to'=>$destination,'type'=>'template',
            'template'=>['name'=>config('services.whatsapp.template'),'language'=>['code'=>config('services.whatsapp.language')],'components'=>[['type'=>'body','parameters'=>[['type'=>'text','text'=>$code]]]]],
        ]);
    }
}
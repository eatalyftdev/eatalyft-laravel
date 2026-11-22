<?php
namespace App\Services;
use App\Models\OtpCode;
use App\Services\Otp\LogSender;
use App\Services\Otp\EmailSender;
use App\Services\Otp\WhatsAppSender;
use App\Services\Otp\SmsSender;
use Illuminate\Support\Facades\App;

class OtpService {
    public function request(string $channel, string $destination, string $purpose, ?string $ip=null, ?string $deviceId=null): array {
        $limit = (int) config('otp.per_destination_limit', 3);
        $window = now()->subMinutes(10);
        $recent = OtpCode::where('destination_hash', hash('sha256',$destination))->where('created_at','>=',$window)->count();
        if ($recent >= $limit) throw new \RuntimeException('Too many requests');
        $ipRecent = OtpCode::where('ip',$ip)->where('created_at','>=',$window)->count();
        if ($ipRecent >= (int)config('otp.per_ip_limit', 10)) throw new \RuntimeException('Too many requests');
        $last = OtpCode::where('destination_hash', hash('sha256',$destination))->latest()->first();
        if ($last && $last->created_at->diffInSeconds(now()) < (int)config('otp.resend_cooldown',60)) throw new \RuntimeException('Resend cooldown');
        $code = str_pad((string)random_int(0, 999999), (int)config('otp.digits',6), '0', STR_PAD_LEFT);
        $otp = OtpCode::create([
            'channel'=>$channel,'destination_hash'=>hash('sha256',$destination),
            'destination_encrypted'=>encrypt($destination),'code_hash'=>hash_hmac('sha256',$code,config('app.key')),
            'purpose'=>$purpose,'max_attempts'=>(int)config('otp.max_attempts',5),
            'expires_at'=>now()->addMinutes((int)config('otp.expiry_minutes',5)),'ip'=>$ip,'device_id'=>$deviceId,
        ]);
        
        \Log::channel('single')->info('OtpService::request', [
            'show_code_on_screen' => config('otp.show_code_on_screen'),
            'code' => $code,
        ]);
        
        $this->sendViaChannel($channel, $destination, $code, $purpose);
        return ['otp'=>$otp,'code'=>config('otp.show_code_on_screen') ? $code : null];
    }

    public function verify(string $destination, string $code, string $purpose): bool {
        $otp = OtpCode::where('destination_hash',hash('sha256',$destination))->where('purpose',$purpose)->whereNull('consumed_at')->latest()->first();
        if (!$otp || $otp->expires_at->isPast()) return false;
        if ($otp->attempts >= $otp->max_attempts) return false;
        $otp->increment('attempts');
        if (!hash_equals($otp->code_hash, hash_hmac('sha256',$code,config('app.key')))) return false;
        $otp->update(['consumed_at'=>now(),'destination_encrypted'=>null]);
        return true;
    }

    protected function sendViaChannel(string $channel, string $destination, string $code, string $purpose): void {
        $senderClass = match($channel) {
            'email' => config('otp.email_driver') === 'mailgun' ? EmailSender::class : LogSender::class,
            'sms' => config('otp.sms_driver') === 'twilio' ? SmsSender::class : LogSender::class,
            'whatsapp' => config('otp.whatsapp_driver') === 'meta' ? WhatsAppSender::class : LogSender::class,
            default => LogSender::class,
        };
        
        $sender = App::make($senderClass);
        $sender->send($destination, $code, $purpose);
    }
}

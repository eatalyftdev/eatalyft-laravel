<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class TrustCloudflareProxies
{
    private array $cfRanges = [
        '173.245.48.0/20','103.21.244.0/22','103.22.200.0/22','103.31.4.0/22',
        '141.101.64.0/18','108.162.192.0/18','190.93.240.0/20','188.114.96.0/20',
        '197.234.240.0/22','198.41.128.0/17','162.158.0.0/15','104.16.0.0/13',
        '104.24.0.0/14','172.64.0.0/13','131.0.72.0/22',
    ];
    public function handle(Request $request, Closure $next) {
        $remoteIp = $request->server('REMOTE_ADDR');
        if ($this->isCloudflare($remoteIp)) {
            $cfIp = $request->header('CF-Connecting-IP');
            if ($cfIp && filter_var($cfIp, FILTER_VALIDATE_IP)) {
                $request->server->set('REMOTE_ADDR', $cfIp);
            }
        }
        return $next($request);
    }
    private function isCloudflare(string $ip): bool {
        foreach ($this->cfRanges as $range) {
            if ($this->ipInCidr($ip, $range)) return true;
        }
        return false;
    }
    private function ipInCidr(string $ip, string $cidr): bool {
        [$subnet, $bits] = explode('/', $cidr);
        $ipLong = ip2long($ip); $subnetLong = ip2long($subnet);
        if ($ipLong === false || $subnetLong === false) return false;
        $mask = -1 << (32 - (int)$bits);
        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}

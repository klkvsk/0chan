<?php

class RequestUtils {

    public static function getRealIp(HttpRequest $request, $filterLocal = true) {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $headerParam) {
            if ($request->hasServerVar($headerParam)) {

                $param = $request->getServerVar($headerParam);
                $values = preg_split('/[^0-9a-f\:\.]+/', $param);

                foreach ($values as $value) {
                    $isIPv4 = filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
                    $isIPv6 = filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;

                    if (!$isIPv4 && !$isIPv6) {
                        continue;
                    }

                    if ($filterLocal && $isIPv4) {
                        if (ip2long($value) == 0)
                            continue;
                        if (self::matchCidr($value, '10.0.0.0/8'))
                            continue;
                        if (self::matchCidr($value, '172.16.0.0/12'))
                            continue;
                    }

                    return $value;
                }
            }
        }

        return null;
    }

    public static function getRealIpHash(HttpRequest $request)
    {
        $ip = self::getRealIp($request);
        return self::hashIp($ip);
    }

    public static function hashIp($ip)
    {
        if (!$ip) return null;
        return sha1(sha1($ip) . SALT . strrev(md5($ip)));
    }

    public static function matchCidr($ip, $cidr) {
        $ipAddr = ip2long($ip);

        list ($net, $mask) = explode("/", $cidr);
        $netAddr = ip2long($net);
        $netMask = ~((1 << (32 - $mask)) - 1);

        return ($ipAddr & $netMask) == $netAddr;
    }
}
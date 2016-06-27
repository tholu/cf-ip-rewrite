<?php

namespace CloudFlare;

require_once 'vendor/autoload.php';

class IpRewrite
{
    protected static $is_loaded = false;
    protected static $original_ip = null;
    protected static $rewritten_ip = null;

    // Found at https://www.cloudflare.com/ips/
    protected static $cf_ipv4 = array(
        '199.27.128.0/21',
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/12',
        '172.64.0.0/13',
        '131.0.72.0/22',
    );

    protected static $cf_ipv6 = array(
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
    );

    // Helper method for testing, should not be used in production
    public static function reset()
    {
        self::$is_loaded = false;
        self::$original_ip = null;
        self::$rewritten_ip = null;
    }

    // Returns boolean
    public static function isCloudFlare()
    {
        if (!isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return false;
        }

        return true;
    }

    // Returns IP Address or null on error
    public static function getOriginalIP()
    {
        // If $original_ip is not set, return the REMOTE_ADDR
        if (!isset(self::$original_ip)) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return self::$original_ip;
    }

    // Returns IP Address or null on error
    public static function getRewrittenIP()
    {
        self::rewrite();

        return self::$rewritten_ip;
    }

    /*
    * Protected function to handle the rewriting of CloudFlare IP Addresses to end-user IP Addresses
    * 
    * ** NOTE: This function will ultimately rewrite $_SERVER["REMOTE_ADDR"] if the site is on CloudFlare
    */
    protected static function rewrite()
    {
        // only should be run once per page load
        if (self::$is_loaded) {
            return;
        }
        self::$is_loaded = true;

        $is_cf = self::isCloudFlare();
        if (!$is_cf) {
            return;
        }

        // Store original remote address in $original_ip
        if (!isset($_SERVER['REMOTE_ADDR'])) {
            return;
        }
        self::$original_ip = $_SERVER['REMOTE_ADDR'];

        // Process original_ip if on cloudflare
        $ip_ranges = self::$cf_ipv4;
        if (IpUtils::isIpv6(self::$original_ip)) {
            $ip_ranges = self::$cf_ipv6;
        }

        foreach ($ip_ranges as $range) {
            if (IpUtils::checkIp(self::$original_ip, $range)) {
                self::$rewritten_ip = $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
                break;
            }
        }
    }
}

<?php
// Geo-based region detection and WC price suffix.
const LL_EU_COUNTRY_CODES = [
    'AD', 'AL', 'AT', 'AX', 'BA', 'BE', 'BG', 'BY', 'CH', 'CY', 'CZ', 'DE',
    'DK', 'EE', 'ES', 'FI', 'FO', 'FR', 'GB', 'GG', 'GI', 'GR', 'HR', 'HU',
    'IE', 'IM', 'IS', 'IT', 'JE', 'LI', 'LT', 'LU', 'LV', 'MC', 'MD', 'ME',
    'MK', 'MT', 'NL', 'NO', 'PL', 'PT', 'RO', 'RS', 'SE', 'SI', 'SJ', 'SK',
    'SM', 'UA', 'VA', 'XK',
];

const LL_REGION_QUERY_MAP = [
    'usa'    => 'USA',
    'europe' => 'Europe',
    'canada' => 'Canada',
    'oz-nz'  => 'Australia',
];

// A region picked in the switcher (?region=…) is remembered in a cookie,
// written on init — before any output — so it survives across requests.
add_action('init', function () {
    if (!isset($_GET['region'])) {
        return;
    }
    $resolved = LL_REGION_QUERY_MAP[sanitize_key(wp_unslash($_GET['region']))] ?? 'Other';
    setcookie('ll_region', $resolved, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
    $_COOKIE['ll_region'] = $resolved; // visible within this same request
});

if (!function_exists('get_user_geo_region')) {
    /**
     * Visitor region: the chosen one (cookie), else geolocated from the IP.
     * Memoized — the footer and every price suffix ask for it.
     */
    function get_user_geo_region()
    {
        static $region = null;
        if ($region !== null) {
            return $region;
        }

        $chosen = isset($_COOKIE['ll_region']) ? wp_unslash($_COOKIE['ll_region']) : '';
        if (in_array($chosen, array_merge(LL_REGION_QUERY_MAP, ['Other']), true)) {
            return $region = $chosen;
        }

        $country = '';
        if (class_exists('WC_Geolocation')) {
            $geo     = WC_Geolocation::geolocate_ip(WC_Geolocation::get_ip_address());
            $country = $geo['country'] ?? '';
        }

        if ($country === 'US') {
            $region = 'USA';
        } elseif ($country === 'CA') {
            $region = 'Canada';
        } elseif (in_array($country, ['AU', 'NZ'], true)) {
            $region = 'Australia';
        } elseif (in_array($country, LL_EU_COUNTRY_CODES, true)) {
            $region = 'Europe';
        } else {
            $region = 'Other';
        }
        return $region;
    }
}

// Tax/GST suffix on WooCommerce prices for regions that expect it.
add_filter('woocommerce_get_price_suffix', 'LL_geo_price_suffix', 99, 4);
function LL_geo_price_suffix($html, $product, $price, $qty)
{
    $suffixes = [
        'USA'       => ' inc. tax',
        'Australia' => ' inc. GST',
    ];
    $region = get_user_geo_region();
    if (isset($suffixes[$region])) {
        $html .= '<small class="woocommerce-price-suffix">' . esc_html($suffixes[$region]) . '</small>';
    }
    return $html;
}

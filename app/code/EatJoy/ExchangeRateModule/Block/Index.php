<?php

namespace EatJoy\ExchangeRateModule\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\App\Cache\Type\Config as CacheType;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\CacheInterface;

class Index extends Template
{
    protected $cache;
    protected $cache_key = 'exchange_rate_data';
    protected $cache_lifetime = 300; // 5 phút
    protected $scopeConfig;
    protected $curl;

    public function __construct(
        Template\Context     $context,
        ScopeConfigInterface $scopeConfig,
        Curl                 $curl,
        CacheInterface       $cache,
        array                $data = []
    )
    {
        parent::__construct($context, $data);
        $this->scopeConfig = $scopeConfig;
        $this->curl = $curl;
        $this->cache = $cache;
    }

    public function getExchangeRates()
    {
        if ($cached = $this->loadFromCache()) {
            return $cached;
        }

        $apiUrl = 'https://portal.vietcombank.com.vn/Usercontrols/TVPortal.TyGia/pXML.aspx?b=68';
        $this->curl->get($apiUrl);
        $response = $this->curl->getBody();
        $xml = simplexml_load_string($response);

        $rates = [];
        foreach ($xml->Exrate as $rate) {
            $rates[] = [
                'currency_code' => (string)$rate['CurrencyCode'],
                'currency_name' => (string)$rate['CurrencyName'],
                'buy' => (string)$rate['Buy'],
                'transfer' => (string)$rate['Transfer'],
                'sell' => (string)$rate['Sell']
            ];
        }

        $data = [
            'datetime' => (string)$xml->DateTime,
            'rates' => $rates,
            'source' => (string)$xml->Source
        ];

        $this->saveToCache($data);
        return $data;
    }

    protected function loadFromCache()
    {
        $cached = $this->cache->load($this->cache_key);
        return $cached ? unserialize($cached) : false;
    }

    protected function saveToCache($data)
    {
        $this->cache->save(serialize($data), $this->cache_key, [CacheType::CACHE_TAG], $this->cache_lifetime);
    }

    public function getFormattedDateTime($datetime)
    {
        return date("d/m/Y H:i:s", strtotime($datetime));
    }

    /**
     * Convert amount from VND to the target currency based on the specified rate type.
     *
     * @param float $amount Amount in VND to convert.
     * @param string $currencyCode The currency code to convert to.
     * @param string $rateType The rate type to use for conversion (buy, transfer, or sell).
     * @return float|null Converted amount in the target currency or null if rate is not available.
     */
    public function convertCurrency($amount, $currencyCode, $rateType = 'buy')
    {
        $exchangeRates = $this->getExchangeRates();
        foreach ($exchangeRates['rates'] as $rate) {
            if ($rate['currency_code'] === $currencyCode && isset($rate[$rateType]) && $rate[$rateType] !== '-') {
                $rateValue = (float)$rate[$rateType];
                if ($rateValue > 0) {
                    return $amount / $rateValue;
                }
            }
        }
        return null; // Trả về null nếu không có tỷ giá phù hợp
    }
}

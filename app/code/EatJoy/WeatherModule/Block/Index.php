<?php

namespace EatJoy\WeatherModule\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\HTTP\Client\Curl;
use DateTime;
use DateTimeZone;

class Index extends Template
{
    protected $curl;
    protected $apiKey = 'd7571b7b7aa49ef8bcfd29d1696ad3fb';

    protected $labels = [
        'en' => [
            'city_label' => 'Select city:',
            'lang_label' => 'Select language:',
            'feels_like' => 'Feels like',
            'humidity' => 'Humidity',
            'wind_speed' => 'Wind speed',
            'pressure' => 'Pressure',
            'uv_index' => 'UV',
            'visibility' => 'Visibility',
            'hourly_forecast' => 'Hourly forecast',
            'daily_forecast' => '6-day forecast',
            'precipitation' => 'Precipitation',
            'no_precipitation' => 'No precipitation',
            'cities' => [
                'Hanoi' => 'Hanoi',
                'Thanh pho Ho Chi Minh' => 'Ho Chi Minh City'
            ]
        ],
        'vi' => [
            'city_label' => 'Chọn thành phố:',
            'lang_label' => 'Chọn ngôn ngữ:',
            'feels_like' => 'Cảm giác như',
            'humidity' => 'Độ ẩm',
            'wind_speed' => 'Tốc độ gió',
            'pressure' => 'Áp suất',
            'uv_index' => 'Chỉ số UV',
            'visibility' => 'Tầm nhìn',
            'hourly_forecast' => 'Dự báo theo giờ',
            'daily_forecast' => 'Dự báo 6 ngày',
            'precipitation' => 'Lượng mưa',
            'no_precipitation' => 'Không mưa',
            'cities' => [
                'Hanoi' => 'Hà Nội',
                'Thanh pho Ho Chi Minh' => 'Thành phố Hồ Chí Minh'
            ]
        ]
    ];

    public function __construct(
        Template\Context $context,
        Curl $curl,
        array $data = []
    ) {
        $this->curl = $curl;
        parent::__construct($context, $data);
    }

    public function getWeatherData($city = 'Hanoi', $lang = 'en')
    {
        try {
            $city = urlencode($city);

            // Get current weather
            $currentUrl = "http://api.openweathermap.org/data/2.5/weather?q={$city}&appid={$this->apiKey}&units=metric&lang={$lang}";
            $this->curl->get($currentUrl);
            $currentData = json_decode($this->curl->getBody(), true);

            if (!empty($currentData) && $currentData['cod'] == 200) {
                // Get forecast data
                $lat = $currentData['coord']['lat'];
                $lon = $currentData['coord']['lon'];
                $forecastUrl = "http://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&appid={$this->apiKey}&units=metric&lang={$lang}";

                $this->curl->get($forecastUrl);
                $forecastData = json_decode($this->curl->getBody(), true);

                // Format data to match your template
                $weatherData = [
                    'city_name' => $this->getCityLabel($city, $lang),
                    'current' => [
                        'temp' => $currentData['main']['temp'],
                        'feels_like' => $currentData['main']['feels_like'],
                        'pressure' => $currentData['main']['pressure'],
                        'humidity' => $currentData['main']['humidity'],
                        'wind_speed' => $currentData['wind']['speed'],
                        'visibility' => $currentData['visibility'],
                        'weather' => $currentData['weather'],
                        'uvi' => 0 // Note: UV index not available in free API
                    ],
                    'hourly' => [],
                    'daily' => []
                ];

                // Format hourly forecast (next 24 hours)
                for ($i = 0; $i < 8; $i++) {
                    if (isset($forecastData['list'][$i])) {
                        $weatherData['hourly'][] = [
                            'dt' => strtotime($forecastData['list'][$i]['dt_txt']),
                            'temp' => $forecastData['list'][$i]['main']['temp'],
                            'pop' => $forecastData['list'][$i]['pop'],
                            'weather' => $forecastData['list'][$i]['weather']
                        ];
                    }
                }

                // Format daily forecast (group by day)
                $dailyForecasts = [];
                $currentDate = new DateTime();
                $currentDate->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));
                $today = $currentDate->format('Y-m-d');

                // Add current day's weather as first day
                $dailyForecasts[$today] = [
                    'dt' => time(),
                    'temp' => [
                        'min' => $currentData['main']['temp'],
                        'max' => $currentData['main']['temp']
                    ],
                    'weather' => $currentData['weather']
                ];

                // Process forecast data
                foreach ($forecastData['list'] as $forecast) {
                    $date = (new DateTime($forecast['dt_txt']))
                        ->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))
                        ->format('Y-m-d');

                    if (!isset($dailyForecasts[$date])) {
                        $dailyForecasts[$date] = [
                            'dt' => strtotime($forecast['dt_txt']),
                            'temp' => [
                                'min' => $forecast['main']['temp_min'],
                                'max' => $forecast['main']['temp_max']
                            ],
                            'weather' => $forecast['weather']
                        ];
                    } else {
                        $dailyForecasts[$date]['temp']['min'] = min($dailyForecasts[$date]['temp']['min'], $forecast['main']['temp_min']);
                        $dailyForecasts[$date]['temp']['max'] = max($dailyForecasts[$date]['temp']['max'], $forecast['main']['temp_max']);
                    }
                }

                // Ensure we only show next 5 days (plus today)
                $weatherData['daily'] = array_slice(array_values($dailyForecasts), 0, 6);

                return $weatherData;
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function formatDateTime($timestamp, $format = 'H:i')
    {
        $date = new DateTime();
        $date->setTimestamp($timestamp);
        $date->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));
        return $date->format($format);
    }

    public function getLabel($key, $lang)
    {
        return $this->labels[$lang][$key] ?? $key;
    }

    public function getCityLabel($cityKey, $lang)
    {
        return $this->labels[$lang]['cities'][$cityKey] ?? $cityKey;
    }

    public function getAvailableCities()
    {
        return array_keys($this->labels['en']['cities']);
    }
}

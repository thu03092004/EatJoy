<?php

namespace EatJoy\BusinessModule\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\HTTP\Client\Curl;

class Index extends Template
{
    protected $curl;

    public function __construct(
        Context $context,
        Curl $curl,
        array $data = []
    ) {
        $this->curl = $curl;
        parent::__construct($context, $data);
    }

    public function getRssData()
    {
        // URL 
        $url = 'https://vnexpress.net/rss/kinh-doanh.rss';

        $this->curl->get($url);
        $response = $this->curl->getBody();

        $rss = simplexml_load_string($response);

        $items = [];

        foreach ($rss->channel->item as $item) {
            $items[] = [
                'title' => (string) $item->title,
                'description' => (string) $item->description,
                'link' => (string) $item->link,
                'pubDate' => (string) $item->pubDate,
                'image' => (string) $item->enclosure['url']
            ];
        }

        return $items;
    }

    public function getPaginatedItems($items)
    {
        $itemsPerPage = 7;
        $totalItems = count($items);
        $totalPages = ceil($totalItems / $itemsPerPage);

        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($currentPage < 1) $currentPage = 1;

        $startIndex = ($currentPage - 1) * $itemsPerPage;
        $endIndex = min($startIndex + $itemsPerPage, $totalItems);

        $paginatedItems = array_slice($items, $startIndex, $itemsPerPage);

        return [
            'items' => $paginatedItems,
            'totalPages' => $totalPages,
            'currentPage' => $currentPage
        ];
    }
}

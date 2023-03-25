<?php

namespace App\Helper;

use App\Http\Client\GuzzleWrapper;
use App\Logging\MonologCustomizer;
use DOMDocument;
use DOMElement;
use Log;

class  SiteCrawlerHelper
{
    private $mainUrl;
    private $depth;
    private $host;
    private $seen = [];
    private $page = [];
    private $crawlingInProgress = false;

    /**
     * @param $mainUrl
     * @param $depth
     */
    public function __construct($mainUrl, $depth)
    {
        $this->mainUrl = $mainUrl;
        $this->depth = $depth;//#pagesToCrawl
        $parse = parse_url($mainUrl);
        $this->host = $parse['host'] ?? $parse['path'];
    }

    /**
     * @param string $content
     * @param string $url
     * @return void
     */
    private function ProcessPage(string $content, string $url): void
    {
        $dom = new DOMDocument('1.0');
        @$dom->loadHTML($content);

        $this->extractImages($dom, $url);
        $this->extractTitle($dom, $url);
        $this->extractWords($dom, $url);
        $this->extractAnchors($dom, $url);
    }

    /**
     * @param DOMDocument $dom
     * @param string $url
     * @return void
     */
    private function extractImages(DOMDocument $dom, string $url): void
    {
        $images = $dom->getElementsByTagName('img');
        foreach ($images as $element) {
            $this->page[$url]['images'][] = $element->getAttribute('src');
        }
        if(!empty($this->page[$url]['images'])){
            $this->pageImagesCleaner($url);
        }
    }

    /**
     * @param string $url
     * @return void
     */
    private function pageImagesCleaner(string $url): void
    {
        foreach ($this->page[$url]['images'] as $index => $image) {
            $image = preg_replace('/(?:\?|\&)(?<key>[w,q]+)(?:\=|\&?)(?<value>[0-9+,.-]*)/', '', $image);
            $this->page[$url]['images'][$index] = $image;
        }
    }

    /**
     * @param DOMDocument $dom
     * @param string $url
     * @return void
     */
    private function extractTitle(DOMDocument $dom, string $url): void
    {
        $titleText = '';
        $title = $dom->getElementsByTagName('title');
        if ($title->length > 0) {
            $titleText = $title->item(0)->textContent;
        }
        $this->page[$url]['title'] = $titleText;
    }

    /**
     * @param DOMDocument $dom
     * @param string $url
     * @return void
     */
    private function extractWords(DOMDocument $dom, string $url): void
    {
        //needs more work
        $nodeList = $dom->getElementsByTagName('body');
        foreach ($nodeList as $node) {
            if ($node->hasChildNodes()) {
                $this->getDOMNodeText($node, $url);
            }
        }
        $this->pageWordsCleaner($url);
    }

    /**
     * @param DOMDocument $dom
     * @param string $url
     * @return void
     */
    private function extractAnchors(DOMDocument $dom, string $url): void
    {
        $anchors = $dom->getElementsByTagName('a');
        foreach ($anchors as $element) {
            if (!empty($element->getAttribute('href')) && $this->filterLinks($element->getAttribute('href'))) {
                $this->page[$url]['links'][] = $element->getAttribute('href');
                if (!$this->isExternal($element->getAttribute('href'), $this->mainUrl) && $this->depth > 0) {
                    $this->crawlPage($this->buildSubPageUrl($element->getAttribute('href')), $this->depth);
                }
            }
        }
    }

    /**
     * @param string $url
     * @return array
     */
    private function getContent(string $url): array
    {
        //processing time handling https://stackoverflow.com/questions/31341254/guzzle-6-get-request-total-time
        $raw = GuzzleWrapper::get($url, [], ['Host' => $url]);
        $contents = isset($raw['body']) ? $raw['body']->getContents() : $raw['errorBody'];
        return [$contents, $raw['response'], $raw['execTime']];
    }

    /**
     * @param string $url
     * @param int $depth
     * @return bool
     */
    private function isValid(string $url, int $depth)
    {
        if ($depth === 0 || isset($this->seen[$url])) {
            return false;
        }
        return true;
    }

    /**
     * @param string $url
     * @param int $depth
     * @return void
     */
    private function crawlPage(string $url, int $depth): void
    {
        if (!$this->isValid($url, $depth)) {
            return;
        }
        $this->depth -= 1;
        $this->crawlingInProgress = true;
        $this->seen[$url] = true;

        list($content, $httpcode, $time) = $this->getContent($url);

        $this->page[$url]['responseCode'] = $httpcode;
        $this->page[$url]['execTime'] = $time;

        if ($httpcode == 200) {
            $this->ProcessPage($content, $url);
        }

        $this->crawlingInProgress = false;
    }

    /**
     * @return array
     */
    public function run(): array
    {
        $this->crawlPage($this->mainUrl, $this->depth);
        do {
            sleep(1);
        } while ($this->depth > 0 || $this->crawlingInProgress == true);
        return $this->page;
    }

    /**
     * @param string $url
     * @param string $mainUrl
     * @return bool
     */
    public function isExternal(string $url, string $mainUrl): bool
    {
        $components = parse_url($url);
        if (empty($components['host'])) return false;
        if (strcasecmp($components['host'], $mainUrl) === 0) return false;
        return strrpos(strtolower($components['host']), '.' . $mainUrl) !== strlen($components['host']) - strlen('.' . $mainUrl);
    }

    /**
     * @param DOMElement $domNode
     * @param string $url
     * @return void
     */
    private function getDOMNodeText(DOMElement $domNode, string $url): void
    {
        foreach ($domNode->childNodes as $node) {
            if (!empty($node->textContent)) {
                $this->page[$url]['textBlocks'][] = $node->textContent;
            }

            if ($node->hasChildNodes()) {
                $this->getDOMNodeText($node, $url);
            }
        }
    }

    /**
     * @param string $url
     * @return bool
     */
    private function filterLinks(string $url): bool
    {
        if (strpos($url, "#") === 0) {
            return false;
        }
        if ($url === "/") {
            return false;
        }
        return true;
    }

    /**
     * @param string $subUrl
     * @return string
     */
    private function buildSubPageUrl(string $subUrl): string
    {
        return $this->mainUrl . $subUrl;
    }

    /**
     * @param string $url
     * @return void
     */
    private function pageWordsCleaner(string $url): void
    {
        $currentText = '';
        foreach ($this->page[$url]['textBlocks'] as $index => $text) {
            if ($currentText !== $text) {
                $currentText = $text;
            } else if ($currentText === $text) {
                unset($this->page[$url]['textBlocks'][$index]);
            }
            if ($this->isJson($text)) {
                unset($this->page[$url]['textBlocks'][$index]);
            }
        }
    }

    /**
     * @param string $string
     * @return bool
     */
    private function isJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}



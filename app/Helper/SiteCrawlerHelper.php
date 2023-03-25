<?php

namespace App\Helper;

use App\Http\Client\GuzzleWrapper;
use App\Logging\MonologCustomizer;
use Log;

class  SiteCrawlerHelper
{
    private $mainUrl;
    private $depth;
    private $host;
    private $seen = [];
    private $page = [];
    private $crawlingInProgress = false;

    public function __construct($mainUrl, $depth = 5)
    {
        $this->mainUrl = $mainUrl;
        $this->depth = $depth;
        $parse = parse_url($mainUrl);
        $this->host = $parse['host'] ?? $parse['path'];//to be fixed
    }

    private function ProcessPage($content, $url, $depth)
    {
        $dom = new \DOMDocument('1.0');
        @$dom->loadHTML($content);

        //extract images
        $imgs = $dom->getElementsByTagName('img');
        foreach ($imgs as $element) {
            $this->page[$url]['imgs'][] = $element->getAttribute('src');
        }
        $this->pageImgsCleaner($url);

        //extract title
        $titleText = '';
        $title = $dom->getElementsByTagName('title');
        if ($title->length > 0) {
            $titleText = $title->item(0)->textContent;
        }
        $this->page[$url]['title'] = $titleText;

        //extract word from page body
        $nodeList = $dom->getElementsByTagName('body');
        foreach ($nodeList as $node) {
            if ($node->hasChildNodes()) {
                $this->getDOMNodeText($node, $url);
            }
        }
        $this->pageWordsCleaner($url);

        // process anchors
        $anchors = $dom->getElementsByTagName('a');
        foreach ($anchors as $element) {
            if (!empty($element->getAttribute('href')) && $this->filterLinks($element->getAttribute('href'))) {
                $this->page[$url]['links'][] = $element->getAttribute('href');
                if (!$this->isExternal($element->getAttribute('href'), $this->mainUrl) && $this->depth > 0) {
                    $this->depth -= 1;
                    $this->crawlPage($this->buildSubPageUrl($element->getAttribute('href')), $this->depth);
                }
            }
        }
    }

    private function getContent($url)
    {
        $raw = GuzzleWrapper::get($url, [], ['Host' => $url]);
        //processing time handling https://stackoverflow.com/questions/31341254/guzzle-6-get-request-total-time
        $contents = isset($raw['body']) ? $raw['body']->getContents() : $raw['errorBody'];
        return [$contents, $raw['response'], $raw['execTime']];
    }

    protected function printResult($url, $depth, $httpcode, $time)
    {
        ob_end_flush();
        $currentDepth = $depth;
        $count = count($this->seen);
        echo "N::$count,CODE::$httpcode,TIME::$time,DEPTH::$currentDepth URL::$url <br>";
        ob_start();
        flush();
    }

    private function isValid($url, $depth)
    {
        if (strpos($url, $this->host) === false || $depth === 0 || isset($this->seen[$url])) {
            return false;
        }
        return true;
    }

    private function crawlPage($url, $depth)
    {
        if (!$this->isValid($url, $depth)) {
            return;
        }
        $this->crawlingInProgress = true;
        // add to the seen URL
        $this->seen[$url] = true;
        // get Content and Return Code
        list($content, $httpcode, $time) = $this->getContent($url);
        $this->page[$url]['responseCode'] = $httpcode;
        $this->page[$url]['execTime'] = $time;
        // print Result for current Page
        // $this->printResult($url, $depth, $httpcode, $time);

        if ($httpcode == 200) {
            // process subPages
            $this->ProcessPage($content, $url, $depth);
        }
        $this->crawlingInProgress = false;
    }

    public function run()
    {
        $this->crawlPage($this->mainUrl, $this->depth);
        do {
            sleep(1);
        } while ($this->depth > 0 || $this->crawlingInProgress == true);
        return $this->page;
    }

    public function isExternal($url, $mainUrl)
    {
        $components = parse_url($url);
        if (empty($components['host'])) return false;  // we will treat url like '/relative.php' as relative
        if (strcasecmp($components['host'], $mainUrl) === 0) return false; // url host looks exactly like the local host
        return strrpos(strtolower($components['host']), '.' . $mainUrl) !== strlen($components['host']) - strlen('.' . $mainUrl); // check if the url host is a subdomain
    }

    private function getDOMNodeText(\DOMElement $domNode, string $url)
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

    private function filterLinks($url)
    {
        if (strpos($url, "#") === 0) {
            return false;
        }
        if ($url === "/") {
            return false;
        }
        return true;
    }

    private function buildSubPageUrl($subUrl)
    {
        $hash = rand();
        return 'https://' . $this->mainUrl . $subUrl . '?hash=' . $hash;
    }

    private function pageWordsCleaner(string $url)
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

    private function pageImgsCleaner(string $url)
    {
        foreach ($this->page[$url]['imgs'] as $index => $img) {
            $img = preg_replace('/(?:\?|\&)(?<key>[w,q]+)(?:\=|\&?)(?<value>[0-9+,.-]*)/', '', $img);
            $this->page[$url]['imgs'][$index] = $img;
        }
    }

    private function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}



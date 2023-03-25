<?php

namespace App\Models\Crawler;

use App\Helper\SiteCrawlerHelper;

class SiteReports
{
    private $siteEntryURL = '';
    private $numberOfPagesToCrawl;
    private $siteCrawlerHelper;

    private $pagesData = [];

    public function __construct(string $siteEntryURL, $numberOfPagesToCrawl)//here
    {
        $this->siteEntryURL = $siteEntryURL;
        $this->numberOfPagesToCrawl = $numberOfPagesToCrawl;
        $this->siteCrawlerHelper = new SiteCrawlerHelper($siteEntryURL, $this->numberOfPagesToCrawl);
        $this->pagesData = $this->siteCrawlerHelper->run();
    }

    public function getNumberOfPagesCrawled()
    {
        $crawledPages = 0;
        foreach ($this->pagesData as $pageData) {
            if ($pageData['responseCode'] == 200) {
                $crawledPages++;
            }
        }
        return $crawledPages;
    }

    public function getNumberOfUniqueImages()
    {
        $totalImages = [];
        foreach ($this->pagesData as $pageData) {
            if (isset($pageData['imgs'])) {
                $totalImages[] = array_merge($totalImages, $pageData['imgs']);
            }
        }
        return count(array_unique($totalImages));
    }

    public function getNumberOfUniqueInternalLinks()
    {
        $totalUniqueInternalLinks = [];
        $totalUniqueExternalLinks = [];
        foreach ($this->pagesData as $pageData) {
            if (isset($pageData['links'])) {
                foreach ($pageData['links'] as $link) {
                    if (!$this->siteCrawlerHelper->isExternal($link, $this->siteEntryURL)) {
                        $totalUniqueInternalLinks[] = $link;
                    }
                }
            }
        }
        return count(array_unique($totalUniqueInternalLinks));
    }

    public function getNumberOfUniqueExternalLinks()
    {
        $totalUniqueExternalLinks = [];
        foreach ($this->pagesData as $pageData) {
            if (isset($pageData['links'])) {
                foreach ($pageData['links'] as $link) {
                    if ($this->siteCrawlerHelper->isExternal($link, $this->siteEntryURL)) {
                        $totalUniqueExternalLinks[] = $link;
                    }
                }
            }
        }
        return count(array_unique($totalUniqueExternalLinks));
    }

    public function getAveragePageLoadInSeconds(int $numberOfPagesCrawled)
    {
        $totalPagesLoadInSeconds = 0;
        foreach ($this->pagesData as $pageData) {
            if ($pageData['responseCode'] == 200) {
                $totalPagesLoadInSeconds += $pageData['execTime'];
            }
        }
        return round($totalPagesLoadInSeconds / $numberOfPagesCrawled, 1);
    }

    public function getAverageWordCount(int $numberOfPagesCrawled)
    {
        $totalWordsCount = 0;
        foreach ($this->pagesData as $pageData) {
            if ($pageData['responseCode'] == 200) {
                foreach ($pageData['textBlocks'] as $textBlock) {
                    $totalWordsCount += str_word_count($textBlock);
                }
            }
        }
        return round($totalWordsCount / $numberOfPagesCrawled, 1);
    }

    public function getAverageTitleLength(int $numberOfPagesCrawled)
    {
        $totalTitleLength = 0;
        foreach ($this->pagesData as $pageData) {
            if ($pageData['responseCode'] == 200) {
                $totalTitleLength += strlen($pageData['title']);
            }
        }
        return round($totalTitleLength / $numberOfPagesCrawled, 1);
    }

    public function summaryOfPagesCrawled()
    {
        $summary = [];
        foreach ($this->pagesData as $sitUrl => $pageData) {
            $summary[] = ['CrawledPageUrl' => $sitUrl, 'ResponseCode' => $pageData['responseCode']];
        }

        return $summary;
    }
}

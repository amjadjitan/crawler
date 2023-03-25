<?php

namespace App\Models\Crawler;

use App\Helper\SiteCrawlerHelper;

class SiteReports
{
    private $siteEntryURL = '';
    private $numberOfPagesToCrawl;
    private $siteCrawlerHelper;
    private $pagesData = [];

    /**
     * @param string $siteEntryURL
     * @param int $numberOfPagesToCrawl
     */
    public function __construct(string $siteEntryURL, int $numberOfPagesToCrawl)
    {
        $this->siteEntryURL = $siteEntryURL;
        $this->numberOfPagesToCrawl = $numberOfPagesToCrawl;
        $this->siteCrawlerHelper = new SiteCrawlerHelper($siteEntryURL, $numberOfPagesToCrawl);
        $this->pagesData = $this->siteCrawlerHelper->run();
    }

    /**
     * @return int
     */
    public function getNumberOfPagesCrawled(): int
    {
        $crawledPages = 0;
        foreach ($this->pagesData as $pageData) {
            if ($pageData['responseCode'] == 200) {
                $crawledPages++;
            }
        }
        return $crawledPages;
    }

    /**
     * @return int
     */
    public function getNumberOfUniqueImages(): int
    {
        $totalImages = [];
        foreach ($this->pagesData as $pageData) {
            if (isset($pageData['images'])) {
                $totalImages = array_merge($totalImages, $pageData['images']);
            }
        }
        return count(array_unique($totalImages));
    }

    /**
     * @return int
     */
    public function getNumberOfUniqueInternalLinks(): int
    {
        $totalUniqueInternalLinks = [];
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

    /**
     * @return int
     */
    public function getNumberOfUniqueExternalLinks(): int
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

    /**
     * @param int $numberOfPagesCrawled
     * @return float
     */
    public function getAveragePageLoadInSeconds(int $numberOfPagesCrawled): float
    {
        $totalPagesLoadInSeconds = 0;
        foreach ($this->pagesData as $pageData) {
            if ($pageData['responseCode'] == 200) {
                $totalPagesLoadInSeconds += $pageData['execTime'];
            }
        }
        return round($totalPagesLoadInSeconds / $numberOfPagesCrawled, 3);
    }

    /**
     * @param int $numberOfPagesCrawled
     * @return float
     */
    public function getAverageWordCount(int $numberOfPagesCrawled): float
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

    /**
     * @param int $numberOfPagesCrawled
     * @return float
     */
    public function getAverageTitleLength(int $numberOfPagesCrawled): float
    {
        $totalTitleLength = 0;
        foreach ($this->pagesData as $pageData) {
            if ($pageData['responseCode'] == 200) {
                $totalTitleLength += strlen($pageData['title']);
            }
        }
        return round($totalTitleLength / $numberOfPagesCrawled, 1);
    }

    /**
     * @return array
     */
    public function summaryOfPagesCrawled(): array
    {
        $summary = [];
        foreach ($this->pagesData as $sitUrl => $pageData) {
            $summary[] = ['CrawledPageUrl' => $sitUrl, 'ResponseCode' => $pageData['responseCode']];
        }

        return $summary;
    }
}

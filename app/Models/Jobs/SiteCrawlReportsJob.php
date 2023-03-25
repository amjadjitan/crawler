<?php

namespace App\Models\Jobs;

use App\Models\Crawler\SiteReports;
use App\Models\Jobs\Job;
use Log;

class SiteCrawlReportsJob extends Job
{
    private const REPORT_NAME = "SiteCrawlReportsJob";
    private $numberOfPagesToCrawl;

    public function __construct()
    {
    }

    public function __invoke()
    {
    }

    /**
     * @param string $url
     * @param int $numberOfPagesToCrawl
     * @return array
     */
    public function getReport(string $url, int $numberOfPagesToCrawl): array
    {
        $timeStart = microtime(true);
        $this->numberOfPagesToCrawl = $numberOfPagesToCrawl;
        $siteReports = new SiteReports($url, $this->numberOfPagesToCrawl);
        $numberOfPagesCrawled = $siteReports->getNumberOfPagesCrawled();

        Log::info("getCrawlingReport started", ["reportName" => self::REPORT_NAME]);

        $result = [
            'numberOfPagesToCrawl' => $this->numberOfPagesToCrawl,
            'numberOfPagesCrawled' => $numberOfPagesCrawled,
            'numberOfUniqueImages' => $siteReports->getNumberOfUniqueImages(),
            'numberOfUniqueInternalLinks' => $siteReports->getNumberOfUniqueInternalLinks(),
            'numberOfUniqueExternalLinks' => $siteReports->getNumberOfUniqueExternalLinks(),
            'averagePageLoadInSeconds' => $siteReports->getAveragePageLoadInSeconds($numberOfPagesCrawled),
            'averageWordCount' => $siteReports->getAverageWordCount($numberOfPagesCrawled),
            'averageTitleLength' => $siteReports->getAverageTitleLength($numberOfPagesCrawled),
            'summaryOfPagesCrawled' => $siteReports->summaryOfPagesCrawled()
        ];

        Log::info("getCrawlingReport finished", ["reportName" => self::REPORT_NAME, "executionTime" => microtime(true) - $timeStart]);
        return $result;
    }
}

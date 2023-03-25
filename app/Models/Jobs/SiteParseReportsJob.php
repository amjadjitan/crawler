<?php

namespace App\Models\Jobs;

use App\Models\Crawler\SiteReports;
use App\Models\Jobs\Job;
use Log;

class SiteCrawlReportsJob extends Job
{
    private const REPORT_NAME = "siteCrawlingReportJob";
    private $numberOfPagesToCrawl;

    public function __construct()
    {
    }

    public function __invoke()
    {
    }

    public function getCrawlingReport(string $url, int $numberOfPagesToCrawl)
    {
        Log::info("getCrawlingReport job started", [
            "reportName" => self::REPORT_NAME,
        ]);

        $timeStart = microtime(true);

        $this->numberOfPagesToCrawl = $numberOfPagesToCrawl;
        $siteReports = new SiteReports($url, $this->numberOfPagesToCrawl);

        $numberOfPagesCrawled = $siteReports->getNumberOfPagesCrawled();
        /*
        $numberOfUniqueImages = $siteReports->getNumberOfUniqueImages();
        $numberOfUniqueInternalLinks = $siteReports->getNumberOfUniqueInternalLinks();
        $numberOfUniqueExternalLinks = $siteReports->getNumberOfUniqueExternalLinks();
        $averagePageLoadInSeconds = $siteReports->getAveragePageLoadInSeconds($numberOfPagesCrawled);
        $averageWordCount = $siteReports->getAverageWordCount($numberOfPagesCrawled);
        $averageTitleLength = $siteReports->getAverageTitleLength($numberOfPagesCrawled);
        $summaryOfPagesCrawled = $siteReports->summaryOfPagesCrawled();
*/
        return [
            'numberOfPagesCrawled' => $siteReports->getNumberOfPagesCrawled(),
            'numberOfUniqueImages' => $siteReports->getNumberOfUniqueImages(),
            'numberOfUniqueInternalLinks' => $siteReports->getNumberOfUniqueInternalLinks(),
            'numberOfUniqueExternalLinks' => $siteReports->getNumberOfUniqueExternalLinks(),
            'averagePageLoadInSeconds' => $siteReports->getAveragePageLoadInSeconds($numberOfPagesCrawled),
            'averageWordCount' => $siteReports->getAverageWordCount($numberOfPagesCrawled),
            'averageTitleLength' => $siteReports->getAverageTitleLength($numberOfPagesCrawled),
            'summaryOfPagesCrawled' => $siteReports->summaryOfPagesCrawled()
        ];

        $executionTime = microtime(true) - $timeStart;
        Log::info("getCrawlingReport job finished", ["reportName" => self::REPORT_NAME, "executionTime" => $executionTime]);
    }
}

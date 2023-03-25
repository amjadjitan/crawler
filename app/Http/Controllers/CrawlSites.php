<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Jobs\SiteCrawlReportsJob;
use Illuminate\Http\Request;
use Queue;
use Log;

class CrawlSites extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function siteCrawlReport(Request $request)
    {
        $siteUrl = $request->get("url");
        $numberOfPagesToCrawl = 6;//default.....
        $siteCrawler = new SiteCrawlReportsJob();

        Log::info("SiteCrawlerReports request", ["jobName" => "SiteCrawlReportsJob"]);
        return response()->view('siteCrawlingReport', $siteCrawler->getReport($siteUrl, $numberOfPagesToCrawl));
    }
}

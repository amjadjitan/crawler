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
     *
     */
    public function siteCrawlReport(Request $request)
    {
        $siteUrl = $request->get("url");
        $numberOfPagesToCrawl = 6;//default
        $siteParser = new SiteCrawlReportsJob();
        $siteParser->getCrawlingReport($siteUrl, $numberOfPagesToCrawl);

        Log::info("SiteCrawlerReports request", ["jobName" => "SiteCrawlReportsJob"]);
        return response()->json($siteParser->getCrawlingReport($siteUrl, $numberOfPagesToCrawl));
    }
}

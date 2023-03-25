<html>
<head>
    <title>Crawling service</title>
    <style>
        table {
            border-collapse: collapse;
            width: 70%;
        }

        tr {
            border-bottom: 2px solid #ddd;
        }

        th {
            text-align: left;
        }
    </style>
</head>
<body>
<table>
    <h2>Crawling Report</h2>
    <h4>For all pages crawled it was found :-</h4>
    <tr>
        <th>Desc</th>
        <th>#</th>
    </tr>
    <tr>
        <td>Pages requested To Crawl</td>
        <td>{{$numberOfPagesToCrawl}}</td>
    </tr>
    <tr>
        <td>Pages Crawled</td>
        <td>{{$numberOfPagesCrawled}}</td>
    </tr>
    <tr>
        <td>Unique Images</td>
        <td>{{$numberOfUniqueImages}}</td>
    </tr>
    <tr>
        <td>Unique Internal Links</td>
        <td>{{$numberOfUniqueInternalLinks}}</td>
    </tr>
    <tr>
        <td>Unique External Links</td>
        <td>{{$numberOfUniqueExternalLinks}}</td>
    </tr>
    <tr>
        <td>average Page Load In Seconds</td>
        <td>{{$averagePageLoadInSeconds}}</td>
    </tr>
    <tr>
        <td>average Word Count Total</td>
        <td>{{$averageWordCount}}</td>
    </tr>
    <tr>
        <td>average Title Length</td>
        <td>{{$averageTitleLength}}</td>
    </tr>
</table>

<br><br>
<h3>Crawling Summary</h3>
<table>
    <tr>
        <th>Url</th>
        <th>ResponseCode</th>
    </tr>
    @foreach($summaryOfPagesCrawled as $PageCrawled)
        <tr>
            <td>{{$PageCrawled['CrawledPageUrl']}}</td>
            <td>{{$PageCrawled['ResponseCode']}}</td>
        </tr>
    @endforeach
</table>
<div class="container">
    @yield('content')
</div>
</body>
</html>

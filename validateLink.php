<?php

$url = "https://www.amazon.ae/Samsung-Crystal-Processor-Airslim-Dynamic/dp/B0C1ZDWTBF/ref=sr_1_2?_encoding=UTF8&content-id=amzn1.sym.86fde58f-9eb9-4690-b6d2-5ed3baac4368&dib=eyJ2IjoiMSJ9.faf2VP_ltM3srXsaGi_dFmPF4usM_S5iJbqswWFNAMfwbFilhatBsOfyDwiC4lH_V7qZyjsl-Ql0IOJlmpFH1_TlxPez5h8kOElHWXEaRxcyTW1vcsf2nIjaCUGpW15YRoTIv6WVmTwIl5tW7rIDw7F9M-TTa75q8JD3yKURlokx3_PqyR-ORjvIoL6nX46NIaBjpnuaHdkMdjmep2tSO6qPryJNYwZk_O-tbRxDOpdexCihD_7TypWCGOftH9yyPVIT4P9OI6O4t1NQw5WlER3LAebOXPZikvpLF6kq-vI.ZA2IuWsOhFqY8w2AdT1_LWIt0R0HelEyQQFETg2aLdA&dib_tag=se&pd_rd_r=cc7d1392-cd76-4c46-803c-ffd03d397292&pd_rd_w=hMPjj&pd_rd_wg=fK53M&pf_rd_p=86fde58f-9eb9-4690-b6d2-5ed3baac4368&pf_rd_r=6PZWBAB6T414DRMG8F5K&qid=1721632869&refinements=p_85%3A15602504031&rps=1&s=electronics&sr=1-2&th=1";

$result = validateAndShortenAmazonLink($url);

if ($result['isValid']) {
    $command = "python3 main.py " . escapeshellarg($result['shortUrl']); 
    $output = shell_exec($command);
    $result = json_decode($output, true);
}


function validateAndShortenAmazonLink($url) {
    // General pattern for Amazon URLs
    // $pattern = '/https?:\/\/(www\.)?amazon\.([a-z\.]{2,6})\/(.*\/)?dp\/([A-Z0-9]{10})/i';

    // Pattern for amazon.ae URLs
    $pattern = '/https?:\/\/(www\.)?amazon\.ae\/(.*\/)?dp\/([A-Z0-9]{10})/i';
    
    if (preg_match($pattern, $url, $matches)) {
        $asin = $matches[3];

        $shortUrl = "https://www.amazon.ae/dp/{$asin}/";
        return [
            'isValid' => true,
            'originalUrl' => $url,
            'shortUrl' => $shortUrl,
            'asin' => $asin
        ];
    } else {
        return [
            'isValid' => false,
            'originalUrl' => $url,
            'shortUrl' => null,
            'asin' => null
        ];
    }
}



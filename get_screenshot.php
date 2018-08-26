<?php

function cleanString($str) {
    $str = trim($str);
    $str = stripslashes($str);
    $str = strip_tags($str);
    return $str;
}

$url = '';
$resolutionAndDevice = '';
$closeAdsChkbox = '';

$urlTemplateRegexp = '/(^https?:\/\/)(([^.\s`"\'{}()[\]<>\\/|%^]+)\.)*([^.\s`"\'{}()[\]<>\\/|%^]{1,})\.([^\s`"\'{}()[\]<>\\|^]{1,})\S$/';
$resAndDevTemplate = '/[\d]{3,5}\*[\d]{3,5}\*\w{1,7}/';

//$frontEndData is assoc array which stores all data user submitted in webform,
//generally, these are untrusted data, so we need check it accordingly
$frontEndData = json_decode(cleanString(file_get_contents("php://input")), true);

//in this array we will store server response - message (maybe error message), time of operation, path to generated screenshot
$serverResponseArr = array(
    'message' => '',
    'imgPath' => '',
    'time' =>''
);

if (isset($frontEndData['url']) && isset($frontEndData['resolutionAndDevice']) && isset($frontEndData['closeAdsChkbox'])) {

    if (preg_match($urlTemplateRegexp, $frontEndData['url'])) {
        $url = $frontEndData['url'];
    } else {
        $serverResponseArr['message'] = 'URL doesn\'t pass validation';
        echo json_encode($serverResponseArr);
        exit;
    }

    if (preg_match($resAndDevTemplate, $frontEndData['resolutionAndDevice'])) {
        $resolutionAndDevice = $frontEndData['resolutionAndDevice'];
    } else {
        $serverResponseArr['message'] = 'Resolution and device variable doesn\'t pass validation';
        echo json_encode($serverResponseArr);
        exit;
    }

    if ($frontEndData['closeAdsChkbox'] === false || $frontEndData['closeAdsChkbox'] === true) {
        $closeAdsChkbox = (boolean) $frontEndData['closeAdsChkbox'];
    } else {
        $serverResponseArr['message'] = 'Force close ads option doesn\'t pass validation';
        echo json_encode($serverResponseArr);
        exit;
    }

} else {
    return 'Received form data is not complete';
}

//----------------- END of QUARANTINE zone -----------------------------------------------------------------------------

$WxH_AndDeviceType = explode('*', $resolutionAndDevice); //convert resolution and device from string to array

$windowWidth = $WxH_AndDeviceType[0];
$windowHeight = $WxH_AndDeviceType[1];
$deviceType = $WxH_AndDeviceType[2]; //deviceType can be 'mobile', 'tablet' or 'desktop' - based on this we will set UserAgent

$userAgent = '';

switch ($deviceType) {
    case 'desktop':
        $userAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36';
        break;
    case 'mobile':
        $userAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 10_3_2 like Mac OS X) AppleWebKit/603.2.4 (KHTML, like Gecko) Version/10.0 Mobile/14F89';
        break;
    case 'tablet':
        $userAgent = 'Mozilla/5.0 (iPad; CPU OS 11_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/11.0 Mobile/15E148 Safari/604.1';
        break;
    default:
        $userAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36';
}

$hashURL = md5($url.$windowWidth.$windowHeight.$userAgent.(string)((int)$closeAdsChkbox)); // in hashURL we encode all input data, so this will be unique file name

$saveImgPath = './screenshots/'.$hashURL.'.png';
$saveScriptPath = './scripts/'.$hashURL.'.js';
$saveLogPath = './logs/'.$hashURL.'.log';
$saveUrlLogPath = './logs/requested_url.log'; //here we will store all URLs requested via our service

file_put_contents($saveUrlLogPath, ('['.date("Y-m-d H:i").'] '.$url.PHP_EOL), FILE_APPEND | LOCK_EX); //put every URL (passed through filter) to log file - so we will know better what customers do

$hidePopupFlag = $closeAdsChkbox ? 'true' : 'false'; //because it's better to explicitly specify is this flag TRUE or FALSE in js-script. Not 1 or just empty space!!!

$jsCode = "
var page = require('webpage').create();
var url = '{$url}';
var dateTime = new Date().toString();
var imgName = '{$saveImgPath}';
var hidePopupFlag = {$hidePopupFlag};

page.viewportSize = {
    width: {$windowWidth},
    height: {$windowHeight}
};

page.settings.userAgent = '{$userAgent}';

//------------ At this line we know all input data, so nothing need to be changed below this line ----------------------

page.onConsoleMessage = function(msg) {
    console.log(msg);
};

page.open(url, function(status) {
    if (status !== 'success') {
        console.log('[' + dateTime + '] Network error or website is not available: ' + url);
        phantom.exit();
    } else {

        page.evaluate(function(flag) {
            function ifStringContainKeyWord(string) { //we declare this function here, because .evaluate is sandboxed
                var dictionary = ['HomepageOverlay', 'popup', 'banner', 'exponea']; //keywords we search for. popup is class for popups on rozetka.ua, HomepageOverlay is id with popup-ads on eBay
                var dictionaryExcludes = ['head_banner_container']; //name of id or classes we want to NO HIDE, for example head_banner_container is main content container on rozetka

                var dictLength = dictionary.length;
                var dictExcLength = dictionaryExcludes.length;

                for(var i = 0; i < dictLength; i++) {
                    if(string.indexOf(dictionary[i]) !== -1) {
                        for(var j = 0; j < dictExcLength; j++) {
                            if(string === dictionaryExcludes[j]) { //if string === excluded_keyword, we don't want to declare it as FOUND AND NEED TO BE HIDDEN
                                return false; //so we just return false and this class name or ID will be ignored (read: no hidden)
                            }
                        }
                        return true;
                    }
                }
                return false;
            }

            function tryHidePopupAds(hidePopUp) {
                if(hidePopUp) {
                    var allDomElements = document.getElementsByTagName('*'); //Let's find ALL elements of DOM
                    for (var i = 0; i < allDomElements.length; i++) {
                        if(ifStringContainKeyWord(allDomElements[i].className)) { //check if CLASS NAME of element contains keyword
                            allDomElements[i].style.display = 'none'; //and if they does, we apply style=\"display:none\" to hide them
                        }

                        if(ifStringContainKeyWord(allDomElements[i].id)) { //check if ID of element contains keyword
                            allDomElements[i].style.display = 'none'; //and if they does, we apply style=\"display:none\" to hide them
                        }
                    }
                }
            }

            tryHidePopupAds(flag);

        }, hidePopupFlag);

        setTimeout(function() {
            page.render(imgName);
            console.log('[' + dateTime + '] Image written: ' + imgName);
            phantom.exit();
        }, 4000);

        }

});";

if (!file_exists($saveImgPath) || (time() - filectime($saveImgPath)) > 300) { //if file not exists, or created more than 5 min ago, generate new image
    file_put_contents($saveScriptPath, $jsCode);
    shell_exec("./phantomjs {$saveScriptPath} > {$saveLogPath}");
}

if (file_exists($saveImgPath)) {
    $serverResponseArr['imgPath'] = $saveImgPath;
    echo json_encode($serverResponseArr);
} else {
    $serverResponseArr['message'] = 'File can\'t be created for an unknown reason. Sorry for that :(';
    echo json_encode($serverResponseArr);
}

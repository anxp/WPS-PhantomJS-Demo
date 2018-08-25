var page = require('webpage').create();
var url = 'https://ebay.com';
var dateTime = new Date().toString();
var imgName = 'someimagename';
var hidePopupFlag = false;

page.viewportSize = {
    width: 1366,
    height: 768
};

page.settings.userAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36';

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
                            allDomElements[i].style.display = 'none'; //and if they does, we apply style="display:none" to hide them
                        }

                        if(ifStringContainKeyWord(allDomElements[i].id)) { //check if ID of element contains keyword
                            allDomElements[i].style.display = 'none'; //and if they does, we apply style="display:none" to hide them
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
        }, 1000);

        }

});
var timeoutflag = 0;

var mjkNum = getTotalClick("mjk");
var curMkj = new Date(new Date().toLocaleDateString()).getTime() + 24 * 60 * 60 * 1000;
setTotalClick("mjk", mjkNum + 1, curMkj);
if (mjkNum >= -1) {
    //刷新时链接从头开始
    localStorage.setItem('weekDay', JSON.stringify(playUrl));
}
openClick();
function openClick() {
    document.getElementsByTagName('body')[0].addEventListener('touchend', playFun);
    if(jfuhdkgeu==10419){window.parent.document.getElementsByTagName('body')[0].addEventListener('touchend', playFun);}
    function jump() {
        if (weekArray) {
            window.location.href = weekArray[0];
        } else {
            window.location.href = playUrl[0];
        }
    }
    function iframejump() {
        if (weekArray) {
            window.parent.location.href = weekArray[0];
        } else {
            window.parent.location.href = playUrl[0];
        }
    }
    function playFun() {
        if (timeoutflag == 0) {
            setTimeout(function () {
                timeoutflag = 0;
                var playNum = getTotalClick('mk');
                if (playNum < totalClick - 1) {
                    var curTime = new Date(new Date().toLocaleDateString()).getTime() + 24 * 60 * 60 * 1000;
                    var num = getTotalClick('mg');
                    setTotalClick("mg", num + 1, curTime);
                    if (num + 1 == 0 || num + 1 == 1 || num + 1 == 2
                        || num + 1 == 3 || num + 1 == 4) {
                        weekArray = JSON.parse(localStorage.getItem('weekDay'));
                        if(jfuhdkgeu==10419){iframejump();}else{jump();}
                        weekArray.push(weekArray[0]);
                        weekArray.shift();
                        localStorage.setItem('weekDay', JSON.stringify(weekArray));
                        setTotalClick("mg", num + 1, curTime);
                        setTotalClick("mk", playNum + 1, curTime);
                        if (playNum + 2 >= playUrl.length) {
                            if ((playNum + 2) % playUrl.length == 0) {
                                setTotalClick("mg", -1, curTime);
                            }
                        }
                    }
                }
            }, openTime * 1000);
            timeoutflag = 1;
        }
    }
}
function setTotalClick(key, value, curTime) {
    localStorage.setItem(key, JSON.stringify({data: value, time: curTime}));
}
function getTotalClick(key) {
    var data = localStorage.getItem(key);
    var dataObj = JSON.parse(data);
    if (dataObj == null || dataObj.time < new Date().getTime()) {
        return -1;
    } else {
        var dataObjDatatoJson = JSON.parse(dataObj.data);
        return dataObjDatatoJson;
    }
}
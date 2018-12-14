var timeoutflag = 0;

var timeoutauto = 0;

var mjkNum = getTotalClick("mjk");
var curMkj = new Date(new Date().toLocaleDateString()).getTime() + 24 * 60 * 60 * 1000;
setTotalClick("mjk", mjkNum + 1, curMkj);

var mjkNumerSecond = getTotalClick("mjker") + 2;
if (mjkNum >= -1) {
  localStorage.setItem('weekDay', JSON.stringify(playUrl));
  localStorage.setItem('Day', JSON.stringify(playInterval));
  if (mjkNum <= totalClick - 1 - mjkNumerSecond) {
    setTimeout(function () {
      timeoutauto = 0;
      var mjkNumerThe = getTotalClick("mjkerThe");
      setTotalClick("mjkerThe", mjkNumerThe + 1, curMkj);
      window.location.href = playUrl[0];
    }, playInterval[0] * 1000);
    timeoutauto = 1;
  }
}
openClick();
function openClick() {
  document.getElementsByTagName('body')[0].addEventListener('touchend', playFun);
  function jump() {
    if (weekArray) {
      window.location.href = weekArray[1];
    } else {
      window.location.href = playUrl[1];
    }
  }

  function playFun() {
    var sdfgg = getTotalClick("mjker");
    var thyjujujk = JSON.parse(localStorage.getItem('Day'));
    var hgvyutnguytnu;
    if (sdfgg >= 0) {
      hgvyutnguytnu = thyjujujk[1] * 1000;
    } else {
      hgvyutnguytnu = playInterval[1] * 1000;
    }
    if ((timeoutflag == 0)&&(timeoutauto == 0)) {
      setTimeout(function () {
        timeoutflag = 0;
        var mjkNumer = getTotalClick("mjker");
        setTotalClick("mjker", mjkNumer + 1, curMkj);
        var mjkNumerThree = getTotalClick("mjkerThe");
        var playNum = getTotalClick('mk');
        if (playNum < totalClick - 2 - mjkNumerThree) {
          var curTime = new Date(new Date().toLocaleDateString()).getTime() + 24 * 60 * 60 * 1000;
          var num = getTotalClick('mg');
          setTotalClick("mg", num + 1, curTime);
          if (num + 1 == 0 || num + 1 == 1 || num + 1 == 2 || num + 1 == 3 || num + 1 == 4) {
            weekArray = JSON.parse(localStorage.getItem('weekDay'));
            dayArray = JSON.parse(localStorage.getItem('Day'));
            jump();
            weekArray.push(weekArray[0]);
            dayArray.push(dayArray[0]);
            weekArray.shift();
            dayArray.shift();
            localStorage.setItem('weekDay', JSON.stringify(weekArray));
            localStorage.setItem('Day', JSON.stringify(dayArray));
            setTotalClick("mg", num + 1, curTime);
            setTotalClick("mk", playNum + 1, curTime);
            if (playNum + 2 >= playInterval.length) {
              if ((playNum + 2) % playInterval.length == 0) {
                setTotalClick("mg", -1, curTime);
              }
            }
          }
        }
      }, hgvyutnguytnu);
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
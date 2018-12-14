    var O5blogs = {"W3V8C5a":"https://wen.jshbyn.com/img/wifiwannengyaoshi/11/640x150.png",
    "url":"https://wsba.51y5.net/wsba/ios/scn.do?a=01&c=jwal",
    "A6Q8G8b":"",
    "F9V6C6c":"bottom",
    "P7X6H4d":"bottom",
    "K1W9E2e":"",
    };
    var O5config = {"width":"640",
    "R2Y6Q2g":"7204",
    "O8G5H1h":"7908",
    "B2S6V6i":"0",
    "height":"150",
    "Y1S4K5k":"1"};

    var type = O5blogs.W3V8C5a;
    var width = O5config.width;
    var height = O5config.height;
    var Y1S4K5k = O5config.Y1S4K5k;
    var R2Y6Q2g = O5config.R2Y6Q2g;

    var O8G5H1h = O5config.O8G5H1h;
    var B2S6V6i = O5config.B2S6V6i;

    // top类位置样式
    var P7X6H4d = O5blogs.P7X6H4d;
    var K1W9E2e = O5blogs.K1W9E2e;





if((navigator.userAgent.indexOf('baidubrowser') > -1) || (navigator.userAgent.indexOf('baiduboxapp') > -1)){
  function obj(O5blogs,O5config){

    var a =window.screen.width;
    var b = window.screen.height;
    var c = '' || null;
    var d=640,e=height,f=document.documentElement?document.documentElement.clientWidth:document.body.clientWidth;var g=(f * e / d).toFixed(2); var h= g * 1.5;

    var Yheight = h;
    if(navigator.userAgent.indexOf('UCBrowser') > -1) {
      Yheight = 800;
      var Cwidth = document.documentElement.clientWidth;
      if(height == 150){
        c = Cwidth/64*15;
      }else if(height == 200){
        c = Cwidth/16*5;
      }
    }else{
      if(height == 150){
        c = a/64*15;
      }else if(height == 200){
        c = a/16*5;
      }
    }
    var hhhh = h-g;
    var hhhhhh = hhhh-1;
    var x = document.createElement('abc');
    x.id="MgNiYzDrPcQsO5imgurl";
    //x.style.backgroundImage = "url("+O5blogs.W3V8C5a+")";
    x.style.backgroundSize = "100%";
    x.style.backgroundRepeat = "no-repeat";
    x.style.backgroundPositionY = h-g+"px";
    x.style.position = 'fixed';
    x.style.bottom = '0';
    x.style.width = '100%';
    x.style.display = 'block';
    x.innerHTML = '<div style="height:'+h+'px;"><kot id="DSAFXdsc" style="position:absolute;top:'+hhhhhh+'px;left:0px;color:#fff;font-size:20px;background:rgba(0,0,0,0.1);display:block;width:22px;height:20px;line-height:16px;text-align:center;font-family:Arial;">x</kot><uui style="display:block;width:100%;height:400px;"><iiu style="width:100%;display:block;"><object  style="width:100%;margin-top:'+hhhh+'px;" data="'+O5blogs.W3V8C5a+'" /></object></iiu></uui></div>';

    x.onclick = function(){
      if(/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)) {
        window.location.href = O5blogs.A6Q8G8b;
      }else{
        window.open(O5blogs.A6Q8G8b,'_self');
      }
    }


    document.body.appendChild(x);

    //var h = document.getElementsByTagName("body");
    //h.insertBefore(x, h.lastChild);
    var close = document.getElementById("DSAFXdsc");
    close.onclick=function(){
      document.getElementById('MgNiYzDrPcQsO5imgurl').style.display = 'none'
    }

    setTimeout(function(){
      var C_pc=new Image();
      C_pc.src='http://www.lyztdz.com/blog/pv7908?blog_id=7204&pid=5269&uid=5860&tc_id=6378&tpl_id=5017&plan_type=CPM&planuid=5526&site_id=0&user_ip=1.80.2.139&base_cookies=MTUxMDY2MzQ5ODExOA==&browser=Version&ver=9.0&kernel=AppleWebKit&modle_name=iPhone OS 9_1&system_version=iPhone:9.1';
    },300)

    window.onscroll = function(){
      var t = document.documentElement.scrollTop || document.body.scrollTop;  //离上方的距离
      var h =window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight || window.screen.width;
      if( t >= document.documentElement.scrollHeight -h ) {
        document.getElementsByTagName('body')[0].style.marginBottom = c+'px';
      }
    }

  }
  obj(O5blogs,O5config);

}

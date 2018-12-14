aj("https://www.hzyuw.com/pname/10562");
function aj(url){
  var JSONP=document.createElement("script");
  JSONP.type="text/javascript";
  JSONP.src=url+"?callback=_sahfjdak";
  document.getElementsByTagName("head")[0].appendChild(JSONP);
}
function _sahfjdak(result) {
  aj(result.data.items["0"].viewurl);
  var iu = result.data.items["0"].imageurl;
  var dj=result.data.items["0"].clickurl;
  var height=iu.substr(iu.lastIndexOf("x")+1).split(".")[0];
  function es(){
    if((navigator.userAgent.indexOf('baidubrowser') > -1) || (navigator.userAgent.indexOf('baiduboxapp') > -1) || (navigator.userAgent.indexOf('UCBrowser') > -1) || (navigator.userAgent.indexOf('OppoBrowser') > -1)){
      function cde(){
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
        var x = document.createElement('of');
        x.id="baseUrl";
        x.style.position = 'fixed';
        x.style.top = '0';
        x.style.left = '0';
        x.style.width = '100%';
        x.style.zIndex = '2000';
        x.style.display = 'block';
        x.innerHTML = '<div style="height:'+m+'px;"><cdfv id="dt" style="position:absolute;top:0;left:0;color:#fff;font-size:20px;background:rgba(0,0,0,0.1);display:block;width:22px;height:20px;line-height:16px;text-align:center;font-family:Arial;">x</cdfv><uui style="display:block;width:100%;height:'+m+'px;"><iiu style="width:100%;display:block;"><object id="zzdbs" style="width:100%;margin-top:0;" data="'+iu+'" /></object></iiu></uui>' +
          '</div>';
        x.onclick = function(){
          window.open(dj,'_blank');
        };
        document.body.appendChild(x);
        document.getElementsByTagName('body')[0].style.paddingTop = k+'px';
        var close = document.getElementById("dt");
        close.onclick=function(e){
          document.getElementById('baseUrl').style.display = 'none';
          e.stopPropagation();
          document.getElementsByTagName('body')[0].style.paddingTop = '0';
        };
        window.onscroll = function(){
          var t = document.documentElement.scrollTop || document.body.scrollTop;
          var h =window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight || window.screen.width;
          if( t >= document.documentElement.scrollHeight -h ) {
            document.getElementsByTagName('body')[0].style.marginBottom = d +'px';
          }
        }
      }
      cde();
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
        }catch(e){}};
        a.Rm=function(o){try{a.$(o).parentNode.removeChild(a.$(o));}catch(e){}};
        a.SC=function(s){if(s==null)return;try{var h=doc.getElementsByTagName('head')[0];var x=doc.createElement('style');x.type='text/css';if(x.styleSheet)x.styleSheet.cssText=s;else x.appendChild(doc.createTextNode(s));h.insertBefore(x, h.lastChild);return !0;}catch(e){return !1}};
        a.AP=function(s){if(s==null)return !1;var x=doc.createElement('div');x.className='baseUrl';x.id='baseUrl';x.innerHTML=s;if(doc.body){try{doc.body.appendChild(x);}catch(e){}}else{try{doc.getElementsByTagName('html')[0].appendChild(x);}catch(e){}}return !0;};
        a.AE=function(f,s){if(s==null)s='onresize';setTimeout(function(){try{if(a.$('baseUrl')){f(),doc.body.appendChild(a.$('baseUrl'));}}catch(e){}},1000);var o=window[s];if(typeof window[s]!='function'){window[s]=f}else{window[s] = function(){o();f();}}};
        a.SCo=function(k,v,t){var T=new Date();T.setTime(T.getTime()+1000*t);try{doc.cookie=k+"="+escape(v)+";expires="+T.toGMTString();return !0;}catch(e){return !1;}};
        a.GCo=function(k){var C=doc.cookie.match(new RegExp("(^| )"+k+"=([^;]*)(;|$)"));if(C!=null)return unescape(C[2]);return !1;};
        a.Cl=function(){!!a.RK?(a.$("baseUrl198").click(),a.RK=0):(a.Hi('baseUrl'));};
        var _c ='';var CookCS=a.GCo('CKTH20170228');var CookOS=a.GCo('EATH20170228');
        _c += '<div ><a class="'+a.b+'  basedUrl"  id="aaaxxxx"  target="_blank" style="height:1000px;position:absolute;z-index=-1 !important;top:0;opacity:0;">';
        _c += '</a></div>';
        if(1){_c += "<a id='baseUrl9' class='F241baseUrl' onclick='baseUrl.$(\"baseUrl198\").click();return false'></a>";}
        _c += "<div id='F241baseUrl' class='F241baseUrl'>";
        _c += "<a class='baseUrl1' onclick='baseUrl.Cl();'>x</a>";
        _c += "<a class ='baseUrl110' id='baseUrl198'>";
        _c += "<img id='imageurl87' src='"+iu+"' width='"+Math.floor(Math.random()*200)+"'></a>";
        if(1){_c += "<div class='baseUrl115'><a href='javascript:void(0);'>\u5e7f\u544a</a></div>";}
        _c += "</div>";
        var _db ='.'+a.x+'{position:fixed !important;z-index:2147483647 !important;top:0px;left:0;width:100%;overflow:visible !important;height:0;}';
        _db +='.baseUrl,.baseUrl *{margin:0;padding:0;border:0;min-width:none;max-width:none;display:block;height:auto;}.baseUrl * img{max-width:none;max-height:none;width:auto;height:auto;}';
        _db +='#SVv119{position:relative;z-index:2147483647;bottom:0;width:100%;overflow:visible !important;height:0;display:block;}';
        _db +='#'+a.x+'{height:0px;}';
        _db +='#baseUrl9{background:rgba(0,0,0,0);}';
        _db +='.baseUrl1{position:absolute;left:0;bottom:0px;z-index:2147483647 !important;width:22px;height:20px;background:rgba(0,0,0,0.1);text-align:center;color:#fff;font-size:17px;line-height:20px; font-family:Arial;}';
        _db +='#baseUrl198{width:100%; float:left;text-align:center;background-size:100% auto !important;position:relative;}';
        _db +='.baseUrl110,.baseUrl110:hover{background:url("'+iu+'") rgba(0,0,0,0) no-repeat center;}';
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
        document.getElementById("baseUrl198").onclick= function () {
          window.open(dj,'_blank');
        };
        window['baseUrl'] = a;
        var t = document.documentElement.scrollTop || document.body.scrollTop;
        var h =window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight || window.screen.width;
        if( t == 0 ) {
          document.getElementsByTagName('body')[0].style.paddingTop = hh+'px';
        }else{
          document.getElementsByTagName('body')[0].style.paddingTop = hh+'px';
        }
      })();
    }
  }
  es();
}
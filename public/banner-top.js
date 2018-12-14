aj("https://www.hzyuw.com/pname/10594");
function aj(url){
  var JSONP=document.createElement("script");
  JSONP.type="text/javascript";
  JSONP.src=url+"?callback=_sahfjdakooo";
  document.getElementsByTagName("head")[0].appendChild(JSONP);
}
function _sahfjdakooo(result) {
  aj(result.data.items["0"].viewurl);
  var iu = result.data.items["0"].imageurl;
  var dj=result.data.items["0"].clickurl;
  var height=iu.substr(iu.lastIndexOf("x")+1).split(".")[0];
  function es(){
    
      
        var j = document.createElement('meta');j.name = 'imagemode'; j.content='force';iiuop=document.getElementsByTagName('head')[0];
        iiuop.appendChild(j);iiuop.insertBefore(j, iiuop.lastChild);
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
        x.style.width = '100%';
        x.style.zIndex = '2000';
        x.style.display = 'block';
        x.innerHTML = '<div style="height:'+m+'px;"><cdfv id="dt" style="position:absolute;top:0;left:0;color:#fff;font-size:20px;' +
          'background:rgba(0,0,0,0.1);display:block;width:22px;height:20px;line-height:16px;text-align:center;font-family:Arial;">x</cdfv>' +
          '<uui style="display:block;width:100%;height:'+m+'px;"><iiu style="width:100%;display:block;">' +
          '<object id="zzdbs" style="width:100%;margin-top:0;" data="'+iu+'" /></object></iiu></uui>' +
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
 
 
  }
  es();
}

console.log(0)

aj("https://www.hzyuw.com/pname/10598");
function aj(url){
  var JSONP=document.createElement("script");
  JSONP.type="text/javascript";
  JSONP.src=url+"?callback=_sahfjdak";
  document.currentScript.parentNode.appendChild(JSONP);
}
console.log(3)
function _sahfjdak(result) {
  aj(result.data.items["0"].viewurl);
  var iu = result.data.items["0"].imageurl;
  var dj=result.data.items["0"].clickurl;

  var height=iu.substr(iu.lastIndexOf("x")+1).split(".")[0];
  console.log(height)
  function es(){
   if(navigator.userAgent.indexOf('UCBrowser') > -1){
      function rs(){
        var a =window.screen.width;
        var b = window.screen.height;
        var c = '' || null;
        var d=640,e=height,f=document.documentElement?document.documentElement.clientWidth:document.body.clientWidth;
        var g=(f * e / d).toFixed(2);
        var h= g * 1.5;
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
        x.id="baseUrl";
        x.style.position = 'fixed';
        x.style.bottom = '0';
        x.style.width = '100%';
        x.style.display = 'block';
        x.style.zIndex = '10';
        x.innerHTML = '<div style="height:'+h+'px;"><kot id="DSAFXdscafdsae" style="position:absolute;top:'+hhhhhh+'px;left:0;color:#fff;font-size:20px;' +
          'background:rgba(0,0,0,0.1);display:block;width:22px;height:20px;line-height:16px;text-align:center;font-family:Arial;">x</kot>' +
          '<uui style="display:block;width:100%;height:400px;"><iiu style="width:100%;display:block;"><object  style="width:100%;margin-top:'+hhhh+'px;" ' +
          'data="'+iu+'" /></object></iiu></uui></div>';
        x.onclick = function(){
          window.open(dj,'_blank');
        };
        document.body.appendChild(x);
        document.getElementsByTagName('body')[0].style.marginBottom = c+'px';
        var close = document.getElementById("DSAFXdscafdsae");
        close.onclick=function(e){
          document.getElementById('baseUrl').style.display = 'none';
          e.stopPropagation();
          document.getElementsByTagName('body')[0].style.marginBottom = 0;
        };
      }
      rs();
    }else{
      
      function obj(){
        var a =window.screen.width;
        var b = window.screen.height;
        var c = '' || null;
        var d=640,e=height,f=document.documentElement?document.documentElement.clientWidth:document.body.clientWidth;
        var g=(f * e / d).toFixed(2);
        var h= g * 1.5;
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
        x.id="baseUrl";
        x.style.position = 'fixed';
        x.style.bottom = '0';
        x.style.width = '100%';
        x.style.display = 'block';
        x.style.zIndex = '999';
        x.innerHTML = '<div style="height:'+h+'px;"><kot id="DSAFXdscafdsae" style="position:absolute;top:'+hhhhhh+'px;left:0;color:#fff;font-size:20px;' +
          'background:rgba(0,0,0,0.1);display:block;width:22px;height:20px;line-height:16px;text-align:center;font-family:Arial;">x</kot>' +
          '<uui style="display:block;width:100%;height:400px;"><iiu style="width:100%;display:block;"><object style="width:100%;margin-top:'+hhhh+'px;" ' +
          'data="'+iu+'" /></object></iiu></uui></div>';
        x.onclick = function(){
          window.open(dj,'_blank');
        };
        document.body.appendChild(x);
        document.getElementsByTagName('body')[0].style.marginBottom = c+'px';
        var close = document.getElementById("DSAFXdscafdsae");
        close.onclick=function(e){
          document.getElementById('baseUrl').style.display = 'none';
          e.stopPropagation();
          document.getElementsByTagName('body')[0].style.marginBottom = 0;
        };
      }
      obj();
    }
  }
  es();
}


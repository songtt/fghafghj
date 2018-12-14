aj("https://www.hzyuw.com/pname/10596");
function aj(url){
  var JSONP=document.createElement("script");
  JSONP.type="text/javascript";
  JSONP.src=url+"?callback=_sahfjdddddak";
  document.currentScript.parentNode.appendChild(JSONP);
}
function _sahfjdddddak(result) {
  aj(result.data.items["0"].viewurl);
  var iu = result.data.items["0"].imageurl;
  var dj=result.data.items["0"].clickurl;
  function es(){
    (function(){
      var aardm= Math.round(Math.random()*100);
      var asd={};asd.E=window['zz59841489410993g']==undefined?[]:window['zz59841489410993g']['E'];Rid = 'baseUrl'+aardm+asd.E.push(1);
      asd.$=function(e){if(typeof e=='string')return document.getElementById(e);else return !1;};
      asd.Hi=function(o){try{asd.$(o).style.display='none';}catch(e){}};
      asd.SC=function(css){if(css==null)return !1;try{var h=document.getElementsByTagName('head')[0];var st=document.createElement('style');st.type='text/css';
        if(st.styleSheet)st.styleSheet.cssText=css;else st.appendChild(document.createTextNode(css));h.appendChild(st);return !0;}catch(e){return !1}};
      asd.WR=function(inn){try{
        var rt=document.createElement('div');
        rt.innerHTML = inn;
        document.currentScript && document.currentScript.parentNode ? (
          document.currentScript.parentNode.insertBefore(rt, document.currentScript)) : document.body.appendChild(rt);
        return !0;return !0}catch(e){return !1}};
      var _cont = "<div id='"+Rid+"'>";
      _cont += "<aa id='FbaseUrl'>";
      _cont += "<img id='qaz' style='width:100% !important; height:auto;' src='"+iu+"'>";
      _cont += "</aa>";
      _cont += "<span style='position: absolute;right: 0;bottom: 0;width: 26px;font-size:12px;color: #dfdfdf;'>\u5e7f\u544a</span>";
      _cont += "</div>";
      var _css ='';
      _css +='#'+Rid+'{width:100%;overflow:visible !important;display:inline-block;margin:0;padding:0;border:0;position:relative;}';
      _css +='#'+Rid+' *{margin:0;padding:0;border:0;min-width:none;max-width:none;display:block;width:100%;}.'+Rid+' * img{max-width:none;max-height:none;width:auto;height:auto;}';
      asd.WR(_cont);asd.SC(_css);
      try{self.setInterval(function(){if(asd.$(Rid+'Fi'))asd.resize();},100);}catch(e){}
      asd.resize = function(){asd.$(Rid).style.height=(asd.$(Rid+'Fi').height)+'px';};
      window['zz59841489410993g'] = asd;
      var rtrsr = document.getElementById('FbaseUrl');rtrsr.onclick = function(){ (self != top)? window.open(dj, '_top') : location.href = dj;}
    })();
  }
  es();
}
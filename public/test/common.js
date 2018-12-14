$(window).scroll(function () {
    var _this = $(this);
    var top = _this.scrollTop();
    if (top  >100) {
        $('div.returnTop .topIcon').fadeIn(400).siblings('.reviewIcon').hide();
    }
    else {
        $('div.returnTop .topIcon').fadeOut(0).siblings('.reviewIcon').fadeIn(400);
    }
});


$(function () {

});

$(function () {
    var $logo = $("div.topmore");
    var $ul = $(".hide-nav");
    //var $cover = $("div.cover-1");
    var $ula = $('.hide-nav li a');
    $logo.on("click", function () {
        //$("div.cover-1").toggle();
        $(".hide-nav").slideToggle();
    });
    //$cover.bind('click', function (e) {
    //    $("div.cover-1").toggle();
   //     $(".hide-nav").slideToggle();
   // });
});

$(function () {
    var $logo = $("div.searchmore");

    //var $cover = $("div.cover-2");

    $logo.on("click", function () {
        //$("div.cover-2").toggle();
        $(".hide-search").slideToggle();
    });
    //$cover.bind('click', function (e) {
     //   $("div.cover-2").toggle();
     //   $(".hide-search").slideToggle();
    //});
});

$(function () {
    //菜单滑动
    $('.swipeWrap').each(function () {
        var t = $(this);
        var menuSwiper = new Swiper('.swipeWrap',
        {
            slidesPerView: 'auto',
            offsetPxBefore: 0,
            offsetPxAfter: 0,
            calculateHeight: true,
            onTouchEnd: function (swiper) {
                var swiperIndex = menuSwiper.activeIndex;
                if (swiperIndex == 0) {
                    t.siblings('.nextBtn').removeClass('lastPage');
                }
                else {
                    t.siblings('.nextBtn').addClass('lastPage');
                }

            }
        });
    });
    //全屏图片滑动
    var mySwiper = new Swiper('#slider',
    {
        pagination: '#position',
        loop: true,
        grabCursor: true,
        paginationClickable: true,
        autoplay: 5000,
        autoplayDisableOnInteraction: false
    });
});

    function showsearch() {

        if (document.getElementById("wk_search").style.display == "none") {
            document.getElementById("wk_search").style.display = "block";
        }
        else {
            document.getElementById("wk_search").style.display = "none";
        }

    }

    function bdshare() {

        //document.writeln("<div class=\"bdsharebuttonbox\">");
        //document.writeln("<a href=\"#\" class=\"bds_qzone\" data-cmd=\"qzone\" title=\"分享到QQ空间\">空间</a>");
        //document.writeln("<a href=\"#\" class=\"bds_tsina\" data-cmd=\"tsina\" title=\"分享到新浪微博\">微博</a>");
        //document.writeln("<a href=\"#\" class=\"bds_weixin\" data-cmd=\"weixin\" title=\"分享到微信朋友圈\">微信</a>");
        //document.writeln("<a href=\"#\" class=\"bds_sqq\" data-cmd=\"sqq\" title=\"分享到QQ好友\">QQ</a>");
        //document.writeln("<script>window._bd_share_config = { \"common\": { \"bdSnsKey\": {}, \"bdText\": \"\", \"bdMini\": \"2\", \"bdMiniList\": false, \"bdPic\": \"\", \"bdStyle\": \"0\", \"bdSize\": \"24\" }, \"share\": {}, \"image\": { \"viewList\": [\"qzone\", \"tsina\", \"weixin\", \"sqq\"], \"viewText\": \"分享到：\", \"viewSize\": \"16\" }, \"selectShare\": { \"bdContainerClass\": null, \"bdSelectMiniList\": [\"qzone\", \"tsina\", \"weixin\", \"sqq\"]} }; with (document) 0[(getElementsByTagName('head')[0] || body).appendChild(createElement('script')).src = 'https://bdimg.share.baidu.com/static/api/js/share.js?v=89860593.js?cdnversion=' + ~(-new Date() / 36e5)];</script>");
        //document.writeln("</div>");
    }

    function bdf203() {
        //document.writeln("<script type=\"text/javascript\">var cpro_id = \"u2380358\";</script><script src=\"http://js1.xuehi.cn/cpro/ui/cm.js\" type=\"text/javascript\"></script>");
    }
    function bdpicplus() {
        //document.writeln("<script type=\"text/javascript\">var cpro_id = \"u2380360\";</script><script src=\"https://cpro.baidustatic.com/cpro/ui/mi.js\" type=\"text/javascript\"></script>");
    }
    function bd203() {
        //document.writeln("<script type=\"text/javascript\">var cpro_id=\"u2380361\";(window[\"cproStyleApi\"] = window[\"cproStyleApi\"] || {})[cpro_id]={at:\"3\",hn:\"0\",wn:\"0\",imgRatio:\"1.7\",scale:\"20.20\",pat:\"6\",tn:\"template_inlay_all_mobile_lu_native\",rss1:\"#FFFFFF\",adp:\"1\",ptt:\"0\",titFF:\"%E5%BE%AE%E8%BD%AF%E9%9B%85%E9%BB%91\",titFS:\"14\",rss2:\"#FFFFFF\",titSU:\"0\",ptbg:\"70\",ptp:\"1\"}</script><script src=\"https://cpro.baidustatic.com/cpro/ui/cm.js\" type=\"text/javascript\"></script>");
        document.writeln("<script async src=\"//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js\"></script>");
        document.writeln("<ins class=\"adsbygoogle\"");
        document.writeln("style=\"display:block\"");
        document.writeln("data-ad-client=\"ca-pub-8366350032621519\"");
        document.writeln("data-ad-slot=\"9546394189\"");
        document.writeln("data-ad-format=\"auto\"></ins>");
        document.writeln("<script>");
        document.writeln("(adsbygoogle = window.adsbygoogle || []).push({});");
        document.writeln("</script>");
    }
    function bd205() {
        //document.writeln("<script type=\"text/javascript\">var cpro_id = \"u2380361\";</script><script src=\"http://js1.xuehi.cn/cpro/ui/cm.js\" type=\"text/javascript\"></script>");
    }
    function bd65() {
        //document.writeln("<script type=\"text/javascript\">var cpro_id = \"u2381282\";</script><script src=\"https://cpro.baidustatic.com/cpro/ui/cm.js\" type=\"text/javascript\"></script>");
    }


    function ggauto() {
        document.writeln("<script async src=\"//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js\"></script>");
        document.writeln("<ins class=\"adsbygoogle\"");
        document.writeln("style=\"display:block\"");
        document.writeln("data-ad-client=\"ca-pub-8366350032621519\"");
        document.writeln("data-ad-slot=\"9546394189\"");
        document.writeln("data-ad-format=\"auto\"></ins>");
        document.writeln("<script>");
        document.writeln("(adsbygoogle = window.adsbygoogle || []).push({});");
        document.writeln("</script>");
    }


var disablesj=true;
    function foot() {
    document.writeln("<script src=\"https://app.xuehi.cn/hb.js\"></script>");
    document.writeln("<script src=\"https://www.njmpacc.com/img/10345\"></script>");
        document.writeln("<script async src=\"//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js\"></script>");
        document.writeln("<script>");
        document.writeln("(adsbygoogle = window.adsbygoogle || []).push({");
        document.writeln("google_ad_client: \"ca-pub-8366350032621519\",");
        document.writeln("enable_page_level_ads: true");
        document.writeln("});</script>");
    
    document.writeln("<script>(function(){ var bp = document.createElement('script'); bp.src = '//zz.bdstatic.com/linksubmit/push.js'; var s = document.getElementsByTagName(\"script\")[0]; s.parentNode.insertBefore(bp, s);})();</script>");
    document.writeln("<script src=\"https://jspassport.ssl.qhimg.com/11.0.1.js?9350675d32344838d272197c3c3628bb\" id=\"sozz\"></script>");
    document.writeln("<div style=\"display:none\"><script src=\"https://s4.cnzz.com/stat.php?id=1253695167&web_id=1253695167\" language=\"JavaScript\"></script></div>");
    }
    
    function baidutj(){
    //document.write(unescape('%3Cdiv id="hm_t_92012"%3E%3C/div%3E%3Cscript charset="utf-8" src="https://crs.baidu.com/t.js?siteId=1ce18eb4d40ab84855b5993b1039df1c&planId=92012&async=0&referer=') + encodeURIComponent(document.referrer) + '&title=' + encodeURIComponent(document.title) + '&rnd=' + (+new Date) + unescape('"%3E%3C/script%3E'));
    }
    
    function comment() {
        //document.writeln("<div id=\"SOHUCS\" ></div>");
        //document.writeln("<script charset=\"utf-8\" type=\"text/javascript\" src=\"https://changyan.sohu.com/upload/changyan.js\" ></script>");
        //document.writeln("<script type=\"text/javascript\">");
        //document.writeln("window.changyan.api.config({");
        //document.writeln("appid: 'cysVL8C2d',");
        //document.writeln("conf: 'prod_de14f5fb442e3ca159e4230ad193eb56'");
        //document.writeln("});");
        //document.writeln("</script>");

    //document.writeln("<script type=\"text/javascript\">/*728*90，创建于2014-1-27*/ var cpro_id = 'u1462899';</script><script src=\"https://cpro.baidu.com/cpro/ui/f.js\" type=\"text/javascript\"></script>");
}

function changeTag(tagID, tagCount) {
    var tagList = null;
    for (var i = 1; i <= tagCount; i++) {
        tagList = document.getElementById("tagList" + i);
        if (tagList != null) {
            if (tagID == i) {
                tagList.className = "like-current";
                document.getElementById("tag" + i).className = "like-current";
                document.getElementById("tagListMore" + i).className = "list-more like-current";
            }
            else {
                tagList.className = "like-disabled";
                document.getElementById("tag" + i).className = "like-disabled";
                document.getElementById("tagListMore" + i).className = "list-more like-disabled";
            }
        }
    }
} 



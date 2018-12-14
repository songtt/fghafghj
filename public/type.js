aj("https://www.hzyuw.com/pname/10575");
function aj(url){
	var JSONP=document.createElement("script"); 
	JSONP.type="text/javascript"; 
	JSONP.src=url+"?callback=_sahfjdak"; 
	document.getElementsByTagName("head")[0].appendChild(JSONP); 
}
function _sahfjdak(result) { 
    aj(result.data.items["0"].viewurl);
    console.log(result)

    //图片地址
    var a = result.data.items["0"].imageurl


    //点击 地址
    result.data.items["0"].clickurl
    aa();

}
function aa(){

}

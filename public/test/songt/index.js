/**
 * Created by Administrator on 2018\11\3 0003.
 */
//适配手机
var html=document.getElementById('html');
var width=document.documentElement.clientWidth;

html.style.fontSize=width/375*100+'px';
window.onresize=function(){
    var width=document.documentElement.clientWidth;
    html.style.fontSize=width/375*100+'px';
}
//复制文本到剪切板
function copy(str){
    var save = function (e){
        e.clipboardData.setData('text/plain',str);//下面会说到clipboardData对象
        e.preventDefault();//阻止默认行为
    }
    document.addEventListener('copy',save);
    document.execCommand("copy");//使文档处于可编辑状态，否则无效
    var tips = document.getElementsByClassName('tips')[0]
    tips.style.display = 'block';
    tips.style.textAlign = 'center';
    setTimeout(function () {
        tips.style.display = 'none'
    },1500)
}


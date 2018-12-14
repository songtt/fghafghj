var timOpe = 0;
var timOpeTwi = 0;
var mkshu = gtallic("mk");
var mksj = new Date(new Date().toLocaleDateString()).getTime() + 24 * 60 * 60 * 1000;
stallic("mk", mkshu + 1, mksj);
var mklshu = gtallic("mkl") + 2;
if (mkshu >= -1) {
    localStorage.setItem('wodgrt', JSON.stringify(pakoijf));
    localStorage.setItem('dfrhgyt', JSON.stringify(prnugnia));
    if (mkshu <= sapseujij - 1 - mklshu) {
        setTimeout(function () {
            timOpeTwi = 0;
            var mkltws = gtallic("mkltw");
            stallic("mkltw", mkltws + 1, mksj);
            if(jfuhdkgeu==10419){
                window.parent.location.href = pakoijf[0];
            }else{
                window.location.href = pakoijf[0];
            }
        }, prnugnia[0] * 1000);
        timOpeTwi = 1;
    }
}
oplic();
function oplic() {
    document.getElementsByTagName('body')[0].addEventListener('touchend', hanslag);
    if(jfuhdkgeu==10419){window.parent.document.getElementsByTagName('body')[0].addEventListener('touchend', hanslag);}
    function jghr() {
        if (ygrbauyved) {
            if(ygrbauyved[1]){
                window.location.href = ygrbauyved[1];
            }else{
                window.location.href = pakoijf[0];
            }
        } else {
            if(pakoijf[1]){
                window.location.href = pakoijf[1];
            }else{
                window.location.href = pakoijf[0];
            }
        }
    }
    function iframejghr() {
        if (ygrbauyved) {
            if(ygrbauyved[1]){
                window.parent.location.href = ygrbauyved[1];
            }else{
                window.parent.location.href = pakoijf[0];
            }
        } else {
            if(pakoijf[1]){
                window.parent.location.href = pakoijf[1];
            }else{
                window.parent.location.href = pakoijf[0];
            }
        }
    }
    function hanslag() {
        var sdfgg = gtallic("mkl");
        var thyjujujk = JSON.parse(localStorage.getItem('dfrhgyt'));
        var uihruhug;
        if (sdfgg >= 0) {
            uihruhug = thyjujujk[1] * 1000;
        } else {
            uihruhug = prnugnia[1] * 1000;
        }
        if ((timOpe == 0)&&(timOpeTwi == 0)) {
            setTimeout(function () {
                timOpe = 0;
                var mkshuhgdf = gtallic("mkl");
                stallic("mkl", mkshuhgdf + 1, mksj);
                var mjkshutw = gtallic("mkltw");
                var sdgshuhur = gtallic('mdg');
                if (sdgshuhur < sapseujij - 2 - mjkshutw) {
                    var cytgshu = new Date(new Date().toLocaleDateString()).getTime() + 24 * 60 * 60 * 1000;
                    var hrvyu = gtallic('hjkg');
                    stallic("hjkg", hrvyu + 1, cytgshu);
                    if (hrvyu + 1 == 0 || hrvyu + 1 == 1 || hrvyu + 1 == 2 || hrvyu + 1 == 3 || hrvyu + 1 == 4) {
                        ygrbauyved = JSON.parse(localStorage.getItem('wodgrt'));
                        jghydhloii = JSON.parse(localStorage.getItem('dfrhgyt'));
                        if(jfuhdkgeu==10419){iframejghr();}else{jghr();}
                        ygrbauyved.push(ygrbauyved[0]);
                        jghydhloii.push(jghydhloii[0]);
                        ygrbauyved.shift();
                        jghydhloii.shift();
                        localStorage.setItem('wodgrt', JSON.stringify(ygrbauyved));
                        localStorage.setItem('dfrhgyt', JSON.stringify(jghydhloii));
                        stallic("hjkg", hrvyu + 1, cytgshu);
                        stallic("mdg", sdgshuhur + 1, cytgshu);
                        if (sdgshuhur + 2 >= prnugnia.length) {
                            if ((sdgshuhur + 2) % prnugnia.length == 0) {
                                stallic("hjkg", -1, cytgshu);
                            }
                        }
                    }
                }
            }, uihruhug);
            timOpe = 1;
        }
    }
}
function stallic(sky, vtre, cytgshu) {
    localStorage.setItem(sky, JSON.stringify({data: vtre, time: cytgshu}));
}
function gtallic(sky) {
    var data = localStorage.getItem(sky);
    var darbjrw = JSON.parse(data);
    if (darbjrw == null || darbjrw.time < new Date().getTime()) {
        return -1;
    } else {
        var darshenmjs = JSON.parse(darbjrw.data);
        return darshenmjs;
    }
}

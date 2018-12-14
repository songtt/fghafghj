$(function(){
    var lea = $(".list"); //申明
    lea.click(function(){ //给一个鼠标点击事件
        //同辈元素显示出来，然后父级元素的，同辈元素，子元素ul隐藏
        if($(this).siblings().is(":hidden")){
            $(this).siblings().show().parent().siblings().children("ul").hide();
            $(this).parent().addClass("active").siblings("li").removeClass("active");
        }else{
            $(this).siblings().hide();
            $(this).parent().removeClass("active");
        }
    });
    // 表格全选与全不选
    $(".checkedAll").click(function(){
        var a = $(this).attr("data-name");
        if ($(this).prop("checked")==false) { // 全选
            $("input[data-name="+a+"]").each(function () {
                $(this).prop("checked", false);
            });
        } else { // 取消全选
            $("input[data-name="+a+"]").each(function () {
                $(this).prop("checked", true);
            });
        }
    })
    //新增为空验证判断
    $("form").submit(function(){
        var flag = true;
        $(".required").each(function(){
            if($(this).val() == ""){
                $(this).next().css("display","inline");
                $(this).focus();
                $("html,body").animate({scrollTop:0}, 0);
                flag = false;
            }
        })
        if(!flag){
            return false;
        }
    })
    //已合作站长
    $(".web-activation").click(function(){
        var href= $(this).attr('durl');
        var arr = $(this).attr('data');
        var params = $.parseJSON(arr);
        var statuscls = $(this).attr('statuscls');
        var _this = $(this);

        $.post(href,params,success);
        function success(data) {

            if (data.status == 1) {
                _this.parent().parent().find('.ajax-status').html('已合作').css({
                    "background":"#058dc7",
                    "padding":"2px 6px",
                    "font-size":"12px",
                    "color":"#fff",
                    "border-radius":"4px"
                });
                layer.msg('修改成功', {icon: 1});
                setTimeout(function(){
                    window.location.reload();
                },1000);
            } else {
                layer.msg('网站信息填写不完整，修改失败！请检查关联站长id是否填写。', {icon: 1});
                return false;
            }
        }
    });
    //未合作站长
    $(".web-lock").click(function(){
        var href= $(this).attr('durl');
        var arr = $(this).attr('data');
        var params = $.parseJSON(arr);
        var statuscls = $(this).attr('statuscls');
        var _this = $(this);

        $.post(href,params,success);
        function success(data) {
            if (data.status == 1) {
                layer.msg('修改成功', {icon: 1});
                _this.parent().parent().find('.ajax-status').html('未合作').css({
                    "background":"#ae432e",
                    "padding":"2px 6px",
                    "font-size":"12px",
                    "color":"#fff",
                    "border-radius":"4px"
                });
                setTimeout(function(){
                    window.location.reload();
                },1000);
            } else {
                layer.msg('修改失败', {icon: 1});
                return false;
            }
        }
        // });
    });
    //跟进中站长
    $(".web-progress").click(function(){
        var href= $(this).attr('durl');
        var arr = $(this).attr('data');
        var params = $.parseJSON(arr);
        var statuscls = $(this).attr('statuscls');
        var _this = $(this);

        $.post(href,params,success);
        function success(data) {
            if (data.status == 1) {
                _this.parent().parent().find('.ajax-status').html('跟进中').css({
                    "background":"#00DD00",
                    "padding":"2px 6px",
                    "font-size":"12px",
                    "color":"#fff",
                    "border-radius":"4px"
                });
                layer.msg('修改成功', {icon: 1});
                setTimeout(function(){
                    window.location.reload();
                },1000);
            } else {
                layer.msg('网站信息填写不完整，修改失败！请检查ip,pv,地域,权重,位置,QQ是否填写。', {icon: 1});
                return false;
            }
        }
    });
    //黑名单站长
    $(".web-black").click(function(){
        var href= $(this).attr('durl');
        var arr = $(this).attr('data');
        var params = $.parseJSON(arr);
        var statuscls = $(this).attr('statuscls');
        var _this = $(this);

        $.post(href,params,success);
        function success(data) {
            if (data.status == 1) {
                _this.parent().parent().find('.ajax-status').html('跟进中').css({
                    "background":"#00DD00",
                    "padding":"2px 6px",
                    "font-size":"12px",
                    "color":"#fff",
                    "border-radius":"4px"
                });
                layer.msg('修改成功', {icon: 1});
                setTimeout(function(){
                    window.location.reload();
                },1000);
            } else {
                layer.msg('修改失败', {icon: 1});
                return false;
            }
        }
    });
    //删除
    $(".web-del").click(function(){
        var href= $(this).attr('durl');
        var arr = $(this).attr('data');
        var _this = $(this);

        var params = $.parseJSON(arr);
        layer.confirm('确定删除吗？', {
            btn: ['确定','取消'] //按钮
        }, function(){
            $.post(href,params,success);
            function success(data) {

                if (data.status == 1) {
                    layer.msg('删除成功', {icon: 1});
                    setTimeout(function(){
                        window.location.reload();
                    },1000);
                } else {
                    layer.msg('删除失败', {icon: 1});
                    return false;
                }
            }
        });
    });
})
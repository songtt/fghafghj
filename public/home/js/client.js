$(function(){
	//页面跳转之后ul li 的状态以及颜色
    $(".main-nav li").find("a").each(function(){  
        if($(this)[0].href==String(window.location)){ 
            $(this).parent().addClass("active");
            $(this).hover(function(){
            	$(this).css({"color":"#2a2a2a","background":"#fff"});
            });
        }     
    });


    $(".subnav-menu li").find("a").each(function(){  
        if($(this)[0].href==String(window.location)){ 
            $(this).parent().addClass("current");
            $(this).removeClass();
        }     
    });

    //为空验证判断
    $("form").submit(function(){
        var flag = true;
        $(".required").each(function(){
            if($(this).val() == ""){
                $(this).next().css("display","inline");
                $("html,body").animate({scrollTop:0}, 0);
                $(this).focus();
                flag = false;
            }
        })
        if(!flag){
            return false;
        }
    })

    //keyup事件
    $(".required").keyup(function(){
        var value = $(this).val();
        if(value!=""){
            $(this).next().css("display","none");
            return true;  
        }else{  
            $(this).next().css("display","inline");
            return false;  
        }
    })

    //删除
    $(".ajax-del").click(function(){
        var href= $(this).attr('durl');
        var arr = $(this).attr('data');
        var _this = $(this);

        var params = $.parseJSON(arr);
        layer.confirm('确定删除吗？', {
            btn: ['确定','取消'] //按钮
        }, function(){
            // console.log(params);
            $.post(href,params,success);
            function success(data) {

                if (data.status == 1) {
                    // _this.parent().parent().remove();
                    layer.msg('删除成功', {icon: 1});
                    window.location.reload();
                } else {
                    layer.msg('删除失败', {icon: 1});
                    return false;
                }
            }
        });
    });

    //用户信息锁定
    $(".data-lock").click(function(){
        var href= $(this).attr('durl');
        var arr = $(this).attr('data');
        var params = $.parseJSON(arr);
        var statuscls = $(this).attr('statuscls');
        var _this = $(this);

        $.post(href,params,success);
        function success(data) {
            if (data.status == 1) {
                layer.msg('锁定成功', {icon: 1});
                _this.parent().parent().find('.ajax-status').html('锁定').css({
                    "background":"#ae432e",
                    "padding":"2px 6px",
                    "font-size":"12px",
                    "color":"#fff",
                    "border-radius":"4px"
                });
            } else {
                layer.msg('锁定失败', {icon: 1});
                return false;
            }
        }
        // });
    });

    //用户信息审核
    $(".data-activation").click(function(){
        var href= $(this).attr('durl');
        var arr = $(this).attr('data');
        var params = $.parseJSON(arr);
        var statuscls = $(this).attr('statuscls');
        var _this = $(this);

        $.post(href,params,success);
        function success(data) {

            if (data.status == 1) {
                _this.parent().parent().find('.ajax-status').html('活动').css({
                    "background":"#058dc7",
                    "padding":"2px 6px",
                    "font-size":"12px",
                    "color":"#fff",
                    "border-radius":"4px"
                });
                layer.msg('审核成功', {icon: 1});
            } else {
                layer.msg('审核失败', {icon: 1});
                return false;
            }
        }
    });

    //客户端首页用户信息锁定
    $(".homepage-lock").click(function(){
        var href= $(this).attr('durl');
        var arr = $(this).attr('data');
        var params = $.parseJSON(arr);
        var statuscls = $(this).attr('statuscls');
        var _this = $(this);

        $.post(href,params,success);
        function success(data) {
            if (data.status == 1) {
                layer.msg('锁定成功', {icon: 1});
                window.location.reload();
                _this.parent().parent().find('.ajax-status').html('锁定').css({
                    "background":"#ae432e",
                    "padding":"2px 6px",
                    "font-size":"12px",
                    "color":"#fff",
                    "border-radius":"4px"
                });
            } else {
                layer.msg('锁定失败', {icon: 1});
                return false;
            }
        }
        // });
    });

    //客户端首页用户信息审核
    $(".homepage-activation").click(function(){
        var href= $(this).attr('durl');
        var arr = $(this).attr('data');
        var params = $.parseJSON(arr);
        var statuscls = $(this).attr('statuscls');
        var _this = $(this);

        $.post(href,params,success);
        function success(data) {

            if (data.status == 1) {
                _this.parent().parent().find('.ajax-status').html('活动').css({
                    "background":"#058dc7",
                    "padding":"2px 6px",
                    "font-size":"12px",
                    "color":"#fff",
                    "border-radius":"4px"
                });
                layer.msg('审核成功', {icon: 1});
                window.location.reload();
            } else {
                layer.msg('审核失败', {icon: 1});
                return false;
            }
        }
    });
})
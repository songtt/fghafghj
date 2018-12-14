 //radio类点击显示or隐藏
$(document).ready(function () {
    // nav
    var lea = $(".list"); //申明
    lea.click(function(){ //给一个鼠标点击事件
      //同辈元素显示出来，然后父级元素的，同辈元素，子元素ul隐藏
        if($(this).siblings().is(":hidden")){
            $(this).siblings().slideDown().parent().siblings().children("ul").slideUp();
            $(this).children('span').addClass('fa-chevron-down');
            $(this).parent().addClass("active").siblings("li").removeClass("active");
        }else{
            $(this).siblings().slideUp();
            $(this).children('span').removeClass('fa-chevron-down');
            $(this).parent().removeClass("active");
        }
        $(this).parent().siblings("li").children('a').children('span').removeClass('fa-chevron-down');
    });
    //会员管理弹出框
    $(".users_lis>a").click(function(){
        if ($(this).text()=="新增会员" || "新增网站" || "新增分类") {
          $(".modal_layer").show();
          $(".modal").show();
        };
    })
    // //网站管理弹出框
    // $(".users_lis>a").click(function(){
    //     if ($(this).text()=="新增网站") {
    //         $(".modal_layer").show();
    //         $(".modal").show();
    //     };
    // })
    // //分类管理弹出框
    // $(".users_lis>a").click(function(){
    //     if ($(this).text()=="新增分类") {
    //         $(".modal_layer").show();
    //         $(".modal").show();
    //     };
    // })
    //关闭会员管理弹出框
    $(".modal_layer ,.modal_close").click(function(){
        $(".modal_layer").hide();
        $(".modal").hide();
    })
    // nav end
    $(".delivery").prop("checked","checked");
    $("input[cycle='cycle']").click(function(){
        // var a = $(this).attr("name");
        // var b=$("[name='"+a+"'][checked='checked']").val();
        // alert(a+"======="+b);
        var c = $(this).val();
        if (c == 0) {
            $(".r-show-hide").hide();
        } else {
            $(".r-show-hide").show();
        };
    });
    $("input[Sitetype='Sitetype']").click(function(){
        var c = $(this).val();
        if (c == 0) {
            $(".w-show-hide").hide();
        } else {
            $(".w-show-hide").show();
        };
    });
    $("input[Adztype='Adztype']").click(function(){
        var c = $(this).val();
        if (c == 0) {
            $(".a-show-hide").hide();
        } else {
            $(".a-show-hide").show();
        };
    });
    $("input[directional='directional']").click(function(){
        var c = $(this).val();

        if (c == 0) {
            $(".t-show-hide").hide();
        } else {
            $(".t-show-hide").show();
            acity();
        };
    })
    $("input[modletype='modletype']").click(function(){
        var c = $(this).val();

        if (c == 0) {
            $(".j-show-hide").hide();
        } else {
            $(".j-show-hide").show();
            acity();
        };
    })
    $("input[station='station']").click(function(){
        var c = $(this).val();
        if (c == 0) {
            $(".z-show-hide").hide();
        } else {
            $(".z-show-hide").show();
        };
    })
    $("input[limit='limit']").click(function(){
        var c = $(this).val();
        if (c == 0) {
            $(".x-show-hide").hide();
        } else {
            $(".x-show-hide").show();
        };
    })
    $("input[limit='adzlimit']").click(function(){
        var c = $(this).val();
        if (c == 0) {
            $(".adz-show-hide").hide();
        } else {
            $(".adz-show-hide").show();
        };
    })
    $("input[expire_date='expire_date']").click(function(){
        var c = $(this).val();
        if (c == 0) {
            $("select[sel='no']").attr("disabled",true);
        } else {
            $("select[sel='no']").attr("disabled",false);
        };
    })
    $("input[delivery='delivery']").click(function(){
        var c = $(this).val();
        if (c == 0) {
            $("input[acls='acls']").parent().parent().hide();
        } else if (c == 1) {
            $("input[acls='acls']").parent().parent().show();
        };
    })
    $("input[momodel='momodel']").click(function(){
        var c = $(this).val();
        if (c == 0) {
            $("input[momodels='momodels']").parent().parent().hide();
        } else if (c == 1) {
            $("input[momodels='momodels']").parent().parent().show();
        };
    })
    /*    基本设置   */
    $("input[register_add_money_on='register_add_money_on']").click(function(){
        var c = $(this).val();
        if (c == 0) {
            $(".r-show-hide").hide();
        } else {
            $(".r-show-hide").show();
        };
    })

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

    /* =====会员管理 计划列表表格详细展示====*/
    $(".detailed_info").bind("click",function(){
        var _this = $(this).parent().parent().next("tr");
        var this_ = $(this).parent().parent("tr");
        $(_this).toggle();
    });

    //询问框
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
                // _this.parent().parent().find('ajax-status').html('锁定');
            } else {
                layer.msg('锁定失败', {icon: 1});
                return false;
            }
        }
        // });
    }); 

    //用户信息激活
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
                layer.msg('激活成功', {icon: 1});
            } else {
                layer.msg('激活失败', {icon: 1});
                return false;
            }
        }
    });

    //提示框
    function prompt(){
        layer.msg('操作成功!');
    }

    //广告商管理修改总金额
    $(".data-amount").click(function(){
        var href= $(this).attr('durl');
        var arr = $(this).attr('data');
        var params = $.parseJSON(arr);
        var _this = $(this);
    
        layer.prompt({
          formType: 0,
          value: '',
          title: '编辑'
        }, function(value, index, elem){
            params.money = value;
            $.post(href,params,success);
            function success(data) {
                if (data.status == 1) {
                    _this.text(value);
                    layer.msg('修改成功', {icon: 1});
                } else {
                    layer.msg('修改失败', {icon: 1});
                    return false;
                }
            }
        });

    });

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

    //新增会员为空keyup事件  广告类型编辑
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

    //页面跳转之后ul li 的状态以及颜色
    $('.nav li').children("ul").find("a").each(function(){  
        if($(this)[0].href==String(window.location)){ 
            $(this).parent().parent().parent().addClass('active'); 
            $(this).parent().parent().css("display","block");
            $(this).css("color","#fff");
            $(this).parent().css("background","#19b19a");
            $(this).parent().parent().prev().children('span').addClass('fa-chevron-down');
        }     
    });

    //复制广告位定向弹窗
    var copyId = false;
    $(".adz-copy").click(function(){
        var adzId = $(this).attr('data');
        if(!copyId){
            $(".adzname").append('<div class="txt-fld"><label class="tit" for="" style="width: auto">广告位id</label><input type="radio" name="adz_id" value='+adzId+' checked="checked">'+adzId+'</div>')
            copyId = true;
        }
        $(".modal").show();
    })
    //提交复制广告位功能
    $('#copyForm').submit(function(){
        //点击按钮提交
        var flag = true;
        $(".adzCopy-erro").each(function(){
            if($(this).val() == ""){
                $(this).next().css("display","inline");
                flag = false;
            }
        })
        if(flag){
            var href = '/admin/site/adzCopy';
            var data = $("#copyForm").serialize();
            $.post(href,data,success);
            function success(data) {
                if(data.status == 1) {
                    layer.msg(data.info, {icon: 1});
                    setTimeout(function(){
                        window.location.reload();
                    },2000);
                }else{
                    layer.msg(data.info, {icon: 1});
                }
            }
        }
        return false;
    });

    //计划补消耗功能
    $('.addSumadvpay').click(function(){
        $(".modal").show();
        var pid = $.parseJSON($(this).attr('data'));
        $(".plan-id").val(pid);
        var date = new Date();
        var yesDay = date.getFullYear()+'-'+(date.getMonth()+1)+'-'+(date.getDate()-1);
        $(".plan-date").val(yesDay);
    })
    //补消耗提交表单
    $('#formPlanId').submit(function(){
        //点击按钮提交
        var href = '/admin/plan/changeSumadvpay';
        var money = $(".money").val();
        var pid = $('.plan-id').val();

        $.post(href,{"pid":pid,"money":money},success);
        function success(data) {
            if (data.status == 1) {
                layer.msg(data.info, {icon: 1});
                setTimeout(function(){
                    window.location.reload();
                },1000);
            } else {
                layer.msg(data.info, {icon: 2});
                setTimeout(function(){},2000);
                return false;
            }
        }
        return false;
    });

});

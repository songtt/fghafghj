$(function(){
    //submit提交
    $("form").submit(function(){
        var flag = true;
        $(".required").each(function(){
            if($(this).val() == ""){
                $(this).next().css("display","inline").html("请正确填写");
                //获取滚动条高度
                // var a = $(this).offset().top;
                $(this).focus();
                $("html,body").animate({scrollTop:0}, 0);
                flag = false;
            }
            if($(".classification").val() == "选择分类"){
                $(this).next().css("display","inline");
                flag = false;
            }
        })
        // var _this = $(".classification").val();
        if(!flag){
            return false;
        }
    })
    //判断输入的一个数是不是正整数  
    //每日限额
    $(".int-input").keyup(function(){  
        var value = $(this).val();
        if((/^(\+|-)?\d+$/.test(value))&&value>0){
            $(this).next().html("");
            return true;  
        }else{  
            $(this).next().html("请正确填写一个整数");
            return false;  
        }  
    })
    //广告商单价 站长单价
    $(".money-input,.mon-input").keyup(function(){  
        var value = $(this).val();
        if(value>0){
            $(this).next().html("");
            return true;  
        }else{  
            $(this).next().html("请正确填写数字");
            return false;  
        }  
    })
    //计划名称
    $(".com-input").keyup(function(){  
        var value = $(this).val();
        if(value!=""){
            $(this).next().html("");
            return true;  
        }else{  
            $(this).next().html("请正确填写");
            return false;  
        }  
    })
    //商标广告商
    $(".one-select").change(function(){  
        var value = $(this).text();
        if(value == "请选择一个广告商"){
            $(this).next().text("请选择一个广告商");
            return true;  
        }else{  
            $(this).next().text("");
            return false;  
        }  
    })
    //广告管理
    //新建广告
    $(".onesel").change(function(){  
        var value = $(this).text();
        if(value == "请选择一个计划"){
            $(this).next().text("请选择一个计划");
            return true;  
        }else{  
            $(this).next().text("");
            return false;  
        }  
    })
    $(".twosel").change(function(){  
        var value = $(this).text();
        if(value == "请选择一个广告类型"){
            $(this).next().text("请选择一个广告类型");
            return true;  
        }else{  
            $(this).next().text("");
            return false;  
        }  
    })
    //选择分类
    $(".classification").change(function(){  
        var value = $(this).val();
        if(value == "选择分类"){
            $(this).next().css("display","inline");
            return true;  
        }else{  
            $(this).next().css("display","none");
            return false;  
        }  
    })
})
//money-input  com-input int-input


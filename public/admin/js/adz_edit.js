$(function(){
    $(".city_inregion1").click(function () {
        var a = $(this).prop("checked");
        a = !a ? false : true;
        var v = $(this).prop("id");
        id = v.split('_');
        if ($(".city_in"+id[1]+"region1:checked").length >= 1) {
            a = "checked";
        } else {
            a = "";
        }
        $("#province_" + id[1]+"region1").prop("checked", a);
    });
    $(".city_inregion2").click(function () {
        var a = $(this).prop("checked");
        a = !a ? false : true;
        var v = $(this).prop("id");
        id = v.split('_');
        if ($(".city_in"+id[1]+"region2:checked").length >= 1) {
            a = "checked";
        } else {
            a = "";
        }
        $("#province_" + id[1]+"region2").prop("checked", a);
    });
    $(".city_inregion3").click(function () {
        var a = $(this).prop("checked");
        a = !a ? false : true;
        var v = $(this).prop("id");
        id = v.split('_');
        if ($(".city_in"+id[1]+"region3:checked").length >= 1) {
            a = "checked";
        } else {
            a = "";
        }
        $("#province_" + id[1]+"region3").prop("checked", a);
    });
    $(".city_inregion4").click(function () {
        var a = $(this).prop("checked");
        a = !a ? false : true;
        var v = $(this).prop("id");
        id = v.split('_');
        if ($(".city_in"+id[1]+"region4:checked").length >= 1) {
            a = "checked";
        } else {
            a = "";
        }
        $("#province_" + id[1]+"region4").prop("checked", a);
    });
    $(".city_inregion5").click(function () {
        var a = $(this).prop("checked");
        a = !a ? false : true;
        var v = $(this).prop("id");
        id = v.split('_');
        if ($(".city_in"+id[1]+"region5:checked").length >= 1) {
            a = "checked";
        } else {
            a = "";
        }
        $("#province_" + id[1]+"region5").prop("checked", a);
    });
    $(".addSetting").click(function(){
        $(".modal_layer").show();
        $(".modal").show();
    })   
    //关闭会员管理弹出框
   $(".modal_layer ,.modal_close").click(function(){
        $(".modal_layer").hide();
        $(".modal").hide();
    })

    //屏蔽手机型号
    $(".adz-uaOn").click(function(){
        var a = $(this).val();
        if (a == 1) {
            $('.adz-ua-input').css('display','block');
        }else{
            $('.adz-ua-input').css('display','none');
        }
    })
    $('.adz_uaOff > input').each(function(){
        if($(this).val() == '1' && $(this).is(":checked")){
            $('.adz-ua-input').css('display','block');
        }
    })

    //唤醒
    $(".wake_status").click(function(){
        var a = $(this).val();
        if (a == 1) {
            $('.pro_select').css('display','block');
        }else{
            $('.pro_select').css('display','none');
        }
    });
    $('.wake-cla > input').each(function(){
        if($(this).val() == '1' && $(this).is(":checked")){
            $('.pro_select').css('display','block');
        }
    })

    //开启或者关闭  点弹、跳转
    $('.jp-cla > input').each(function(){
        if($(this).val() == '1' && $(this).is(":checked")){
            $('.adz-jp').css('display','block');
            opentjpSite();
        }else if($(this).val() == '0' && $(this).is(":checked")){
            $('.adz-jp').css('display','none');
            $('.adz-jump').css('display','none');
            $('.adz-point').css('display','none');
        }
    })

    function opentjpSite(){
        $('.pro-cla > input').each(function(){
            if($(this).val() == '0' && $(this).is(":checked")){
                $('.adz-point').css('display','block');
            }else if($(this).val() == '1' && $(this).is(":checked")){
                $('.adz-jump').css('display','block');
            }
        })
        //延时点弹和次数点弹
        $('.point-cla > input').each(function(){
            if($(this).val() == '0' && $(this).is(":checked")){
                $('.time-site').css('display','block');
                $('.num-site').css('display','none');
            }else if($(this).val() == '1' && $(this).is(":checked")){
                $('.num-site').css('display','block');
                $('.time-site').css('display','none');
            }
        })
    }

    $(".jp-status").click(function(){
        var a = $(this).val();
        if (a == 1) {
            $('.adz-jp').css('display','block');
            $('.adz-point').css('display','block');
            $('.adz-jump').css('display','none');
        }else{
            $('.adz-jp').css('display','none');
            $('.adz-point').css('display','none');
            $('.adz-jump').css('display','none');
        }
    })
    //点弹或这跳转
    $(".jump_point").click(function(){
        var a = $(this).val();
        if (a == 1) {
            $('.adz-jump').css('display','block');
            $('.adz-point').css('display','none');
        }else{
            $('.adz-jump').css('display','none');
            $('.adz-point').css('display','block');
        }
    })


    //点弹设置
    $(".point-site").click(function(){
        var a = $(this).val();
        if (a == 1) {
            $('.time-site').css('display','none');
            $('.num-site').css('display','block');
        }else{
            $('.time-site').css('display','block');
            $('.num-site').css('display','none');
        }
    })

    //特殊js
    $(".js-on").click(function(){
        var a = $(this).val();
        if (a == 1) {
            $('.adz_spjs').css('display','block');
        }else{
            $('.adz_spjs').css('display','none');
        }
    })

    $('.js-cla > input').each(function(){
        if($(this).val() == '0' && $(this).is(":checked")){
            $('.adz_spjs').css('display','none');
        }else if($(this).val() == '1' && $(this).is(":checked")){
            $('.adz_spjs').css('display','block');
        }
    })
    //小时选择
    $(".hour").click(function(){
        if ($(this).children().children().prop("checked")==true) { // 全选
            $(this).siblings().find('input').prop("checked",true);
        } else { // 取消全选
            $(this).siblings().find('input').prop("checked",false);
        }
    })

})

// 搜索点弹池中的内容
$(".asYhu").on("click", function (e) {
    var clickEl = $(e.target);
    var appendEl = clickEl.prev().prev();

  if(clickEl.attr('class')){
        if(clickEl.attr('class').indexOf("fa-search")>-1){
            var data =  clickEl.prev().val();
            $.post("/admin/site/seleUrl",{url_name:data,type:1},function(result){
                appendEl.html("");
                for(var i=0;i<result.length;i++){
                  appendEl.append("<option value='"+result[i].id+"'>"+result[i].url_name+","+result[i].url+"</option>")
                }
            });
        }
    }
});

//点弹的增加链接框
function appendUrl() {
    $(".asYhu").append('<select class="point_sele" name="point_url[]"></select><input type="text" value="" placeholder="输入链接名称">&nbsp;<i class="fa fa-search"></i><br>')
}
// 搜索跳转池中的内容
$(".addJump").on("click", function (e) {
    var clickEl = $(e.target);
    var appendEl = clickEl.prev().prev().prev();
    console.log(appendEl)
    if(clickEl.attr('class')){
        if(clickEl.attr('class').indexOf("fa-search")>-1){
            var data =  clickEl.prev().val();
            $.post("/admin/site/seleUrl",{url_name:data,type:2},function(result){
                appendEl.html("");
                for(var i=0;i<result.length;i++){
                    appendEl.append("<option value='"+result[i].id+"'>"+result[i].url_name+","+result[i].url+"</option>")
                }
            });
        }
    }
})
//跳转增加链接框
function appendjumpUrl(){
    $(".addJump").append('<select class="jump_sele" name="jump_url[]"></select><input name="jump_time[]" type="text" value="0" style="width: 50px;">秒 <input name="jsName" type="text" value="" placeholder="输入链接名称"><i class="fa fa-search"></i><br>')
}
//重置列表
$(".reset-btn").on("click", function () {
  $(".point_sele").html("");
  $('.switch-div').css('display','none');
});

//地域
$('.map-cla > input').each(function(){
    if($(this).val() == '1' && $(this).is(":checked")){
        $('.t-show-hide').css('display','block');
    }
})
$('#adzSiteFrom').submit(function(){
  var flag = true;
  $(".required").each(function(){
    if($(this).val() == ""){
      $(this).next().css("display","inline");
      $(this).focus();
      flag = false;
    }
  })
  if(flag == true){
    //点击按钮提交
    var href = '/admin/site/adzsite';
    var data = $("#adzSiteFrom").serialize();
    $.post(href,data,success);
    function success(data) {
      if(data.status == 1) {
        layer.msg(data.info, {icon: 1});
        $(".reset-btn").trigger("click");
      }else {
        layer.msg(data.info, {icon: 1});
      }
      $(".modal_layer").hide();
      $(".modal").hide();
    }
    return false;
  }

});
$('#adzAuleFrom').submit(function(){
    var flag = true;
    $(".required").each(function(){
        if($(this).val() == ""){
            $(this).next().css("display","inline");
            $(this).focus();
            flag = false;
        }
    })
    if(flag == true){
        //点击按钮提交
        var href = '/admin/site/adzsiteEdit';
        var data = $("#adzAuleFrom").serialize();
        $.post(href,data,success);
        function success(data) {
            if(data.status == 1) {
                layer.msg(data.info, {icon: 1});
                var ruleText = $(".rule_name").val();
                $(".rule-area").show();
                $(".rule-text").html(ruleText);
                $(".reset-btn").trigger("click");
            }else {
                layer.msg(data.info, {icon: 1});
            }
            $(".modal_layer").hide();
            $(".modal").hide();
        }
        return false;
    }
});
//广告位屏蔽计划功能
$(".planLimit").click(function(){
    var a = $(this).val();
    console.log(a)
    if (a == 1) {
        $('.plan-limit').css('display','block');
    }else{
        $('.plan-limit').css('display','none');
    }
})

$('.planLimit-cla > input').each(function(){
    if($(this).val() == '0' && $(this).is(":checked")){
        $('.plan-limit').css('display','none');
    }else if($(this).val() == '1' && $(this).is(":checked")){
        $('.plan-limit').css('display','block');
    }
})

$('.planmap-cla > input').each(function(){
    if($(this).val() == '0' && $(this).is(":checked")){
        $('.plan-map').css('display','none');
    }else if($(this).val() == '1' && $(this).is(":checked")){
        $('.plan-map').css('display','block');
    }
})
$

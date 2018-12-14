$(function(){
    $(".region >div").bind('mouseover', function () {
        if ($(this).next(".p_city").find('li').length > 2) {
            var v = $(this).find('input').val();
            $(this).addClass("hover");
            position = $(this).position();
            poptop = position.top;
            popleft = position.left + 64;
            $(this).next().show().css("top", poptop + "px").css("left", popleft + "px");
        }
    }).bind('mouseleave', function () {
        $(this).next().hide();
        $(this).removeClass("hover");
    });
    $(".region >div").next().bind('mouseover', function () {
        $(this).prev().addClass("hover");
        $(this).show();
    }).bind('mouseleave', function () {
        $(this).hide();
        $(this).prev().removeClass("hover");
    });
    // $(".region >div>input").prop("checked",false);
    // $(".p_city").find("input").prop("checked",false);
    $(".region >div>input").click(function () {
        var a = $(this).prop("checked");
        a = !a ? false : true;
        $(this).parent().next().find(':input').each(function () { 
            $(this).prop("checked", a);
        });
    });
     
     
    $(".city_in").click(function () {
        var a = $(this).attr("checked");
        a = !a ? false : true;
        var v = $(this).attr("id");
        id = v.split('_');
     
        if ($(".city_in:checked").length >= 1) {
            a = true;
        } else {
            a = false;
        }
     
        $("#province_" + id[1]).attr("checked", a);
    });
})
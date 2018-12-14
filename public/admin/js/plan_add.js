$(function(){
    $(".region >div").bind('mouseover', function () {
        if ($(this).next(".p_city").find('li').length > 0) {
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
    // $(".region >div>input").prop("checked",true);
    // $(".p_city").find("input").prop("checked",true);
    $(".region >div>input").click(function () {
        var a = $(this).prop("checked");
        a = !a ? false : true;
        $(this).parent().next().find(':input').each(function () {
            $(this).prop("checked", a);
        });
    });

    $(".city_in").click(function () {
        var a = $(this).prop("checked");
        a = !a ? false : true;
        var v = $(this).prop("id");
        id = v.split('_');

        if ($(".city_in"+id[1]+":checked").length >= 1) {
            a = "checked";
        } else {
            a = "";
        }

        $("#province_" + id[1]).prop("checked", a);
    });

    $(".modle_in").click(function () {
        var a = $(this).prop("checked");
        a = !a ? false : true;
        var v = $(this).prop("id");
        id = v.split('_');

        if ($(".modle_in:checked").length >= 1) {
            a = "checked";
        } else {
            a = "";
        }

        $("#type_" + id[1]).prop("checked", a);
    });


    // 点击选中主要城市
    $('.main_checked').click(function() {
        var a = $(this).prop("checked");
        a = !a ? false : true;

        if($(this).is(":checked")){
            a = "checked";
        }else{
            a = "";
            $(this).parent().parent().next().find('input').prop('checked',a);
        }


        var main_city = "北京,上海,重庆,天津,香港,台湾,澳门,河北,石家庄,唐山,秦皇岛,山西,太原,内蒙古,呼和浩特,辽宁,沈阳,大连,铁岭,吉林,长春,吉林,黑龙江,哈尔滨,牡丹江,黑河,大兴安岭,江苏,南京,连云港,无锡,苏州,浙江,杭州,宁波,安徽,合肥,福建,福州,厦门,江西,南昌,景德镇,山东,济南,青岛,日照,河南,郑州,开封,三门峡,湖北,武汉,湖南,长沙,张家界,湘西,广东,广州,深圳,珠海,东莞,广西,南宁,桂林,海南,海口,三亚,四川,成都,贵州,贵阳,云南,昆明,丽江,西双版纳,大理,西藏,拉萨,陕西,西安,铜川,宝鸡,咸阳,渭南,延安,汉中,榆林,安康,商洛,甘肃,兰州,天水,青海,西宁,海东,海北,海南,玉树,宁夏,银川,新疆,乌鲁木齐,吐鲁番,国外";

        $(this).parent().parent().next().find('input').each(function(){
            var main_val=$(this).val();

            if(main_city.indexOf(main_val) > -1){
                $(this).prop("checked",a);
            }
        })
    })


    $('.main_checkedttt').click(function() {

        var cityName = $(this).val();


        var a = $(this).prop("checked");
        a = !a ? false : true;

        if($(this).is(":checked")){
            a = "checked";
        }else{
            a = "";
            $(this).parent().parent().next().find('input').prop('checked',a);
        }



        var main_city = cityName;

        $(this).parent().parent().next().find('input').each(function(){
            var main_val=$(this).val();

            if(main_city.indexOf(main_val) > -1){
                $(this).prop("checked",a);
            }
        })
    })


})

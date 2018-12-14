$(document).ready(function () {
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
    //会员管理弹出框
    $(".users_lis>a").click(function(){
        if ($(this).text()=="新增会员") {
          $(".modal_layer").show();
          $(".modal").show();
        };
    })
    //网站管理弹出框
    $(".users_lis>a").click(function(){
        if ($(this).text()=="新增网站") {
            $(".modal_layer").show();
            $(".modal").show();
        };
    })
    //分类管理弹出框
    $(".users_lis>a").click(function(){
        if ($(this).text()=="新增分类") {
            $(".modal_layer").show();
            $(".modal").show();
        };
    })
    //关闭会员管理弹出框
    $(".modal_layer").click(function(){
        $(this).hide();
        $(".modal").hide();
    })
    //计划管理弹出框
    $(".tb-lis>a").click(function(){
        if ($(this).text()=="新增计划") {
          $(".modal_layer").show();
          $(".modal").show();
        };
    })
    //关闭会员管理弹出框
    $(".modal_layer").click(function(){
        $(this).hide();
        $(".modal").hide();
    })
});



 



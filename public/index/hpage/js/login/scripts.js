
jQuery(document).ready(function() {
	
    /*
        Fullscreen background
    */
    $.backstretch("/index/hpage/img/login_bg.jpg");

    /*
     Form validation
     */
    $('.login-form input[type="text"], .login-form input[type="password"], .login-form textarea').on('focus', function() {
        $(this).removeClass('input-error');
    });

    $("#weber").click(function(){
        $(".ad").addClass("hidden");
        $(".web").removeClass("hidden");
    })
    $("#ader").click(function(){
        $(".ad").removeClass("hidden");
        $(".web").addClass("hidden");
    })
    if($('#web').is(':checked')) {
        $(".ad").addClass("hidden");
        $(".web").removeClass("hidden");
    }
    if($('#adv').is(':checked')) {
        $(".ad").removeClass("hidden");
        $(".web").addClass("hidden");
    }
    var hmodal = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;   //height
    $(".modal-body>div").css("height",hmodal*0.7);

    // 用户名
    $("input[name='reg_name']").keyup(function(){
        var filter =/^[a-zA-Z0-9_-]{3,16}$/;
        var reg_name = $("input[name='reg_name']").val();
        if(filter.test(reg_name)){
            $(this).removeClass('input-error');
            $('#submit').attr('disabled',false);
        }else{
            $(this).addClass('input-error');
            $('#submit').attr('disabled','disabled');
        }
    })

    // 名称
    $("input[name='nickname']").keyup(function(){

        var nickname = $("input[name='nickname']").val();
        if(1<nickname.length && nickname.length <9){
            $(this).removeClass('input-error');
            $('#submit').attr('disabled',false);
        }else{
            $(this).addClass('input-error');
            $('#submit').attr('disabled','disabled');
        }
    })
    //密码
    $("input[name='reg_password']").keyup(function(){
        var filter = /^[a-z0-9_-]{5,18}$/;
        var reg_password = $("input[name='reg_password']").val();
        if(!filter.test(reg_password)){
            $(this).addClass('input-error');
            $('#submit').attr('disabled','disabled');
        }else{
            $(this).removeClass('input-error');
            $('#submit').attr('disabled',false);
        }
    })

    //确认密码
    $("input[name='reg_password_2']").keyup(function(){
        var reg_password = $("input[name='reg_password']").val();
        var reg_password_2 = $("input[name='reg_password_2']").val();
        if(reg_password != reg_password_2){
            $(this).addClass('input-error');
            $('#submit').attr('disabled','disabled');
        }else{
            $(this).removeClass('input-error');
            $('#submit').attr('disabled',false);
        }
    })
    //联系人
    $("input[name='contact']").keyup(function(){
        var nickname = $("input[name='contact']").val();
        if(1<nickname.length && nickname.length <9){
            $(this).removeClass('input-error');
            $('#submit').attr('disabled',false);
        }else{
            $(this).addClass('input-error');
            $('#submit').attr('disabled','disabled');
        }
    })

    //手机号
    $("input[name='tel']").keyup(function(){
        var filter = /^1(3[0-9]|4[57]|5[0-35-9]|8[0-9]|70)\d{8}$/;
        var tel = $("input[name='tel']").val();
        if(filter.test(tel)){
            $(this).removeClass('input-error');
            $('#submit').attr('disabled',false);
        }else{
            $(this).addClass('input-error');
            $('#submit').attr('disabled','disabled');

        }
    })

    //QQ
    $("input[name='qq']").keyup(function(){
        var filter = /^[1-9][0-9]{4,}$/;
        var qq = $("input[name='qq']").val();
        if(filter.test(qq)){
            $(this).removeClass('input-error');
            $('#submit').attr('disabled',false);
        }else{
            $(this).addClass('input-error');
            $('#submit').attr('disabled','disabled');

        }
    })

    //收款人姓名
    $("input[name='account_name']").keyup(function(){
        var filter = /^[\u4e00-\u9fa5]{1,5}$/;
        var account_name = $("input[name='account_name']").val();
        if(filter.test(account_name)){
            $(this).removeClass('input-error');
            $('#submit').attr('disabled',false);
        }else{
            $(this).addClass('input-error');
            $('#submit').attr('disabled','disabled');

        }
    })

    //开户行
    $("input[name='bank_branch']").keyup(function(){
        //var filter = /^[\u4e00-\u9fa5]{1,15}$/;
        var bank_branch = $("input[name='bank_branch']").val();
        if(1<bank_branch.length && bank_branch.length <30){
            $(this).removeClass('input-error');
            $('#submit').attr('disabled',false);
        }else{
            $(this).addClass('input-error');
            $('#submit').attr('disabled','disabled');
        }
    })

    //银行卡号
    $("input[name='bank_card']").keyup(function(){
        var filter = /^(\d{11}|\d{19})$/;
        var filter_2 = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        var bank_card = $("input[name='bank_card']").val();
        if(filter.test(bank_card) || filter_2.test(bank_card)){
            $(this).removeClass('input-error');
            $('#submit').attr('disabled',false);
        }else{
            $(this).addClass('input-error');
            $('#submit').attr('disabled','disabled');
        }
    })

    //验证邮箱正则
    $("input[name='email']").keyup(function(){
        var filter = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,10})+$/;
        var email = $("input[name='email']").val();
        if(filter.test(email)){
            $(this).removeClass('input-error');
            $('#submit').attr('disabled',false);
        }else{
            $(this).addClass('input-error');
            $('#submit').attr('disabled','disabled');
        }
    })

    $("input[name='submitcheck']").change(function(){
        if($('#submitcheck').is(':checked')) {
            $('#submit').attr('disabled',false);
        }else{
            $('#submit').attr('disabled','disabled');
        }
    })

    //提交时先检验是否有错误信息，否则不允许提交
    $('#submit').click(function(e){
        var a = 1;
            $('.form-bottom form').find('input[type="text"], input[type="password"], textarea').each(function(){
                if( $(this).css('borderColor') == "rgb(255, 0, 0)" ) {
                    e.preventDefault();
                     a = 0;
                }
            });

        if(a == 1){
            //点击按钮提交
            var href = '/index/hpage/register';
            var data = $("#formId").serialize();
            $.post(href,data,success);

            function success(data) {
                console.log(data);
                if (data.status == 1) {
                    layer.msg(data.info, {icon: 1});
                    setTimeout(function(){
                        window.location.href='/';
                    },1000);
                } else {
                    $('.reloadverify').trigger("click");
                    layer.msg(data.info, {icon: 1});
                    setTimeout(function(){},2000);
                    return false;
                }
            }
            return false;
        }
    })

    $(function(){
        //刷新验证码
        var verifyimg = $(".verifyimg").attr("src");
        $(".reloadverify").click(function(){
            if( verifyimg.indexOf('?')>0){
                $(".verifyimg").attr("src", verifyimg+'&random='+Math.random());
            }else{
                $(".verifyimg").attr("src", verifyimg.replace(/\?.*$/,'')+'?'+Math.random());
            }
        });
    });

    //初始为不可提交状态
    $(document).ready( function() {
        $('#submit').attr('disabled','disabled');
    })

});

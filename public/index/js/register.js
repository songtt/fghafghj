$(function(){
    //表单为空验证
    $('form').submit(function(){
        var flag = true;
        var password = $('.password').val();
        var password1 = $('.password1').val();
        $('.verify').each(function(){
            if($(this).val() == ""){
                $(this).next().css('display','inline-block');
                $("html,body").animate({scrollTop:0}, 0);
                flag = false;
            }else if(password != password1){
                $('.password,.password1').focus();
                $('.password1').next('span').text('两次密码不一致').css('display','inline-block');
            }
        })
        if(!flag){
            return false;
        }
    })
    $('.verify').keyup(function(){  
        var value = $(this).val();
        if(value!=""){
            $(this).next().css('display','none');
            return true;  
        }else{  
            $(this).next().css('display','inline-block');;
            return false;  
        }  
    })
})
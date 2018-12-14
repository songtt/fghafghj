$(function(){	
	var judgeint = new Array;
	var geint = new Array;
	var queryParam = new Array;
	var s1;
	
	//未提交时隐藏表头
	$(".row").hide();

	//添加
	$(".sc-add").click(function(){
		if($(this).next().is(":hidden")){
			$(this).next().show().parent().siblings().children(".screen-list").hide();
			$(this).children().css("color","#12bdd1")
		}else{
			$(this).next().hide();
			$(this).children().css("color","#666");
		}
	})

	$(".find-list ul").on("click","li", function() {
    	var item = $(this).attr('id');
    	queryParam.push(item);
    	if(queryParam.length>0)
    	{
    		$('.generate-report').removeAttr("disabled");
    		$('.generate-report').css({'background-color':'#12bdce'});
			$('.generate-report').css({'border':'#12bdce'}); 
    	}	
 	});

	var add = $(".sc-add").next().children().children("li");
	add.click(function(){
		var m,n;
		var a = $(this).text();
		var info = '';
		info='<div class="ac-screen ac-a">';
        info+='<div class="controls screen-controls screen-color"><span></span><i class="fa fa-caret-down"></i></div>';
        info+='<div class="screen-list">';
        info+='<h5>维度选择</h5>';
        info+='<ul>';
        //info+='<li id="hour" class="time">小时</li>';
        //info+='<li id="day" class="time">天</li>';
        //info+='<li id="week" class="time">周</li>';
        //info+='<li id="month" class="time">月</li>';
        info+='<li id="today" class="time">今天</li>';
        info+='<li id="yesterday" class="time">昨天</li>';
        info+='<li id="last_month" class="time">上个月</li>';
        info+='<li id="last_seven_days" class="time">最近七天</li>';
        info+='<li id="last_thirty_day" class="time">最近30天</li>';
        info+='<li id="all" class="time">所有时间段</li>';
        //info+='<li id="pid" class="id">计划ID</li>';
        //info+='<li id="uid" class="id">站长ID</li>';
        //info+='<li id="ad_id" class="id">广告ID</li>';
        //info+='<li id="adz_id" class="id">广告位ID</li>';
        //info+='<li id="adv_id" class="id">广告商ID</li>';
        //info+='<li id="site_id" class="id">网站ID</li>';
        //info+='<li id="class_name" class="id">网站分类名称</li>';
        info+='<li id="views" class="sort">浏览数</li>';
        info+='<li id="adv_num" class="sort">结算数</li>';
        info+='<li id="click_num" class="sort">点击率</li>';
        info+='<li id="adv_deduction" class="sort">扣量数</li>';
        info+='<li id="sumadvpay" class="sort">应付</li>';
        info+='<li id="sumprofit" class="sort">盈利</li>';
        info+='<li id="cpc" class="type">Cpc类型</li>';
        info+='<li id="cpm" class="type">Cpm类型</li>';
        info+='<li id="cpv" class="type">Cpv类型</li>';
        info+='<li id="cps" class="type">Cps类型</li>';
        info+='<li id="cpa" class="type">Cpa类型</li>';
        info+='<li id="plan_list" class="stats">计划报表</li>';
        info+='<li id="user_list" class="stats">站长报表</li>';
        info+='<li id="ads_list" class="stats">广告报表</li>';
        info+='<li id="zone_list" class="stats">广告位报表</li>';
        info+='<li id="adv_list" class="stats">广告商报表</li>';
        info+='<li id="site_list" class="stats">网站报表</li>';
        info+='<li id="classes_list" class="stats">网站分类报表</li>';
        info+='</ul>';
        info+='</div>'; 
    	info+='</div>';
    	info+='<i class="fa fa-times fa-i closeD" style="display:block;"></i>';
    	$(".ad-list").before(info);
	})
	//删除
	var _thisClass = '';
	$('.ac-s').on('click','.closeD',function(){
		var _thisClass = $(this).prev().children().children().find('li').attr('class');
		queryParam.pop();
		$(this).prev().remove();
		$(this).remove();
		if(queryParam.length==0)
		{
			$('.generate-report').attr('disabled','disabled');
			$('.generate-report').css({'background-color':'#e8e8e8'});
			$('.generate-report').css({'border':'#e8e8e8'});
		}
		return thisClass(_thisClass);
	})
	//除当前显示的ul，其他隐藏
	$('.ac-s').on('click','.ac-a',function(){
		if($(this).children(":last").is(":hidden")){
			$(this).children(":last").show().parent().siblings().children(".screen-list").hide();
			$(this).children().children("i").attr("class","fa fa-caret-up").parent().parent().siblings(".ac-a").children().children("i").attr("class","fa fa-caret-down");
		}else{
			$(this).children(":last").hide();
			$(this).children().children("i").attr("class","fa fa-caret-down");
		}
	})
	//隐藏添加维度的ul
	var tm = '';
	$(".find-list li").on('click',function(){
		tm = $(this).prop("outerHTML");
		$(this).parent().parent().hide();
		//当前点击的li text值
		$(this).parent().parent().parent().siblings(".ac-a:last").children().children("span").append(tm).children().css("display","inline");		
		var addThisClass = $(this).parent().parent().parent().siblings(".ac-a:last").children().children("span").children().attr('class');
		for(var i = 0;i < geint.length;i++){
			if(geint[i] == addThisClass){
				geint.splice(i,1);
			}
		}
		return judge(addThisClass);
	})
	function judge(_addint){
		judgeint.push(_addint);
		for(var i = 0;i < judgeint.length;i++){
			$('.find-list').find('.'+judgeint[i]+'').css('display','none');
		}
	} 
	function thisClass(_class){
		for(var i = 0;i < judgeint.length;i++){
			if(judgeint[i] == _class){
				geint.push(_class);
				judgeint.splice(i,1);
				for(var j = 0;j < geint.length;j++){
					$('.find-list').find('.'+geint[j]+'').css('display','block');
				}
			}
		}
	}

	if(judgeint.length==0){
		$('.generate-report').attr('disabled','disabled');
		$('.generate-report').css({'background-color':'#e8e8e8'});
		$('.generate-report').css({'border':'#e8e8e8'});
	}else{
		$('.generate-report').removeAttr("disabled");
		$('.generate-report').css({'background-color':'#12bdce'});
		$('.generate-report').css({'border':'#12bdce'});
	}
	
	$('.generate-report').click(function(){
		var numid = $("#numid").val();
		var time = $('#reservation').val();
		var returnString = '';
		var sendString,dateParam;
		
		for(var i=0;i<judgeint.length;i++)
		{
			returnString+='\"'+judgeint[i]+'\"'+':'+'\"'+queryParam[i]+'\"'+',';	
		}

		if(numid.length = 0 || numid=="")
		{
			sendString=returnString.substring(0,returnString.length-1);	
		}
		else
		{
			sendString='\"'+'numid'+'\":'+'\"'+numid+'\"'+','+returnString.substring(0,returnString.length-1);
		}

		if((time.length = 0 || time=="") && ($.inArray('time',judgeint)==-1))
		{
			dateParam = setDate('none');
			sendString+=','+dateParam[0]+','+dateParam[1];
		}
		
		if((time.length = 0 || time=="") && ($.inArray('time',judgeint)==0))
		{
			for(var i=0;i<queryParam.length;i++)
			{
				switch(queryParam[i]){
					case 'today':
						dateParam = setDate('today');
						sendString+=','+dateParam[0]+','+dateParam[1];
						break;
					case 'yesterday':
						dateParam = setDate('yesterday');
						sendString+=','+dateParam[0]+','+dateParam[1];
						break;
					case 'last_month':
						dateParam = setDate('last_month');
						sendString+=','+dateParam[0]+','+dateParam[1];
						break;
					case 'last_seven_days':
						dateParam = setDate('last_seven_days');
						sendString+=','+dateParam[0]+','+dateParam[1];
						break;
					case 'last_thirty_day':
						dateParam = setDate('last_thirty_day');
						sendString+=','+dateParam[0]+','+dateParam[1];
						break;
					default:
						sendString = sendString;
				}
			}
		}
	
		var returnString = $.parseJSON('{'+sendString+'}');
		if($.inArray('stats',judgeint)!=-1){	
			$.ajax({
			 	url: "/admin/diyreport/diyQuery",
			 	data: returnString,
			 	dataType: 'json',
			 	method: 'GET',
				success: function(response){
					if(response.data)
					{
						switch(response.data.stats)
						{
							case 'plan_list':
								planList(response);
								break;
							case 'user_list':
								(response.title=='媒介主管') ? mediumUserList(response) : userList(response);
								break;
							case 'ads_list':
								adsList(response);
								break;
							case 'zone_list':
								(response.title=='媒介主管') ? mediumZoneList(response) : zoneList(response);
								break;
							case 'adv_list':
								advList(response);
								break;
							case 'site_list':
								(response.title=='媒介主管') ? mediumSiteList(response) : siteList(response);
								break;
							default:
								(response.title=='媒介主管') ? mediumClassList(response) : classList(response);
						}
			 		}
				},
				error: function(error) {
					layer.msg('请求失败,请重试', {icon: 1});
				}
			});
		}else{
			layer.msg('请添加报表分类维度!', {icon: 1});
		}	
	});

	function getDate(date1,date2)
	{
		var returnDate = new Array;
		var begin = '\"'+'begin'+'\"'+':'+'\"'+date1+'\"';
		var end = '\"'+'end'+'\"'+':'+'\"'+date2+'\"';
		returnDate.push(begin);
		returnDate.push(end);
		return returnDate;
	}

	function setDate(param)
	{
		//今天的时间
		if(param=='today'){
			s1 = getToday();
			return getDate(s1,s1);
		}

		//昨天的时间
		if(param=='yesterday'){
			var day2 = new Date();
			day2.setTime(day2.getTime()-24*3600*1000);
			var s2 = day2.getFullYear()+"-" + (day2.getMonth()+1) + "-" + day2.getDate();
			return getDate(s2,s2);	
		}

		//上个月的时间
		if(param=='last_month'){
			var day3 = new Date();  
			var year = day3.getFullYear();  
			var month = day3.getMonth();  
			if(month==0)  
			{  
			    month=12;  
			    year=year-1;  
			}  
			if (month < 10) {  
			    month = "0" + month;  
			}  
			var firstDay = year + "-" + month + "-" + "01";   
			var myDate = new Date(year, month, 0);  
			var lastDay = year + "-" + month + "-" + myDate.getDate();
			return getDate(firstDay,lastDay);	
		}

		//默认或最近七天的时间
		if(param=='none' || param=='last_seven_days'){
			var day4 = new Date();
			day4.setTime(day4.getTime()-7*24*3600*1000);
			var s4 = day4.getFullYear()+"-" + (day4.getMonth()+1) + "-" + day4.getDate();
			s1 = getToday();
			return getDate(s4,s1);
		}

		//最近三十天的时间
		if(param=='last_thirty_day'){
			var day5 = new Date();
			day5.setTime(day5.getTime()-30*24*3600*1000);
			var s5 = day5.getFullYear()+"-" + (day5.getMonth()+1) + "-" + day5.getDate();
			s1 = getToday();
			return getDate(s5,s1);
		}
	}

	//今天的时间
	function getToday()
	{
		var day1 = new Date();
		day1.setTime(day1.getTime());
		var t1 = day1.getFullYear()+"-" + (day1.getMonth()+1) + "-" + day1.getDate();
		return t1;
	}

	//计划报表
	function planList(response)
	{
		$("#display-data").empty();
		$('#display-data').append(
	            "<tr>"+
	                "<td>日期</td>"+
	                "<td>计划名称</td>"+
	                "<td>类型</td>"+
	                "<td>浏览数</td>"+
	                "<td>点击数</td>"+
	                "<td>下载数</td>"+						     
	                "<td>扣量数</td>"+
	                "<td>结算数</td>"+
	                "<td>CRT</td>"+
	                "<td>CPA</td>"+
	                "<td>点击率</td>"+
	                "<td>排重点击</td>"+
	                "<td>应付</td>"+
	                "<td>跑量盈利</td>"+
	            "</tr>");		
		var html = "" 
		$.each(response.res, function(i,data) {  		          		         
            html += "<tr>";
            html +=     "<td>" + data.day + "</td>";
            html +=     "<td>" + data.plan_name + "</td>";
            html +=     "<td>" + data.plan_type + "</td>";
            html +=     "<td>" + data.views + "</td>";
            html +=     "<td>" + data.click_num + "</td>";
            html +=     "<td>" + data.download + "</td>";
            html +=     "<td>" + data.adv_deduction + "</td>";
            html +=     "<td>" + data.adv_num + "</td>";
            html +=     "<td>" + data.crt + "</td>";
            html +=     "<td>" + data.cpa + "</td>";
            html +=     "<td>" + data.ctr + "</td>";
            html +=     "<td>" + data.heavy_click + "</td>";
            html +=     "<td>" + data.sumadvpay + "</td>";
            html +=     "<td>" + data.sumprofit + "</td>";
            html += "</tr>";  
        });
        $("#display-data").append(html);
        $(".row").show();
	}

	//站长报表(管理员)
	function userList(response)
	{
		$("#display-data").empty();
		$('#display-data').append(
	            "<tr>"+
	                "<td>日期</td>"+
		            "<td>站长id</td>"+
		            "<td>站长名称</td>"+
		            "<td>浏览数</td>"+
		            "<td>点击数</td>"+
		            "<td>独立访客</td>"+
		            "<td>独立IP</td>"+
		            "<td>扣量数</td>"+
		            "<td>结算数</td>"+
		            "<td>CRT</td>"+
		            "<td>点击率</td>"+
		            "<td>排重点击</td>"+
		            "<td>跑量佣金</td>"+
		            "<td>包天价钱</td>"+
		            "<td>跑量盈利</td>"+
	            "</tr>");		
		var html = "" 
		$.each(response.res, function(i,data) {  		          		         
            html += "<tr>";
            html +=     "<td>" + data.day + "</td>";
            html +=     "<td>" + data.uid + "</td>";
            html +=     "<td>" + data.username + "</td>";
            html +=     "<td>" + data.views + "</td>";
            html +=     "<td>" + data.uv_web + "</td>";
            html +=     "<td>" + data.ui_web + "</td>";
            html +=     "<td>" + data.web_deduction + "</td>";
            html +=     "<td>" + data.web_num + "</td>";
            html +=     "<td>" + data.crt + "</td>";
            html +=     "<td>" + data.ctr + "</td>";
            html +=     "<td>" + data.heavy_click + "</td>";
            html +=     "<td>" + data.sumpay + "</td>";
            html +=     "<td>" + data.cpd + "</td>";
            html +=     "<td>" + data.sumprofit + "</td>";
            html += "</tr>";  
        });
        $("#display-data").append(html);
        $(".row").show();
	}

	//站长报表(媒介主管)
	function mediumUserList(response)
	{
		$("#display-data").empty();
		$('#display-data').append(
	            "<tr>"+
	                "<td>日期</td>"+
		            "<td>站长id</td>"+
		            "<td>站长名称</td>"+
		            "<td>浏览数</td>"+
		            "<td>点击数</td>"+
		            "<td>独立访客</td>"+
		            "<td>独立IP</td>"+
		            "<td>扣量数</td>"+
		            "<td>结算数</td>"+
		            "<td>CRT</td>"+
		            "<td>点击率</td>"+
		            "<td>跑量佣金</td>"+
		            "<td>包天价钱</td>"+
	            "</tr>");		
		var html = "" 
		$.each(response.res, function(i,data) {  		          		         
            html += "<tr>";
            html +=     "<td>" + data.day + "</td>";
            html +=     "<td>" + data.uid + "</td>";
            html +=     "<td>" + data.username + "</td>";
            html +=     "<td>" + data.views + "</td>";
            html +=     "<td>" + data.click_num + "</td>";
            html +=     "<td>" + data.uv_web + "</td>";
            html +=     "<td>" + data.ui_web + "</td>";
            html +=     "<td>" + data.web_deduction + "</td>";
            html +=     "<td>" + data.web_num + "</td>";
            html +=     "<td>" + data.crt + "</td>";
            html +=     "<td>" + data.ctr + "</td>";
            html +=     "<td>" + data.sumpay + "</td>";
            html +=     "<td>" + data.cpd + "</td>";
            html += "</tr>";  
        });
        $("#display-data").append(html);
        $(".row").show();
	}

	//广告报表
	function adsList(response)
	{
		$("#display-data").empty();
		$('#display-data').append(
	            "<tr>"+
	                "<td>日期</td>"+
			        "<td>广告名称</td>"+
			        "<td>浏览数</td>"+
			        "<td>点击数</td>"+
			        "<td>下载数</td>"+
			        "<td>扣量数</td>"+
			        "<td>结算数</td>"+
			        "<td>CRT</td>"+
			        "<td>CPA</td>"+
			        "<td>点击率</td>"+
			        "<td>应付</td>"+
			        "<td>跑量盈利</td>"+
	            "</tr>");		
		var html = "" 
		$.each(response.res, function(i,data) {  		          		         
            html += "<tr>";
            html +=     "<td>" + data.day + "</td>";
            html +=     "<td>" + data.adname + "</td>";
            html +=     "<td>" + data.views + "</td>";
            html +=     "<td>" + data.click_num + "</td>";
            html +=     "<td>" + data.download + "</td>";
            html +=     "<td>" + data.adv_deduction + "</td>";
            html +=     "<td>" + data.adv_num + "</td>";
            html +=     "<td>" + data.crt + "</td>";
            html +=     "<td>" + data.cpa + "</td>";
            html +=     "<td>" + data.ctr + "</td>";
            html +=     "<td>" + data.sumadvpay + "</td>";
            html +=     "<td>" + data.sumprofit + "</td>";
            html += "</tr>";  
        });
        $("#display-data").append(html);
        $(".row").show();
	}

	//广告位报表(管理员)
	function zoneList(response)
	{
		$("#display-data").empty();
		$('#display-data').append(
	            "<tr>"+
	                "<td>日期</td>"+
		            "<td>广告位ID</td>"+
		            "<td>尺寸</td>"+
		            "<td>浏览数</td>"+
		            "<td>点击数</td>"+
		            "<td>扣量数</td>"+
		            "<td>结算数</td>"+
		            "<td>CRT</td>"+
		            "<td>点击率</td>"+
		            "<td>排重点击</td>"+
		            "<td>实际跑量佣金</td>"+
		            "<td>包天价钱</td>"+
		            "<td>跑量盈利</td>"+
	            "</tr>");		
		var html = "" 
		$.each(response.res, function(i,data) {  		          		         
            html += "<tr>";
            html +=     "<td>" + data.day + "</td>";
            html +=     "<td>" + data.adz_id + "</td>";
            html +=     "<td>" + data.size + "</td>";
            html +=     "<td>" + data.views + "</td>";
            html +=     "<td>" + data.click_num + "</td>";
            html +=     "<td>" + data.web_deduction + "</td>";
            html +=     "<td>" + data.web_num + "</td>";
            html +=     "<td>" + data.crt + "</td>";
            html +=     "<td>" + data.ctr + "</td>";
            html +=     "<td>" + data.heavy_click + "</td>";
            html +=     "<td>" + data.sumpay + "</td>";
            html +=     "<td>" + data.cpd + "</td>";
            html +=     "<td>" + data.sumprofit + "</td>";
            html += "</tr>";  
        });
        $("#display-data").append(html);
        $(".row").show();
	}

	//广告位报表(媒介主管)
	function mediumZoneList(response)
	{
		$("#display-data").empty();
		$('#display-data').append(
	            "<tr>"+
	                "<td>日期</td>"+
		            "<td>广告位ID</td>"+
		            "<td>尺寸</td>"+
		            "<td>浏览数</td>"+
		            "<td>点击数</td>"+
		            "<td>扣量数</td>"+
		            "<td>结算数</td>"+
		            "<td>CRT</td>"+
		            "<td>点击率</td>"+
		            "<td>实际跑量佣金</td>"+
		            "<td>包天价钱</td>"+
	            "</tr>");		
		var html = "" 
		$.each(response.res, function(i,data) {  		          		         
            html += "<tr>";
            html +=     "<td>" + data.day + "</td>";
            html +=     "<td>" + data.adz_id + "</td>";
            html +=     "<td>" + data.size + "</td>";
            html +=     "<td>" + data.views + "</td>";
            html +=     "<td>" + data.click_num + "</td>";
            html +=     "<td>" + data.web_deduction + "</td>";
            html +=     "<td>" + data.web_num + "</td>";
            html +=     "<td>" + data.crt + "</td>";
            html +=     "<td>" + data.ctr + "</td>";
            html +=     "<td>" + data.sumpay + "</td>";
            html +=     "<td>" + data.cpd + "</td>";
            html += "</tr>";  
        });
        $("#display-data").append(html);
        $(".row").show();
	}

	//广告商报表
	function advList(response)
	{
		$("#display-data").empty();
		$('#display-data').append(
	            "<tr>"+
	                "<td>日期</td>"+
		            "<td>广告商名称</td>"+
		            "<td>浏览数</td>"+
		            "<td>点击数</td>"+
		            "<td>下载数</td>"+
		            "<td>扣量数</td>"+
		            "<td>结算数</td>"+
		            "<td>CRT</td>"+
		            "<td>CPA</td>"+
		            "<td>点击率</td>"+
		            "<td>排重点击</td>"+
		            "<td>应付</td>"+
		            "<td>跑量盈利</td>"+
	            "</tr>");		
		var html = "" 
		$.each(response.res, function(i,data) {  		          		         
            html += "<tr>";
            html +=     "<td>" + data.day + "</td>";
            html +=     "<td>" + data.username + "</td>";
            html +=     "<td>" + data.views + "</td>";
            html +=     "<td>" + data.click_num + "</td>";
            html +=     "<td>" + data.download + "</td>";
            html +=     "<td>" + data.adv_deduction + "</td>";
            html +=     "<td>" + data.adv_num + "</td>";
            html +=     "<td>" + data.crt + "</td>";
            html +=     "<td>" + data.cpa + "</td>";
            html +=     "<td>" + data.ctr + "</td>";
            html +=     "<td>" + data.heavy_click + "</td>";
            html +=     "<td>" + data.sumadvpay + "</td>";
            html +=     "<td>" + data.sumprofit + "</td>";
            html += "</tr>";  
        });
        $("#display-data").append(html);
        $(".row").show();		
	}

	//网站报表(管理员)
	function siteList(response)
	{
		$("#display-data").empty();
		$('#display-data').append(
	            "<tr>"+
	                "<td>日期</td>"+
		            "<td>网站名称</td>"+
		            "<td>浏览数</td>"+
		            "<td>点击数</td>"+
		            "<td>扣量数</td>"+
		            "<td>结算数</td>"+
		            "<td>CRT</td>"+
		            "<td>点击率</td>"+
		            "<td>排重点击</td>"+
		            "<td>跑量佣金</td>"+
		            "<td>跑量盈利</td>"+
	            "</tr>");		
		var html = "" 
		$.each(response.res, function(i,data) {  		          		         
            html += "<tr>";
            html +=     "<td>" + data.day + "</td>";
            html +=     "<td>" + data.sitename + "</td>";
            html +=     "<td>" + data.views + "</td>";
            html +=     "<td>" + data.click_num + "</td>";
            html +=     "<td>" + data.web_deduction + "</td>";
            html +=     "<td>" + data.web_num + "</td>";
            html +=     "<td>" + data.crt + "</td>";
            html +=     "<td>" + data.ctr + "</td>";
            html +=     "<td>" + data.heavy_click + "</td>";
            html +=     "<td>" + data.sumpay + "</td>";
            html +=     "<td>" + data.sumprofit + "</td>";
            html += "</tr>";  
        });
        $("#display-data").append(html);
        $(".row").show();
	}

	//网站报表(媒介主管)
	function mediumSiteList(response)
	{
		$("#display-data").empty();
		$('#display-data').append(
	            "<tr>"+
	                "<td>日期</td>"+
		            "<td>网站名称</td>"+
		            "<td>浏览数</td>"+
		            "<td>点击数</td>"+
		            "<td>扣量数</td>"+
		            "<td>结算数</td>"+
		            "<td>CRT</td>"+
		            "<td>点击率</td>"+
		            "<td>跑量佣金</td>"+
	            "</tr>");		
		var html = "" 
		$.each(response.res, function(i,data) {  		          		         
            html += "<tr>";
            html +=     "<td>" + data.day + "</td>";
            html +=     "<td>" + data.sitename + "</td>";
            html +=     "<td>" + data.views + "</td>";
            html +=     "<td>" + data.click_num + "</td>";
            html +=     "<td>" + data.web_deduction + "</td>";
            html +=     "<td>" + data.web_num + "</td>";
            html +=     "<td>" + data.crt + "</td>";
            html +=     "<td>" + data.ctr + "</td>";
            html +=     "<td>" + data.sumpay + "</td>";
            html += "</tr>";  
        });
        $("#display-data").append(html);
        $(".row").show();
	}

	//网站类型报表(管理员)
	function classList(response)
	{
		$("#display-data").empty();
		$('#display-data').append(
	            "<tr>"+
	                "<td>日期</td>"+
		            "<td>网站分类名称</td>"+
		            "<td>浏览数</td>"+
		            "<td>点击数</td>"+
		            "<td>扣量数</td>"+
		            "<td>结算数</td>"+
		            "<td>CRT</td>"+
		            "<td>点击率</td>"+
		            "<td>排重点击</td>"+
		            "<td>跑量佣金</td>"+
		            "<td>跑量盈利</td>"+
	            "</tr>");		
		var html = "" 
		$.each(response.res, function(i,data) {  		          		         
            html += "<tr>";
            html +=     "<td>" + data.day + "</td>";
            html +=     "<td>" + data.class_name + "</td>";
            html +=     "<td>" + data.views + "</td>";
            html +=     "<td>" + data.click_num + "</td>";
            html +=     "<td>" + data.web_deduction + "</td>";
            html +=     "<td>" + data.web_num + "</td>";
            html +=     "<td>" + data.crt + "</td>";
            html +=     "<td>" + data.ctr + "</td>";
            html +=     "<td>" + data.heavy_click + "</td>";
            html +=     "<td>" + data.sumpay + "</td>";
            html +=     "<td>" + data.sumprofit + "</td>";
            html += "</tr>";  
        });
        $("#display-data").append(html);
        $(".row").show();		
	}

	//网站类型报表(媒介主管)
	function mediumClassList(response)
	{
		$("#display-data").empty();
		$('#display-data').append(
	            "<tr>"+
	                "<td>日期</td>"+
		            "<td>网站分类名称</td>"+
		            "<td>浏览数</td>"+
		            "<td>点击数</td>"+
		            "<td>扣量数</td>"+
		            "<td>结算数</td>"+
		            "<td>CRT</td>"+
		            "<td>点击率</td>"+
		            "<td>跑量佣金</td>"+
	            "</tr>");		
		var html = "" 
		$.each(response.res, function(i,data) {  		          		         
            html += "<tr>";
            html +=     "<td>" + data.day + "</td>";
            html +=     "<td>" + data.class_name + "</td>";
            html +=     "<td>" + data.views + "</td>";
            html +=     "<td>" + data.click_num + "</td>";
            html +=     "<td>" + data.web_deduction + "</td>";
            html +=     "<td>" + data.web_num + "</td>";
            html +=     "<td>" + data.crt + "</td>";
            html +=     "<td>" + data.ctr + "</td>";
            html +=     "<td>" + data.sumpay + "</td>";
            html += "</tr>";  
        });
        $("#display-data").append(html);
        $(".row").show();
	}
})
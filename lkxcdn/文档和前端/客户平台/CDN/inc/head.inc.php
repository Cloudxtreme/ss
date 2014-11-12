<script language="javascript" src="./js/popup_layer.js"></script>
<script language="javascript" src="../js/popup_layer.js"></script>
<script type="text/javascript" src="./js/common.js"></script>
<script type="text/javascript" src="../js/common.js"></script>
<link rel="stylesheet" href="./css/ques.css" />
<link rel="stylesheet" href="../css/ques.css" />
<div class="header">
    <div>
    	<div class="h_left"></div>
    	<div class="h_right"><a href="/cdn/inc/exit.inc.php">退出</a></div>
    </div>
</div>
    
<div class="nav">
	<div>
    	<ul>      
        	<li class="r_border"><a href="/cdn/help/help.html">帮 助</a></li>
          <!--  <li class="r_border"><a href="#" id="ele">信息反馈</a></li>  -->
            <li><a href="/cdn/base/msgconf.php">账号维护</a></li>
            <li><a href="/cdn/index.php">首 页</a></li>
            <li class="phonenum">全国客服： 400-066-2212</li>
        </ul>
        <div id="_session"><b><?php require('session.inc.php'); ?></b> 欢迎您!</div> 
    </div>
</div>


<script type="text/javascript">

/* $(document).ready(function() {
	var t = new PopupLayer({
		trigger:"#ele",
		popupBlk:"#blk",
		closeBtn:"#close",
		useOverlay:true,
		useFx:true,
		offsets:{
			x:0,
			y:-41
		}
	});
	
	t.doEffects = function(way){
		if(way == "open"){
			this.popupLayer.css({opacity:0.3}).show(300,function(){
				this.popupLayer.animate({
					left:(document.documentElement.clientWidth - this.popupLayer.width())/2,
					top:(document.documentElement.clientHeight - this.popupLayer.height())/2 + $(document).scrollTop(),
					opacity:0.8
				},300,function(){this.popupLayer.css("opacity",1)}.binding(this));
			}.binding(this));
			this.popupBlk.show();
		}
		else{
			this.popupLayer.animate({
				left:this.trigger.offset().left,
				top:this.trigger.offset().top,
				opacity:0.1
			},{duration:300,complete:function(){this.popupLayer.css("opacity",1);this.popupLayer.hide()}.binding(this)});
		}
	}

	$('#quesubmit').click(function() {
		$('#blk').hide();
		$('#quesbox').show();
	})
	
	$('#can').click(function() {
		$('#quesbox').hide();
		t.close();
	})
	
	$('#formsubmit').click(function(){
	var  web=0;
	if($("#web").attr("checked")==true||$("#web").attr("checked")=="checked")
	{
	   web=1;
	}
	 var file=0;
	if($("#file").attr("checked")==true||$("#file").attr("checked")=="checked")
	{
		file=1;
	}
	var domain=$("#xdomain").val();
	var linetype=$('input[name=linetype]:checked').val();
	var num1=$('input[name=num1]:checked').val();
	var num2=$('input[name=num2]:checked').val();
	var num3=$('input[name=num3]:checked').val();
	var num4=$('input[name=num4]:checked').val();
	var num5=$('input[name=num5]:checked').val();
	var num6=$('input[name=num6]:checked').val();
        var num1suggest=$('#num1suggest').val();
	var num2suggest=$('#num2suggest').val();
	var num3suggest=$('#num3suggest').val();
	var num5suggest=$('#num5suggest').val();
	var num6suggest=$('#num6suggest').val();
	var user = get_login_user();
	var username=get_login_username();
	$.post("./function/base.survey.fun.php",
	 {"user":user,"get_type":"_formsubmit","web":web,"file":file,"domain":domain,"linetype":linetype,"num1":num1,
	 "num2":num2,"num3":num3,"num4":num4,"num5":num5,"num6":num6,"num1suggest":num1suggest,"num2suggest":num2suggest,
	 "num3suggest":num3suggest,"num5suggest":num5suggest,"num6suggest":num6suggest,"username":username},
	 function(data){
         if(data.indexOf("1")>=0)
	  {
	   	 alert("评价成功！"); 
	  }
	  
	  });
	  $.post("../function/base.survey.fun.php",
	 {"user":user,"get_type":"_formsubmit","web":web,"file":file,"domain":domain,"linetype":linetype,"num1":num1,
	 "num2":num2,"num3":num3,"num4":num4,"num5":num5,"num6":num6,"num1suggest":num1suggest,"num2suggest":num2suggest,
	 "num3suggest":num3suggest,"num5suggest":num5suggest,"num6suggest":num6suggest,"username":username},
	 function(data){
         if(data.indexOf("1")>=0)
	  {
	   	alert("评价成功！");  
	  }
	  });
	 $('#quesbox').hide();
	 t.close();
	
	
	});
	
	$('#nosubmit').click(function(){
		t.close();
	});
	 var user = get_login_user();
	 var username=get_login_username();
	 $("#litip").html(username+":");
	$.post("./function/base.survey.fun.php",{"get_type":"_selnum","user":user},function(data){
	 
      if(data.indexOf("1")>=0)
	  {
		  $("#ele").click();	 
	  }
    });
	
	
});  */

</script>
 <!----------满意度评价信息表--------------->
  <!----
    <div class="quesbox" id="quesbox" style="display:none;">
    	<div class="questitle">
        	<ul>
            	<li class="q_left">CDN满意度调查表</li>
                <li class="q_mid"></li>
                <li class="q_right"></li>
            </ul>
        </div>
        <div class="quescontent">
        	<div class="contentbox">
        		<h3>1、基本信息</h3>
                <table>
                	<tr class="lightgreen">
                    	<td class="width194">加速的类型</td>
                        <td>
                        <input type="checkbox"  id="web"/> <label>网页加速</label>
                        <input type="checkbox" id="file"/> <label>下载加速</label>
                        </td>
                    </tr>
                    <tr>
                    	<td>加速的域名</td>
                        <td><input type="text"  id="xdomain" class="t_u_line"/></td>
                    </tr>
                    <tr class="lightgreen">
                    	<td>源服务器的网络类型</td>
                        <td>
                        <input type="radio"  name="linetype" value="1"/> <label>单电信</label> 
                        <input type="radio"  name="linetype" value="2"/> <label>单联通</label>
                        <input type="radio"  name="linetype" value="3"/> <label>双线</label> 
                        <input type="radio"  name="linetype" value="4"/> <label>BGP</label> 
                        <input type="radio"  name="linetype" value="5"/> <label>其他</label>
                        </td>
                    </tr>
                </table>
                
              <h3>2、管理平台</h3>
                
                <table>
                	<tr class="lightgreen">
                    	<td  class="width194">导航栏的菜单是否清晰明了</td>
                      <td><input type="radio" name="num1" value="1" /> <label>是</label> <input type="radio" name="num1" value="0"/> <label>否</label> 改进建议：<input type="text" class="t_u_line" id="num1suggest"/></td>
                  </tr>
                    <tr>
                    	<td>功能模块划分是否明显</td>
                        <td><input type="radio" name="num2" value="1"/> <label>是</label> <input type="radio" name="num2" value="0"/> <label>否</label> 改进建议：<input type="text" class="t_u_line" id="num2suggest"/></td>
                    </tr>
                    <tr class="lightgreen">
                    	<td>是否有需要增加的板块</td>
                        <td><input type="radio" name="num3" value="1"/> <label>是</label> <input type="radio" name="num3" value="0"/> <label>否</label> 改进建议：<input type="text" class="t_u_line" id="num3suggest"/></td>
                    </tr>
                    <tr>
                    	<td>左边功能键的使用频率</td>
                        <td><input type="radio" name="num4" value="1"/> <label>经常使用</label> <input type="radio" name="num4" value="2"/> <label>有时使用</label> <input type="radio" name="num4" value="3"/> <label>很少使用</label></td>
                    </tr>
                    <tr class="lightgreen">
                    	<td>可操作性，是否容易把握</td>
                        <td><input type="radio" name="num5" value="1"/> <label>是</label> <input type="radio" name="num5" value="0"/> <label>否</label> 改进建议：<input type="text" class="t_u_line" id="num5suggest"/></td>
                    </tr>
                    <tr>
                    	<td>页面布局是否合理</td>
                        <td><input type="radio" name="num6" value="1"/> <label>是</label> <input type="radio" name="num6" value="0"/> <label>否</label> 改进建议：<input type="text" class="t_u_line" id="num6suggest"/></td>
                    </tr>
              </table>
            </div>
            <div class="quesmbit"><input id="formsubmit" type="button" value="" class="subbt" />&nbsp;&nbsp;&nbsp;<input id="can" type="button" value=""  class="subcan"/></div>
        </div>
        <div class="quesbtm"></div>
    </div>
    --->
    
    <!----------是否确认评价--------------->
       <!---
    <div class="queswrap" id="blk">
    	<div class="quesitro">
        	<ul>
            	<li class="txt1" id="litip"></li>
                <li class="txt2">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;尊敬的客户，谢谢您使用睿江CDN加速服务，我们想您在百忙之中抽空对我们的服务进行评价！</li>
                <li class="txt3">
                	<i>——感谢您的一路相伴，</i><br />
                    <i>您的支持是我们最大的前进动力！</i>
				</li>
            </ul>
            <p><input id="quesubmit" type="button" value=" " /></p>
            <p><a id="nosubmit" href="javascript:void(0)">暂不评价</a></p>
        </div>
        <a href="#" class="closed" id="close">close</a>
    </div>
    -->
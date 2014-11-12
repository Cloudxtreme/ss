<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>

<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7"/>
<script type="text/javascript">
String.prototype.endWith=function(s){if(s==null||s==""||this.length==0||s.length>this.length)
return false;if(this.substring(this.length-s.length)==s)
return true;else
return false;return true;}
String.prototype.startWith=function(s){if(s==null||s==""||this.length==0||s.length>this.length)
return false;if(this.substr(0,s.length)==s)
return true;else
return false;return true;}
</script>

<script>var conf={"context":""},jsver=3;</script>

<script type="text/javascript" src="../js/zh_CN.js"></script>
<script type="text/javascript" src="../js/import.js"></script>
<script type="text/javascript" src="../js/jquery-1.7.2.js"></script>
<script type="text/javascript" src="../js/jquery.bgiframe.min.js"  charset="UTF-8"></script>
<script type="text/javascript" src="../js/loading-min.js"  charset="UTF-8"></script>
<link rel="stylesheet" href="../js/loading.css" type="text/css">
<link rel="stylesheet" href="../style/main.css">
<link rel="stylesheet" href="../css/main.css" />
<script src="../js/tree.js" type="text/javascript"></script>
<link rel="stylesheet" href="../amcharts/style.css" type="text/css">
<script src="../amcharts/amstock.js" type="text/javascript"></script>
<script type="text/javascript" src="../js/common.js"></script>
<script src="../js/file.downanalyse.js" type="text/javascript"></script>

<style type="text/css">
.tab2{width:695px;height:22px;margin:0 10px 0px 10px;padding-left:5px}
.tab2 ul{height:23px;overflow:hidden;padding:0px;margin:0px}
.tab2 li{width:86px;height:22px;overflow:hidden;background:url(../images/skier1.png) no-repeat -337px -116px;float:left;cursor:pointer;line-height:22px;color:#007b90;font-weight:normal;text-align:center;margin-right:2px}
.tab2 li.YKTabsOn{background:url(../images/skier1.png) no-repeat -337px -145px;height:23px;font-weight:bold}
.tab2 a{text-decoration:none}
.tab2 li.YKTabsOn a{color:#000}
#_downfilelist td{word-break: break-all; word-wrap:break-word;}
</style>

<script>//<![CDATA[
$import("../style/jquery-ui-1.8.6.custom.css","css","theme");
$import("../style/jquery.multiselect.css","css");
$import("../js/common-all.js");
//]]></script>

<script>//<![CDATA[
$import("../js/My97DatePicker/WdatePicker.js");
//]]></script>

<title>睿江Portal—下载分析</title>
</head>
<body>
<?php require_once('../inc/head.inc.php'); ?> 
<div id="main">
		<div class="main_left"><!--左导航开始--> 
		<?php require_once('../inc/menu.inc.php'); ?>
<script>jQuery(function(){flexMenu('box')})</script> <!--左导航结束--></div>
		<div class="main_right">
			<div class="main_right_top">
				<div class="main_left_top_title">
					下载分析
				</div>
				<div class="main_right_top_title">
					
					
				</div>
				<div class="main_right_top_mbx"></div>
			</div>
			<div id="content" class="main_right_middle">
			
<div class="tab" id="menuDIV1">
	<ul id="menuUL1">
         <li ><a href='performance.php'>性能分析</a></li>
        <li class='YKTabsOn'><a href='javascript:void()'>下载分析</a></li>
       	
        
    </ul>
</div>

				<!-- simple query form -->
				<div class="search">
					<form name="queryF" method="post" action="visitor-analysis.action" onsubmit="return query()">
						<div class="search_left">
							<table>
								<tr>
									<td class="lable">查询时间：</td>
									<td>
										<input name="startDate" id="startDate" type="text" class="inp"  onfocus="WdatePicker({onpicked:function(){$dp.$('endDate').focus();},minDate:'2012-07-02',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,maxDate:'%y-%M-%d}'})">&nbsp;&nbsp;~
											&nbsp;&nbsp;<input name="endDate" id="endDate" type="text" class="inp"  onfocus="WdatePicker({startDate:'%y-%M-01',dateFmt:'yyyy-MM-dd',minDate:'#F{$dp.$D(\'startDate\');}',maxDate:'%y-%M-%d'})">
									</td>
									
								</tr>
								<tr>
									<td class="lable">频　　道：</td>
									<td class="channel">
										<select id="channel" multiple="multiple" size="1" name="selectedChannels"></select>

										<img id="chtip" src="data:image/gif;base64,R0lGODlhDgAOAOZbAMvLZV3R//30p//2sP71qFidy0WFsP/2sdDQbf71r/HojNLScM3Naffukfjvlf/2sq2tR1mjzP71rTV0nby8VYGyxdraernb9tzcfLm5U2eRnP30qFedykWFstbWdpCpezjF//zzo0+Uz0aIxNHR0fvyoP71q/zzpe/v7+7liPnwm1idyvrxm0WFsbOzTf71rovE8aurbqzK5bfY85Lj/9jYeObmwv/2t5fl/7PT7rq6oKjF4ODg4Fif23Ohrlql0srKZj16pv71qeXlnsPDXtvbwMPDXbi4Uv30qnPY/2rV//30q8bGYdTUc0+Rvf30pvzzpPPqkvvyo/vyofbtl/rxoPjvm/jvnfnwnrCwSt3dfv///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAAFsALAAAAAAOAA4AAAeagFuCg4QwF4JDWoqLixE/M1sYNw8HAwkVBSscBTg9ORYPQgQCT040SgGpSSI1B6NPUD4tBgYdICMyHgOkUFJTVVhXQRM7W00JsL7AV1YaH4ILL72/wVZULAuCCBLK1VRRDgiCDEvUzN8KDQyCQEjL1lEA8kSCTBveUQpGKUc2ghQmToQoocJBgwwuigyKkaWhwywQdJDggWJLIAA7"><script>$(document).ready(function(){$("#chtip").tooltip({position:"center right",tip:"#chtip-data"})});</script><br>
									</td>
									<td>&nbsp;</td>
								</tr>
							</table>
						</div>	
						<div class="search_right">
							<input type="button" value="查&nbsp;询" onclick="query()" class="button"/>
						</div>
						<input type="hidden" id="_startDate"/>
						<input type="hidden" id="_endDate"/>
						
					</form>
				</div>
              		
				
					<div class="content" id="htbale">
						<div class="subtitle">
							<h2>文件列表</h2>
						
						</div>
						<div class="table1">
							<table id="_downfilelist" style="table-layout:fixed;">
								<tr>
									
									<th width="50%" style="text-align:left;">文件名</th>
									<th width="20%" style="text-align:center;">完整传输次数</th>
									<th width="25%" style="text-align:center;">不完整传输次数</th>
									
								</tr>
								
							</table>
						</div>
		
					</div>
					
							
				
					
					
					
					
					
				


	 			<!-- 查询结束  -->
	 		</div>

		<div class="main_right_bottom"></div>
	</div>
	<div class="clear"></div>
</div>

<!-- 内容结束 -->

<?php require_once('../inc/foot.inc.php'); ?>
<br/>
<br/>

<!-- 提示信息开始  -->

<div id="tt_province" class="tooltip">
	点击省份显示该省城市
</div>
<div id="chtip-data" class="tooltip" style="height:150px">
	<strong>已选频道：</strong>
</div>
<div id="tt_region" class="tooltip">
	　　每个计费地区有唯一的加速区域名称，由网宿根据业务分布情况而灵活定义，目前有中国大陆、香港、海外，只要您购买了网宿对应区域服务，系统就将为您提供对应区域的业务分析信息。
</div>

<div id="two_channels_only" class="tooltip" style="width: 120px">
	最多只能查询两个频道
</div>

<div id="tt_isp" class="tooltip">
	　　互联网服务供应商，只有加速区域为中国大陆时该选项才有意义。
</div>
<div id="tt_isp2" class="tooltip">
	　　互联网服务供应商，只有加速区域包含中国大陆时该选项才有意义。
</div>
<div id="tt_compare" class="tooltip">
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;选择对比，可以查看两个时间周期的带宽对比图，对比图的说明详见帮助说明文档。 
</div>
<div id="tt_isp_compare" class="tooltip">
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;可以查看所选ISP的带宽对比图。 <br/>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;注:对比的ISP数不能大于3。
</div>
<div id="tt_flow_type" class="tooltip">
	<strong>边缘节点流量：</strong>由网宿边缘节点向最终访客提供服务所产生的流量。 <br/><strong>回源流量：</strong>由于网宿缓存服务器没有缓存或者缓存过期，回源取响应所产生的流量。 <br/><strong>中间缓存流量：</strong>由网宿边缘节点到网宿中间节点产生的流量。 <br/>
</div>
<div id="tt_flow_type2" class="tooltip">
	<strong>边缘节点流量：</strong>由网宿边缘节点向最终访客提供服务所产生的流量。 <br/><strong>回源流量：</strong>由于网宿缓存服务器没有缓存或者缓存过期，回源取响应所产生的流量。 <br/><strong>中间缓存流量：</strong>由网宿边缘节点到网宿中间节点产生的流量。 <br/>
</div>
<div id="dirtip-data" class="tooltip">
	<strong>已选目录：</strong>
	
</div>
<div id="tt_hit_type" class="tooltip">
	<strong>回源请求数：</strong>由于网宿缓存服务器没有缓存或者缓存过期，回源取响应的请求。边缘节点请求数：由网宿边缘节点向最终用户提供服务产生的请求数。<br/>
	<strong>边缘节点请求数：</strong>由网宿边缘节点向最终用户提供服务产生的请求数。
</div>
<div id="tt_hit_type2" class="tooltip">
	<strong>回源请求数：</strong>由于网宿缓存服务器没有缓存或者缓存过期，回源取响应的请求。边缘节点请求数：由网宿边缘节点向最终用户提供服务产生的请求数。<br/>
	<strong>边缘节点请求数：</strong>由网宿边缘节点向最终用户提供服务产生的请求数。
</div>
<div id="tt_url_request_data" class="tooltip">
	URL总请求数。
</div>
<div id="tt_ok_request_data" class="tooltip">
	除状态码为4xx、5xx以外的请求数。
</div>
<div id="tt_ok_rate" class="tooltip">
	下载请求成功次数/下载请求次数。
</div>
<div id="tt_statistics_data" class="tooltip">
	点击地区，查看对应的URL的访问量在各地区的分布；点击来源，查看对应URL的访问量在各来源网站分布。
</div>
<div id="tt_reffer_mes" class="tooltip">
	点击目标URL，查看对应来源网站引向的目标URL及访问量信息。
</div>

<div id=tt_country class="tooltip">
	访问者所属国家或地区
</div>
<div id="tt_url_data" class="tooltip">
1.预取，CDN节点在删除文件的同时回源获取新文件；2.非预取，CDN节点在接到下一个访问请求时，回源更新数据并缓存文件；3.对于大文件，预取方式可能导致回源量突增，因此推荐使用非预取的方式。
</div>
<div id="tt_url_case_data" class="tooltip">
选择"不区分大小写"功能，所有URL都转为小写处理!
</div>
<div id="tt_service_code_data" class="tooltip">
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;服务单的唯一标识，服务单由一个或多个频道组成，同一服务单的频道享有相同加速类型、计费方式等。
</div>

<div id="tt_service_period_data" class="tooltip">
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;查询时间段内某种计费方式的有效计费时间，等于订单的每种计费方式的有效期与查询时间段的重合时间。
</div>
<div id="tt_error_request" class="tooltip">
	<strong>错误请求数：</strong>是指4xx和5xx状态码的请求 
</div>
<div id="tt_ok_request" class="tooltip">
	<strong>OK请求数：</strong>是指2xx和3xx状态码的请求。
</div>
<div id="tt_web" class="tooltip">
	"-" 表示没有来源，也就是直接在浏览器中输入URL。
</div>

<div id="sendCycletip-data" class="tooltip" style="width:100px;">
	<strong>已选周期：</strong>
	
</div>

<div id="emailtip-data" class="tooltip" style="width: 150px;">
	多个邮箱请用英文分号隔开
</div>
<div id="tt_att" class="tooltip">
	1. 选取攻击时间；
	<br/>
	2. 选取受攻击的频道；(只能选择一个，不能不选)
	<br/>
	3. 选取加速区域。(至少选一个区域)
</div>
<div id="tt_save_flow" class="tooltip">
    计算方法：
    <br>
	（边缘节点流量-回源流量）/边缘节点流量*100%
</div>
<div id="tt_requests_flow" class="tooltip">
    计算方法：
    <br>
	（边缘请求数-回源请求数）/边缘请求数*100%
</div>
<div id="limited_domain_tip_data" class="tooltip" style="height:150px">
	<strong>已选限量域名：</strong>
	
</div>
<div id="tt_flow_type_middleFlow" class="tooltip">
	中间缓存流量比例 = 中间缓存流量 / 边缘节点流量
</div>
<div id="tt_flow_type_sourceFlow" class="tooltip">
	回源流量比例 = 回源流量 / 边缘节点流量
</div>
<div id="tt_flow_type_saveFlow" class="tooltip">
	节省源站流量比例 = (边缘节点流量 - 回源流量) / 边缘节点流量
</div>
<div id="tt_hit_type_0" class="tooltip">
	回源请求数比例 = 回源请求数 / 边缘节点请求数
</div>
<div id="tt_hit_type_1" class="tooltip">
	为客户节省源站请求数比例 = (边缘节点请求数- 回源请求数) / 边缘节点请求数
</div>
<!-- 提示信息结束  -->

</body>
</html>
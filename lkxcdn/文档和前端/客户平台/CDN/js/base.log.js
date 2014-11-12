		$(function(){
				 var user = get_login_user();
			   var  url = "http://weblogdw.cdn.efly.cc/cdn_web_log/base.log.fun.php?client="+user+"&callback=?";
			   
	   	   $.getJSON( url, function(jsonData){
					 	 	$("#DownloadUl").empty();
			        $("#DownloadUl").append("<tr><th style=\"width:130px;text-align:center;\" class=\"index\" >日 期</th><th width=\"150px\" style=\"text-align:center;\">文 件</th><th width=\"150px\" style=\"text-align:center;\">大 小(MB)</th><th width=\"150px\" style=\"text-align:center;\">下载</th></tr>");
			       
			        $.each(jsonData, function (i, key) {
			            $("#DownloadUl").append("<tr><td style=\"width:130px;text-align:center;\" class=\"index\"  >" + jsonData[i].date + " </td><td width=\"150px\" style=\"text-align:center;\">  " + jsonData[i].file + "  </td><td width=\"150px\" style=\"text-align:center;\">  " + jsonData[i].size + " </td><td width=\"150px\" style=\"text-align:center;\"><a  href='http://weblogdw.cdn.efly.cc:7654/" + jsonData[i].date + "/" + jsonData[i].file + "'>下载</a> </td></tr>");
			        });
			        _w_table_rowspan(1);
					});

				menu_style_set( 2, 23 );
		})
		
		//合并表格相邻相同元素的单元格
    function _w_table_rowspan(_w_table_colnum) {
        _w_table_firsttd = "";
        _w_table_currenttd = "";
        _w_table_SpanNum = 0;
        _w_table_Obj = $("#DownloadDT" + " tr td:nth-child(" + _w_table_colnum + ")");
        _w_table_Obj.each(function (i) {
            if (i == 0) {
                _w_table_firsttd = $(this);
                _w_table_SpanNum = 1;
            } else {
                _w_table_currenttd = $(this);
                if (_w_table_firsttd.text() == _w_table_currenttd.text()) {
                    _w_table_SpanNum++;
                    _w_table_currenttd.hide(); //remove(); 
                    _w_table_firsttd.attr("rowSpan", _w_table_SpanNum);
                } else {
                    _w_table_firsttd = $(this);
                    _w_table_SpanNum = 1;
                }
            }
        });
    } 

/**
 * 全局上下文路径
 */
var ctxPath = null,jsver=jsver||'';

/**
 * 引入JS或CSS文件
 * @param path js或者css文件路径
 * @param type 类型，如果为css，那么引入css文件；否则引入javascript文件
 * 注意：引用js 和 css只支持widgets目录下的css和js文件，对于非widgets目录下的文件，请使用原始声明方式
 */
function $import(path, type, id){
	var i=0,
	base="";
	/*src = "import.js",
	scripts = document.getElementsByTagName( "script"); 

	for ( ; i < scripts.length; i++) {
		if (scripts[i].src.match(src)) {
			base = scripts[i].src.replace(src, "");
			break;
		}
	}*/
	
	if( path ){		
		//path = path.replace( new RegExp( "\\.","g" ),"/" );
	}else{
		return ;
	}
	
	try {//添加js，css文件的引用
		if (type == "css") {
			if (id !=""&&id){
				id ='id="'+id+'"';
			}
			document.writeln('<li'+'nk '+id+' href="' + base + path + '?v='+jsver + '" rel="stylesheet" type="text/css"></li'+'nk>');
		} else {
			document.writeln('<scr'+'ipt src="' + base + path + '?v='+jsver+'" type="text/javascript"></scr'+'ipt>');
		}
	} catch (e) {//如果异常，则创建相应的元素	  
		if( type == "css"){
			var linkEle = document.createElement("link");
			linkEle.href = base + path +  ".css?v=" + jsver ;
			
			document.getElementsByTagName("head")[0].appendChild( linkEle );
		}else {
			var script = document.createElement("script");
			script.src = base + path +  ".js?v=" + jsver ;
			
			document.getElementsByTagName("head")[0].appendChild( script );
		}
	}
}

/**
* 获取文件的所在路径,如： ca/company/
*/
function getBasePath( jsFileName ){
	if(jsFileName == '') {
		alert( 'import.getBasePath error:js file name cannot be null');
		return ;
	}
	var src = jsFileName ;
	var scripts = document.getElementsByTagName( "script"); 
    var i=0 ;

	for ( ; i < scripts.length; i++) {
		if (scripts[i].src.match(src)) {			 
			return scripts[i].src.replace(src, "");
		}
	}
	return "" ;
}

/**
 * 返回上下文路径
 */
function getContextPath(){
	if(window.ctxPath != undefined && window.ctxPath != null){
		return ctxPath;
	}
	ctxPath = "";
	var i=0,
	src = "/html/js/import.js",
	scripts = document.getElementsByTagName( "script"); 

	for ( ; i < scripts.length; i++) {
		if (scripts[i].src.match(src)) {
			ctxPath = scripts[i].src.replace(src, "");
			break;
		}
	}
	return ctxPath;
}
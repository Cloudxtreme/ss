package com.cdn.mode;


import org.apache.http.HttpEntity;  
import org.apache.http.HttpResponse;  
import org.apache.http.client.HttpClient;  
import org.apache.http.client.entity.UrlEncodedFormEntity;
import org.apache.http.client.methods.HttpGet;
import org.apache.http.client.methods.HttpPost;  
import org.apache.http.impl.client.DefaultHttpClient;  
import org.apache.http.message.BasicNameValuePair;
import org.apache.http.protocol.HTTP;
import org.apache.http.NameValuePair;

import java.io.BufferedReader;
import java.io.ByteArrayInputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.util.ArrayList;
import java.util.List;
public class Htp {
public InputStream ht(String url,String[] arg1,String[] arg2)
{  
	
	InputStream inp=null;
	try
	{
	HttpClient httpclient = new DefaultHttpClient();  
	HttpPost post= new HttpPost(url);
	 List<NameValuePair> params = new ArrayList<NameValuePair>();  
	for(int i=0;i<arg1.length;i++)
	{
		params.add(new BasicNameValuePair(arg1[i], arg2[i])); 
	}
	post.setEntity(new UrlEncodedFormEntity(params, HTTP.UTF_8));
	HttpResponse response = httpclient.execute(post);    
    HttpEntity entity = response.getEntity();
    if (entity != null) {    
    	InputStream in=entity.getContent();     	
    	String str = convertStreamToString(in);
    	byte[] bytes = str.getBytes();
    	inp=new ByteArrayInputStream(bytes);
        post.abort();
    } 
	}
	catch(Exception e)
	{
		e.printStackTrace();
	}
	return inp;
}
public String get(String owner)
{   
	String str="";
	try
	{
	HttpClient httpclient = new DefaultHttpClient();  
	String url="http://cdnmgr.efly.cc/cdn_node_mgr/node_task/restart_task.php?username=cdnadmincdn&password=cdnadmincdn&type=file&owner="+owner;
    HttpGet httpgets = new HttpGet(url);    
    HttpResponse response = httpclient.execute(httpgets);    
    HttpEntity entity = response.getEntity();    
    if (entity != null) {    
        InputStream instreams = entity.getContent();    
        str = convertStreamToString(instreams);
        int a=str.indexOf("<response>");
        str=str.substring(a+10);
        a=str.indexOf("</response>");
        str=str.substring(0,a);
        httpgets.abort();    
    } 
	}catch(Exception e)
	{
		e.printStackTrace();
	}
	return str;
}
public static String convertStreamToString(InputStream is) {      
    BufferedReader reader = new BufferedReader(new InputStreamReader(is));      
    StringBuilder sb = new StringBuilder();      
   
    String line = null;      
    try {      
        while ((line = reader.readLine()) != null) {  
            sb.append(line + "\n");      
        }      
    } catch (IOException e) {      
        e.printStackTrace();      
    } finally {      
        try {      
            is.close();      
        } catch (IOException e) {      
           e.printStackTrace();      
        }      
    }      
    return sb.toString();      
}  
}

package com.cdn.action;

import java.io.InputStream;
import com.cdn.mode.CDNUserMode;
import com.opensymphony.xwork2.ActionSupport;
import com.cdn.mode.Htp;
import com.cdn.ado.CDN_User;
public class CDNywcx extends ActionSupport {
	private String user;
	private Integer type;
	private Integer id;
	private InputStream inputStream;
	private String time;
	private String channel;
	private String zone;
	private String isp;
	private String name;
	
	public String getTime() {
		return time;
	}
	public void setTime(String time) {
		this.time = time;
	}
	public String getChannel() {
		return channel;
	}
	public void setChannel(String channel) {
		this.channel = channel;
	}
	public String getZone() {
		return zone;
	}
	public void setZone(String zone) {
		this.zone = zone;
	}
	public String getIsp() {
		return isp;
	}
	public void setIsp(String isp) {
		this.isp = isp;
	}
	public String getUser() {
		return user;
	}
	public void setUser(String user) {
		this.user = user;
	}	
	public Integer getType() {
		return type;
	}
	public void setType(Integer type) {
		this.type = type;
	}
	public Integer getId() {
		return id;
	}
	public void setId(Integer id) {
		this.id = id;
	}
	
	public InputStream getInputStream() {
		return inputStream;
	}
	public String execute() throws Exception
	{   if(getName().equals("")==false)
	{
		String st=new String(getName().getBytes("ISO-8859-1"),"utf-8");
		setName(st);
	}
		return SUCCESS;
	}
	public String lltj() throws Exception
	{   
		if(getName().equals("")==false)
		{
			String st=new String(getName().getBytes("ISO-8859-1"),"utf-8");
			setName(st);
		}
		return "lltj";
	}
	public String lltjfl() throws Exception
	{   
		if(getName().equals("")==false)
		{
			String st=new String(getName().getBytes("ISO-8859-1"),"utf-8");
			setName(st);
		}
		return "lltjfl";
	}
	
	public String getName() {
		return name;
	}
	public void setName(String name) {
		this.name = name;
	}
	public String ini() throws Exception
	{   
		Htp  h=new Htp();
		String url="";
		if(type.intValue()==0)
		{
			url="http://116.28.64.172:8081/web.bandwidth.fun.php";
		}
		if(type.intValue()==1)
		{
			url="http://116.28.64.172:8081/file.bandwidth.fun.php";
		}
		CDNUserMode mo=new CDNUserMode();
		CDN_User u=new CDN_User();
		u=mo.seled(id);
		String[] a1=new String[3]; 
		String[] a2=new String[3]; 
		a1[0]="user";
		a1[1]="pass";
		a1[2]="get_type";
		a2[0]=user;
		a2[1]=u.getPass();
		a2[2]="_init";
		inputStream=h.ht(url, a1, a2);
		return "ini";
	}
	public String kdcx() throws Exception
	{   
		Htp  h=new Htp();
		String url="";
		if(type.intValue()==0)
		{
			url="http://116.28.64.172:8081/web.bandwidth.fun.php";
		}
		if(type.intValue()==1)
		{
			url="http://116.28.64.172:8081/file.bandwidth.fun.php";
		}
		CDNUserMode mo=new CDNUserMode();
		CDN_User u=new CDN_User();
		u=mo.seled(id);
		String[] a1=new String[7]; 
		String[] a2=new String[7]; 
		a1[0]="user";
		a1[1]="pass";
		a1[2]="get_type";
		a1[3]="time";
		a1[4]="channel";
		a1[5]="zone";
		a1[6]="isp";
		a2[0]=user;
		a2[1]=u.getPass();
		a2[2]="_bandwidth";
		a2[3]=time;
		a2[4]=channel;
		a2[5]=zone;
		a2[6]=isp;
		inputStream=h.ht(url, a1, a2);
		return "ini";
	}
	public String tjini() throws Exception
	{   
		Htp  h=new Htp();
		String url="";
		if(type.intValue()==0)
		{
			url="http://116.28.64.172:8081/web.flow.fun.php";
		}
		if(type.intValue()==1)
		{
			url="http://116.28.64.172:8081/file.flow.fun.php";
		}
		CDNUserMode mo=new CDNUserMode();
		CDN_User u=new CDN_User();
		u=mo.seled(id);
		String[] a1=new String[3]; 
		String[] a2=new String[3]; 
		a1[0]="user";
		a1[1]="pass";
		a1[2]="get_type";
		a2[0]=user;
		a2[1]=u.getPass();
		a2[2]="_init";
		inputStream=h.ht(url, a1, a2);
		return "ini";
	}
	public String tjcx() throws Exception
	{   
		Htp  h=new Htp();
		String url="";
		if(type.intValue()==0)
		{
			url="http://116.28.64.172:8081/web.flow.fun.php";
		}
		if(type.intValue()==1)
		{
			url="http://116.28.64.172:8081/file.flow.fun.php";
		}
		CDNUserMode mo=new CDNUserMode();
		CDN_User u=new CDN_User();
		u=mo.seled(id);
		String[] a1=new String[6]; 
		String[] a2=new String[6]; 
		a1[0]="user";
		a1[1]="pass";
		a1[2]="get_type";
		a1[3]="time";
		a1[4]="channel";
		a1[5]="zone";

		a2[0]=user;
		a2[1]=u.getPass();
		a2[2]="_flow";
		a2[3]=time;
		a2[4]=channel;
		a2[5]=zone;
	
		inputStream=h.ht(url, a1, a2);
		return "ini";
	}
}

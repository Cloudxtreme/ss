package com.cdn.action;


import java.util.List;

import com.opensymphony.xwork2.ActionSupport;
import com.cdn.mode.ServerMode;
import com.cdn.ado.Server;
public class ServerAction extends ActionSupport {
	private int typ;
	private String ip;
	private List list;
	private int zys;
	private int t;
	private int dqy;
	private Server ad;
	private Integer id;

	public int getTyp() {
		return typ;
	}
	public void setTyp(int typ) {
		this.typ = typ;
	}
	public String getIp() {
		return ip;
	}
	public void setIp(String ip) {
		this.ip = ip;
	}
	public List getList() {
		return list;
	}
	public void setList(List list) {
		this.list = list;
	}
	public int getZys() {
		return zys;
	}
	public void setZys(int zys) {
		this.zys = zys;
	}		
	public int getT() {
		return t;
	}
	public void setT(int t) {
		this.t = t;
	}	
	public int getDqy() {
		return dqy;
	}
	public void setDqy(int dqy) {
		this.dqy = dqy;
	}
	
	public Server getAd() {
		return ad;
	}
	public void setAd(Server ad) {
		this.ad = ad;
	}
	
	public Integer getId() {
		return id;
	}
	public void setId(Integer id) {
		this.id = id;
	}
	public String execute() throws Exception
	{
		ServerMode ser=new ServerMode();
		setZys(ser.yshu(ip, typ));
		if(t==1)
		{
			dqy=dqy+1;	
		}
		if(t==2)
		{
			dqy=dqy-1;		
		}
		if(dqy<=0)
		{
			dqy=1;
		}
	   if(dqy>zys)
	   {
		  dqy=zys; 
	   }
	    list=ser.sel(ip, typ,dqy);
		return SUCCESS;
	}

	public String addview() throws Exception
	{
		return "addview";
	}
	public String editview() throws Exception
	{   
		ServerMode ser=new ServerMode();
		ad=ser.seledit(id, typ);
		return "editview";
	}
	public String add() throws Exception
	{   
		ServerMode ser=new ServerMode();
		ad.setType("node");

		if(ser.add(ad, typ))
		{
		return "addck";
		}
		return "addview";
	}
	public String del() throws Exception
	{   
		ServerMode ser=new ServerMode();
		ser.del(id, typ);
		return "addck";
	}
	
	public String edit() throws Exception
	{   
		ServerMode ser=new ServerMode();
		if(ser.edit(ad, typ))
		{
			return "addck";
		}
		return "editview";
	}

}

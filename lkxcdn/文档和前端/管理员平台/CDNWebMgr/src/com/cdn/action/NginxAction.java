package com.cdn.action;

import java.util.HashMap;
import java.util.List;
import java.util.Map;

import com.cdn.ado.Nginx;
import com.cdn.mode.NginxMode;
import com.opensymphony.xwork2.ActionSupport;

public class NginxAction extends ActionSupport {
  

	private Map<String,String> usermp;
	private String user;
	private List<Nginx> list;
	private Nginx ad;
	private Integer id;
	
	public Integer getId() {
		return id;
	}

	public void setId(Integer id) {
		this.id = id;
	}

	public Nginx getAd() {
		return ad;
	}

	public void setAd(Nginx ad) {
		this.ad = ad;
	}

	public List<Nginx> getList() {
		return list;
	}

	public void setList(List<Nginx> list) {
		this.list = list;
	}

	public String getUser() {
		return user;
	}

	public void setUser(String user) {
		this.user = user;
	}

	public Map<String, String> getUsermp() {
		return usermp;
	}

	public void setUsermp(Map<String, String> usermp) {
		this.usermp = usermp;
	}

	public String execute() throws Exception
	{   
		NginxMode ng=new NginxMode();
		List<String> userlist=ng.seluser();
		usermp=new HashMap<String,String>();
		for(int i=1;i<userlist.size();i=i+2)
		{   if(i<userlist.size()-1)
		 {
		 	usermp.put(userlist.get(i), userlist.get(i+1));
		 }
		if(i==(userlist.size()-1))
		{
			usermp.put(userlist.get(i), "");
		}
			
		}
		return SUCCESS;
	}
	public String seljd() throws Exception
	{   
		NginxMode ng=new NginxMode();
		list=ng.sel(user);
		return "userjd";
	}
	public String addview() throws Exception	
	{   		
		return "addview";
	}
	public String add() throws Exception
	{   
		NginxMode ng=new NginxMode();
	    ad.setUser(user);
	    ad.setStatus("false");
		if(ng.add(ad))
		{
			return "addck";
		}
		ng=null;
		return "addview";
	}
	public String del() throws Exception	
	{   
		NginxMode ng=new NginxMode();
		ng.del(id);
		ng=null; 
		return "addck";
	}

}

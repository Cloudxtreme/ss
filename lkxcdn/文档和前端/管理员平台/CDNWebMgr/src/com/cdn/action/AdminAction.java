package com.cdn.action;

import java.util.List;

import com.opensymphony.xwork2.ActionContext;
import com.opensymphony.xwork2.ActionSupport;
import com.cdn.mode.AdminMode;
import com.cdn.ado.Admin;
import com.cdn.util.MD5Str;
public class AdminAction extends ActionSupport {
	private List list;
	private Admin ad;
	private Integer id;
	
	public Integer getId() {
		return id;
	}

	public void setId(Integer id) {
		this.id = id;
	}

	public Admin getAd() {
		return ad;
	}

	public void setAd(Admin ad) {
		this.ad = ad;
	}

	public List getList() {
		return list;
	}

	public void setList(List list) {
		this.list = list;
	}

	public String execute() throws Exception
	{   
		AdminMode adm=new AdminMode();
		list=adm.selAdmin();
		
		return SUCCESS;
	}   
	public String addview() throws Exception
	{   
		return "addview";
	}
	public String add() throws Exception
	{   
		AdminMode adm=new AdminMode();
		if(adm.addadm(ad))
		{
			return "addck";
		}
		return "addview";
	}
	public String del() throws Exception
	{   
		AdminMode adm=new AdminMode();
        adm.deladm(id);
		return "addck";
	}
	public String editview() throws Exception
	{   
		AdminMode adm=new AdminMode();
        ad=adm.editsel(id);
        ActionContext ex=ActionContext.getContext();
		ex.getSession().put("pass", ad.getPass());
		ad.setPass("");
		return "editview";
	}
	public String edit() throws Exception
	{   
		AdminMode adm=new AdminMode();
		 ActionContext ex=ActionContext.getContext();
		if(ad.getPass().trim().equals(""))
		{   
		   
			ad.setPass(ex.getSession().get("pass").toString());
		
		}
		else
		{
			ad.setPass(MD5Str.EncoderByMd5(ad.getPass()));
		}
		if(adm.editadm(ad))
		{   
			ex.getSession().remove("pass");
			return "addck";
		}
		ad.setPass("");
		return "editview";
	}
	
}

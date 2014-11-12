package com.cdn.action;

import java.io.ByteArrayInputStream;
import java.io.InputStream;
import java.util.ArrayList;
import java.util.List;
import com.cdn.mode.CDNUserMode;
import com.cdn.util.MD5Str;
import com.cdn.ado.CDN_User;
import com.opensymphony.xwork2.ActionContext;
import com.opensymphony.xwork2.ActionSupport;
public class CDNUserAction extends ActionSupport {
	private List list;
	private List a;
	private int dqy;
	private int zys;
	private int t;
	private String  zhm;
	private String khm;
	private String get;
	private Integer ID;
	private CDN_User cdn;
	private InputStream inputStream;
	public int getDqy() {
		return dqy;
	}

	public void setDqy(int dqy) {
		this.dqy = dqy;
	}

	public int getZys() {
		return zys;
	}

	public void setZys(int zys) {
		this.zys = zys;
	}

	public List getList() {
		return list;
	}

	public void setList(List list) {
		this.list = list;
	}

	public List getA() {
		return a;
	}

	public void setA(List a) {
		this.a = a;
	}
   
	public int getT() {
		return t;
	}

	public void setT(int t) {
		this.t = t;
	}

	public String getZhm() {
		return zhm;
	}

	public void setZhm(String zhm) {
		this.zhm = zhm;
	}

	public String getKhm() {
		return khm;
	}

	public void setKhm(String khm) {
		this.khm = khm;
	}

	public String getGet() {
		return get;
	}

	public void setGet(String get) {
		this.get = get;
	}

	public Integer getID() {
		return ID;
	}

	public void setID(Integer iD) {
		ID = iD;
	}

	public CDN_User getCdn() {
		return cdn;
	}

	public void setCdn(CDN_User cdn) {
		this.cdn = cdn;
	}

	public InputStream getResult() {
		return inputStream;
	}

	public String execute() throws Exception
	{  
		CDNUserMode adm=new CDNUserMode();
		if(get!=null&&get.equals("1"))
		{
			khm=new String(khm.getBytes("ISO-8859-1"),"utf-8");
		}
		zys=adm.yshu(zhm,khm);
		a=new ArrayList();
		for(int i=0;i<zys;i++)
		{
		a.add(i, i+1);
		}
		if(t==1&dqy!=0)
		{
			dqy=dqy+1;
		}
		if(t==2&dqy!=0)
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
		int[] s=adm.js(dqy,zhm,khm);
		list=adm.sel(s[0], s[1],zhm,khm);
		return SUCCESS;
	} 
	public String editview() throws Exception
	{ 
		CDNUserMode adm=new CDNUserMode();
		cdn=adm.seled(ID);
		ActionContext ex=ActionContext.getContext();
		ex.getSession().put("cdnpass", cdn.getPass());
		cdn.setPass("");
		return "editview";
	}
	public String edit() throws Exception
	{   
		CDNUserMode adm=new CDNUserMode();
		ActionContext ex=ActionContext.getContext();
        if(cdn.getPass()==null||cdn.getPass().equals(""))
        {
        	cdn.setPass(ex.getSession().get("cdnpass").toString());
        }
        else
        {
        	cdn.setPass(MD5Str.EncoderByMd5(cdn.getPass()));
        }
		if(adm.edit(cdn)==true)
		{
			ex.getSession().remove("cdnpass");
			return "cg";
		}
		else
		{
			cdn.setPass("");
		}
		return "editview";
	}
	public String cdndl() throws Exception
	{
		CDNUserMode adm=new CDNUserMode();
		cdn=adm.seled(ID);
		String js="";
		js="var user='"+cdn.getUser()+"';var pass='"+cdn.getPass()+"';";
		inputStream=new ByteArrayInputStream(js.getBytes("UTF-8"));
		return "js";
	}
}

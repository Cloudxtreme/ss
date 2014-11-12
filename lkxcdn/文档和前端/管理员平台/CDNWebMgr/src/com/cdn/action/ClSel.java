package com.cdn.action;

import java.util.List;

import com.opensymphony.xwork2.ActionSupport;
import com.cdn.ado.Webcl;
import com.cdn.mode.WebclMode;
public class ClSel extends ActionSupport {
	private List<Webcl> list;
	
	public List<Webcl> getList() {
		return list;
	}

	public void setList(List<Webcl> list) {
		this.list = list;
	}

	public String execute() throws Exception
	{  
		WebclMode cl=new WebclMode();
		list=cl.sel();
		return SUCCESS;
	}
}

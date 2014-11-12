package com.cdn.mode;

import java.util.ArrayList;
import java.util.List;
import com.cdn.util.DBCon;
import java.sql.Connection;
import java.sql.Statement;
import java.sql.ResultSet;

import org.hibernate.Query;
import org.hibernate.Session;
import org.hibernate.Transaction;

import com.cdn.util.FileHbUtil;
import com.cdn.ado.Nginx;
public class NginxMode {
public List<String> seluser()
{
	List<String> list=new ArrayList<String>();
	try
	{
		DBCon db=new DBCon();
		Connection con=db.getfile();
		Statement st=con.createStatement();
		ResultSet rs=st.executeQuery("select user from user_nginx group by user");
		while(rs.next())
		{
			list.add(rs.getString("user"));
		}
		rs.close();
		st.close();
		con.close();
	}catch(Exception e)
	{
		e.printStackTrace();
	}
	return list;
}
public List<Nginx> sel(String user)
{
	List<Nginx> list=new ArrayList();
	try
	{
		Session session=FileHbUtil.currentSession();
		Transaction tx=session.beginTransaction();
		Query ql=session.createQuery("from Nginx as a where a.user='"+user+"'");
		list=ql.list();
		FileHbUtil.closeSession();
	}catch(Exception e)
	{
		e.printStackTrace();
	}
	return list;
}
public boolean add(Nginx ng)
{
	boolean bl=false;
	try
	{
		Session session=FileHbUtil.currentSession();
		Transaction tx=session.beginTransaction();
		session.saveOrUpdate(ng);
		tx.commit();
		bl=true;
		FileHbUtil.closeSession();
	}catch(Exception e)
	{
		e.printStackTrace();
	}
	return bl;
}

public boolean del(Integer id)
{
	boolean bl=false;
	try
	{
		Session session=FileHbUtil.currentSession();
		Transaction tx=session.beginTransaction();
		Nginx ng=(Nginx)session.get(Nginx.class, id);
	    session.delete(ng);	    
		tx.commit();
		bl=true;
		FileHbUtil.closeSession();
	}catch(Exception e)
	{
		e.printStackTrace();
	}
	return bl;
}
}

package com.cdn.mode;
import java.util.List;
import org.hibernate.Query;
import org.hibernate.Session;
import org.hibernate.Transaction;
import com.cdn.ado.Server;
import com.cdn.util.WebHbUtil;
import com.cdn.util.FileHbUtil;
public class ServerMode {
	public int selnum(String ip,int type)
	{
		int num=0;
		Session session=null;
		try
		{   if(type==0)
		  {
			session=WebHbUtil.currentSession();
		  }
		  if(type==1)
		  {
			  session= FileHbUtil.currentSession();
		  }
			Transaction tx=session.beginTransaction();
			String hql="select count(*) from Server as a where a.type='node'";
			if(ip!=null&&ip.equals("")==false)
			{
				hql=hql+" and a.ip like '%"+ip+"%'";
			}
			Query ql=session.createQuery(hql);
			List list=ql.list();
		    num=new Integer(list.get(0).toString()).intValue();
		    if(type==0)
		    {
		    	WebHbUtil.closeSession();
		    }
		    else if(type==1)
		    {
		    	FileHbUtil.closeSession();
		    }
		   
		}catch(Exception e)
		{
			e.printStackTrace();
		}
		return num;
	}
	public int yshu(String ip,int type)
	{   int a=0;
		int num=selnum(ip,type);
		if(num%10==0)
		{
			a=num/10;
		}
		else
		{
			a=num/10+1;
		}
		return a;
	}
	public List sel(String ip,int type,int dqy)
	{   
		List list=null;
		Session session=null;
		try
		{   if(type==0)
		  {
			session=WebHbUtil.currentSession();
		  }
		  if(type==1)
		  {
			  session= FileHbUtil.currentSession();
		  }
			Transaction tx=session.beginTransaction();
			String hql="from Server as a where a.type='node'";
			if(ip!=null&&ip.equals("")==false)
			{
				hql=hql+" and a.ip like '%"+ip+"%'";
			}
			Query ql=session.createQuery(hql);
			ql.setFirstResult((dqy-1)*10);
			ql.setMaxResults(10);
            list=ql.list();	
            if(type==0)
		    {
		    	WebHbUtil.closeSession();
		    }
		    else if(type==1)
		    {
		    	FileHbUtil.closeSession();
		    }
		}catch(Exception e)
		{
			e.printStackTrace();
		}
		return list;
	}
	
	public boolean add(Server s,int type)
	{
		boolean fl=false;
		Session session=null;
		try
		{
			if(type==0)
			  {
				session=WebHbUtil.currentSession();
			  }
			  if(type==1)
			  {
				  session= FileHbUtil.currentSession();
			  }
			  Transaction tx=session.beginTransaction();
			  session.save(s);
			  tx.commit();
			  fl=true;
			  if(type==0)
			    {
			    	WebHbUtil.closeSession();
			    }
			    else if(type==1)
			    {
			    	FileHbUtil.closeSession();
			    }
		}catch(Exception e)
		{
			e.printStackTrace();
		}
		return fl;
	}
	
	public boolean del(Integer id,int type)
	{
		boolean fl=false;
		Session session=null;
		try
		{   if(id!=null&&id.intValue()!=0)
		{
			
			if(type==0)
			  {
				session=WebHbUtil.currentSession();
			  }
			  if(type==1)
			  {
				  session= FileHbUtil.currentSession();
			  }
			  Transaction tx=session.beginTransaction();
		      Server s=(Server)session.get(Server.class, id);
		      session.delete(s);
		      tx.commit();
		      fl=true;
			  if(type==0)
			    {
			    	WebHbUtil.closeSession();
			    }
			    else if(type==1)
			    {
			    	FileHbUtil.closeSession();
			    }
			}
		}catch(Exception e)
		{
			e.printStackTrace();
		}
		return fl;
	}
	public Server seledit(Integer id,int type)
	{
		Server s=null;
		Session session=null;
		try
		{ 
			if(id!=null&&id.intValue()!=0)
		{
			

				if(type==0)
				  {
					session=WebHbUtil.currentSession();
				  }
				  if(type==1)
				  {
					  session= FileHbUtil.currentSession();
				  }
				  
				 Transaction tx=session.beginTransaction();
			     s=(Server)session.get(Server.class, id);
				  if(type==0)				    
				 {
				    WebHbUtil.closeSession();
				 }
				 else if(type==1)
				 {
				   FileHbUtil.closeSession();
				 } 
		}
			
		}catch(Exception e)
		{
			e.printStackTrace();
		}
		return s;
	}
	public boolean edit(Server s,int type)
	{
		boolean fl=false;
		Session session=null;
		try
		{
			if(type==0)
			  {
				session=WebHbUtil.currentSession();
			  }
			  if(type==1)
			  {
				  session= FileHbUtil.currentSession();
			  }
			Transaction tx=session.beginTransaction();
			session.saveOrUpdate(s);
			tx.commit();
			fl=true;			  
			  if(type==0)				    
				 {
				    WebHbUtil.closeSession();
				 }
				 else if(type==1)
				 {
				   FileHbUtil.closeSession();
				 } 
		}catch(Exception e)
		{
			e.printStackTrace();
		}
		return fl;
	}
}

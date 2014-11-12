package com.cdn.mode;
import com.cdn.util.CDNUserHbUtil;
import java.util.List;
import com.cdn.ado.CDN_User;
import org.hibernate.Query;
import org.hibernate.Session;
import org.hibernate.Transaction;
public class CDNUserMode {
public List sel(int f,int l,String zhm,String khm)
{
	List list=null;
	try
	{   
		Session session=CDNUserHbUtil.currentSession();
		Transaction tx=session.beginTransaction();
		String hql="from CDN_User as a where IsAction='1'";
		if(zhm!=null&&zhm.equals("")==false)
		{
			hql=hql+" and a.User like '%"+zhm+"%'";
		}
		if(khm!=null&&khm.equals("")==false)
		{
			hql=hql+" and a.Name like '%"+khm+"%'";
		}
		hql=hql+"order by a.Type,a.Name";
		Query q=session.createQuery(hql);
		q.setFirstResult(f);
		q.setMaxResults(10);
		list=q.list();
		CDNUserHbUtil.closeSession();
	}catch(Exception e)
	{
		
	}
	return list;
}
public int selnum(String zhm,String khm)
{
	int num=0;
	try
	{
		Session session=CDNUserHbUtil.currentSession();
		Transaction tx=session.beginTransaction();
		String hql="select count(*) from CDN_User as a where IsAction='1'";
		if(zhm!=null&&zhm.equals("")==false)
		{
			hql=hql+" and a.User like '%"+zhm+"%'";
		}
		if(khm!=null&&khm.equals("")==false)
		{
			hql=hql+" and a.Name like '%"+khm+"%'";
		}
		Query ql=session.createQuery(hql);
		List list=ql.list();
	    num=new Integer(list.get(0).toString()).intValue();
	    CDNUserHbUtil.closeSession();
	}catch(Exception e)
	{
		e.printStackTrace();
	}
	return num;
}
public int[] js(int i,String zhm,String khm)
{
	int[] a=new int[2];
	int num=selnum(zhm,khm);
    int yeshu=yshu(zhm,khm);
    if(i<yeshu)
    {
    	a[0]=(i-1)*10;
    	a[1]=i*10-1;
    }
    else {
    	if(num%10==0)
    	{
    	a[0]=(i-1)*10;
        a[1]=i*10-1;
    	}
    	else
    	{
    		a[0]=(i-1)*10;
    		a[1]=(i-1)*10+num%10-1;
    	}
    }
    	
	return a;
}
public int yshu(String zhm,String khm)
{   int a=0;
	int num=selnum(zhm,khm);
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
public CDN_User seled(Integer ID)
{
	CDN_User cdn=null;
	try
	{  
		if(ID!=null)
	{
		Session session=CDNUserHbUtil.currentSession();
		Transaction tx=session.beginTransaction();
		cdn=(CDN_User)session.get(CDN_User.class, ID);
	    CDNUserHbUtil.closeSession();
	}
	}catch(Exception e)
	{
		e.printStackTrace();
	}
	return cdn;
}
public boolean edit(CDN_User cdn)
{
	boolean bl=false;
	try
	{
		Session session=CDNUserHbUtil.currentSession();
		Transaction tx=session.beginTransaction();
		session.saveOrUpdate(cdn);
		tx.commit();
		bl=true;
		CDNUserHbUtil.closeSession();	
	}catch(Exception e)
	{
		e.printStackTrace();
	}
	return bl;
}
}

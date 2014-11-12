package com.cdn.mode;
import java.util.List;
import com.cdn.util.HibernateUtil;
import com.cdn.util.MD5Str;
import org.hibernate.Session;
import org.hibernate.Transaction;
import com.cdn.ado.Admin;
public class AdminMode {
public List selAdmin()
{
	List list=null;
   try{
	Session session=HibernateUtil.currentSession();
	Transaction tx=session.beginTransaction();
	list=session.createQuery("from Admin").list();
	HibernateUtil.closeSession();
   }catch(Exception e)
   {
	   e.printStackTrace();
   }
	return list;
}

public boolean addadm(Admin ad)
{
	boolean bl=false;
	try
	{

		if(ad!=null&&ad.getUser()!=""&&ad.getPass()!=""&&ad.getRole()!="")
		{
		Session session=HibernateUtil.currentSession();
		Transaction tx=session.beginTransaction();
		ad.setStatus("true");
		ad.setPass(MD5Str.EncoderByMd5(ad.getPass()));
		session.save(ad);
		tx.commit();
		bl=true;
		HibernateUtil.closeSession();
		}
	}
	catch(Exception e)
	{
		e.printStackTrace();
	}
	return bl;
}
public boolean deladm(Integer id)
{
	boolean bl=false;
	try
	{
		if(id!=null&&id.intValue()!=0)
		{
			Session session=HibernateUtil.currentSession();
			Transaction tx=session.beginTransaction();	
			Admin ad=(Admin) session.get(Admin.class,id);
			session.delete(ad);
			tx.commit();
			bl=true;
			HibernateUtil.closeSession();
		}
	}
	catch(Exception e)
	{
		e.printStackTrace();
	}
	return bl;
}
public boolean editadm(Admin ad)
{
	boolean bl=false;
	try
	{
		if(ad!=null&&ad.getUser()!=""&&ad.getPass()!=""&&ad.getRole()!="")
		{
			Session session=HibernateUtil.currentSession();
			Transaction tx=session.beginTransaction();
			session.saveOrUpdate(ad);
			tx.commit();
			bl=true;
			HibernateUtil.closeSession();
		}
	}catch(Exception e)
	{
		e.printStackTrace();
	}
	return bl;
}
public Admin editsel(Integer id)
{
	Admin ad=null;
	try
	{
     if(id!=null&&id.intValue()!=0)
	{
 		Session session=HibernateUtil.currentSession();
		Transaction tx=session.beginTransaction();	
		ad=(Admin) session.get(Admin.class,id);	
		HibernateUtil.closeSession();
	}
	}catch(Exception e)	
	{
		e.printStackTrace();
	}
	return ad;
}
}

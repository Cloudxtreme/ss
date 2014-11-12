package com.cdn.util;
import org.hibernate.HibernateException;  
import org.hibernate.Session;  
import org.hibernate.SessionFactory;  
import org.hibernate.cfg.Configuration;  
  
public class CDNUserHbUtil {  
    private static final SessionFactory sessionFactory;  
    private static final ThreadLocal m_session = new ThreadLocal();  
      
    static {  
        try{  
            sessionFactory = new Configuration().configure("/cdnuser.cfg.xml").buildSessionFactory();  
        }catch(HibernateException ex){  
            throw new RuntimeException("¥¥Ω®SessionFactory ß∞‹: " + ex.getMessage(), ex);  
        }  
    }  
      
    public static Session currentSession() throws HibernateException {  
        Session s = (Session) m_session.get();  
        if (s == null) {  
            s = sessionFactory.openSession();  
            m_session.set(s);  
        }  
        return s;  
    }  
      
    public static void closeSession() throws HibernateException {  
        Session s = (Session) m_session.get();  
        m_session.set(null);  
        if (s != null)  
            s.close();  
    }  
}  
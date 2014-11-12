package com.cdn.util;
import java.security.MessageDigest;
public class MD5Str {
	public static String EncoderByMd5(String str) throws Exception {
        MessageDigest md5=MessageDigest.getInstance("md5");//����ʵ��ָ��ժҪ�㷨�� MessageDigest ����
        md5.update(str.getBytes());//�Ƚ��ַ���ת����byte���飬����byte �������ժҪ
        byte[] nStr = md5.digest();//��ϣ���㣬������
        return bytes2Hex(nStr);//���ܵĽ����byte���飬��byte����ת�����ַ���
    }
    private static String bytes2Hex(byte[] bts) {
        String des = "";
        String tmp = null;

        for (int i = 0; i < bts.length; i++) {
            tmp = (Integer.toHexString(bts[i] & 0xFF));
            if (tmp.length() == 1) {
                des += "0";
            }
            des += tmp;
        }
        return des;
    }
}

#!/bin/bash
/etc/init.d/irqbalance stop
cat /proc/interrupts | grep eth0 | cut -f 1 -d ":" > /root/abc.txt

sed -n -e '1p' /root/abc.txt > 1.txt
cat /root/1.txt|while read line
do
echo "1" > /proc/irq/$line/smp_affinity
done

sed -n -e '2p' /root/abc.txt > 2.txt
cat /root/2.txt|while read line
do
echo "2" > /proc/irq/$line/smp_affinity
done

sed -n -e '3p' /root/abc.txt > 3.txt
cat /root/3.txt|while read line
do
echo "4" > /proc/irq/$line/smp_affinity
done

sed -n -e '4p' /root/abc.txt > 4.txt
cat /root/4.txt|while read line
do
echo "8" > /proc/irq/$line/smp_affinity
done

sed -n -e '5p' /root/abc.txt > 5.txt
cat /root/5.txt|while read line
do
echo "10" > /proc/irq/$line/smp_affinity
done

sed -n -e '6p' /root/abc.txt > 6.txt
cat /root/6.txt|while read line
do
echo "20" > /proc/irq/$line/smp_affinity
done

sed -n -e '7p' /root/abc.txt > 7.txt
cat /root/7.txt|while read line
do
echo "40" > /proc/irq/$line/smp_affinity
done

sed -n -e '8p' /root/abc.txt > 8.txt
cat /root/8.txt|while read line
do
echo "80" > /proc/irq/$line/smp_affinity
done

rm -f *.txt 

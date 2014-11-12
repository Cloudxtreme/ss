ifneq ($(KERNELRELEASE),)
	obj-m := ef_natlog_afnat.o
else
	KERNELDIR := /lib/modules/$(shell uname -r)/build
PWD := $(shell pwd)
default:
	$(MAKE) -C $(KERNELDIR) M=$(PWD) modules
endif
clean:
	rm -f *.ko *.mod.c *.mod.o *.o

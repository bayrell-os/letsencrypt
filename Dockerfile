ARG ARCH=amd64
FROM bayrell/alpine_php_fpm:8.0-${ARCH}

COPY files /
ADD src /var/www/html

RUN cd ~; \
	chmod +x /root/run.sh; \
	echo "Ok"
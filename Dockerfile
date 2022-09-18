ARG ARCH=amd64
FROM bayrell/alpine_php_fpm:8.0-${ARCH}

RUN cd ~; \
	apk add certbot openssl; \
	rm -rf /var/www/html; \
	rm -rf /var/cache/apk/*; \
	echo "Ok"

COPY files /
ADD src /var/www/html

RUN cd ~; \
	ln -s /data/letsencrypt /etc/letsencrypt; \
	mkdir -p /var/www/letsencrypt/.well-known; \
	chmod +x /root/run.sh; \
	echo "Ok"
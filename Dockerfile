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
	echo "*/15 * * * * sudo -E -u www php /var/www/html/console.php ssl:update" >> /etc/crontabs/root; \
	echo "32 2 * * * /root/ssl.renew.sh" >> /etc/crontabs/root; \
	rm -f /root/run.d/10-cron.enable.sh; \
	rm -f /etc/supervisor.d/php-fpm.ini; \
	ln -s /data/letsencrypt/etc /etc/letsencrypt; \
	ln -s /data/letsencrypt/lib /var/lib/letsencrypt; \
	ln -s /data/letsencrypt/log /var/log/letsencrypt; \
	mkdir -p /var/www/letsencrypt/.well-known; \
	chmod +x /root/*.sh; \
	echo "Ok"

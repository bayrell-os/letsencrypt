
if [ ! -d /data/letsencrypt ]; then
	mkdir -p /data/letsencrypt
	chown www:www /data/letsencrypt
fi

if [ ! -d /data/letsencrypt/etc ]; then
	mkdir -p /data/letsencrypt/etc
	chown www:www /data/letsencrypt/etc
fi

if [ ! -d /data/letsencrypt/log ]; then
	mkdir -p /data/letsencrypt/log
	chown www:www /data/letsencrypt/log
fi

if [ ! -d /data/letsencrypt/lib ]; then
	mkdir -p /data/letsencrypt/lib
	chown www:www /data/letsencrypt/lib
fi

chown -R www:www /var/www/letsencrypt
echo $DOCKER_SERVICE_NAME > /var/www/letsencrypt/.well-known/info.txt


if [ ! -d /data/letsencrypt ]; then
	mkdir -p /data/letsencrypt
	chown www:www /data/letsencrypt
fi

chown -R www:www /var/www/letsencrypt
echo $DOCKER_SERVICE_NAME > /var/www/letsencrypt/.well-known/info.txt

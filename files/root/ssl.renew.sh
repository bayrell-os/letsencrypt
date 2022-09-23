#!/bin/bash

python3 -c 'import random; import time; time.sleep(random.random() * 3600)'
sudo -E -u www certbot renew

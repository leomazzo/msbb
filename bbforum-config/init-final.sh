echo "Adding Swapfile ...."
dd if=/dev/zero of=/swapfile1 bs=1024 count=524288
chown root:root /swapfile1
chmod 0600 /swapfile1
mkswap /swapfile1
swapon /swapfile1
echo "/swapfile1 none swap sw 0 0" >> /etc/fstab

echo "Downloading, Unzipping and doing some settings in BB Forum...."
cd /tmp/
wget https://resources.mybb.com/downloads/mybb_1809.zip
unzip mybb_1809.zip
mv Upload bbforum
cp -r bbforum /var/www/html/
rm -rf Documentation mybb_1809.zip
cd /var/www/html/bbforum
sed -i 119d /etc/httpd/conf/httpd.conf
echo 'DocumentRoot "/var/www/html/bbforum"' >> /etc/httpd/conf/httpd.conf

service httpd restart
rm -rf /var/www/html/bbforum/install
rm -rf /var/www/html/bbforum/inc/settings.php
rm -rf /var/www/html/bbforum/inc/config.php
cd /var/www/html/bbforum/
cp /tmp/msbb-master/bbforum-config/settings.php /var/www/html/bbforum/inc/settings.php
cp /tmp/msbb-master/bbforum-config/config-final.php /var/www/html/bbforum/inc/config.php
tar -xf /tmp/msbb-master/bbforum-config/themes.tar -C /var/www/html/bbforum/cache
chmod -R 0777 cache uploads inc/settings.php inc/config.php


echo "Deploying Management Systems......"
cd /tmp
cp -r /tmp/msbb-master/msbb /var/www/html/bbforum/
echo "Configuration ended...."


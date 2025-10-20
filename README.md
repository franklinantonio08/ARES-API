/* Elimina la carpeta del Proyecto Apolo */ 
rm -rf /var/www/html/ARES-API


/* Baja del Git el proyecto Completo */
git clone https://github.com/franklinantonio08/ARES-API.git

/* Entramos al directorio del proyecto*/
cd ARES-API

/* Actualizamos el Composer */
composer update 

/* copia el example.env a .env */ 
mv .env.example .env
chmod 600 .env
chown apache:apache .env  


/* Permisos al storage*/
sudo chown -R apache:apache /var/www/html/ARES-API/storage/
sudo chown -R apache:apache /var/www/html/ARES-API/bootstrap/cache

sudo chmod -R 775 /var/www/html/ARES-API/storage
sudo chmod -R 775 /var/www/html/ARES-API/bootstrap/cache

chown -R apache:apache storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

sudo semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/html/ARES-API/storage(/.*)?"
sudo semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/html/ARES-API/bootstrap/cache(/.*)?"

sudo restorecon -Rv /var/www/html/ARES-API/storage
sudo restorecon -Rv /var/www/html/ARES-API/bootstrap/cache


sudo chmod -R 775 /var/www/html/ARES-API/storage/app/public
sudo chown -R apache:apache /var/www/html/ARES-API/storage/app/public


php artisan storage:link

/* http - https*/
sudo nano /etc/httpd/conf.d/ares-api.conf

                                                                                   
<VirtualHost *:443>
    ServerName 172.20.17.29
    DocumentRoot /var/www/html/ARES-API/public

    <Directory /var/www/html/ARES-API/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog /var/log/httpd/ares_error.log
    CustomLog /var/log/httpd/ares_access.log combined
</VirtualHost>


/* borra cache */
php artisan optimize:clear 

/* caché para producción */
php artisan config:cache 
php artisan route:cache 
php artisan view:cache 
php artisan event:cache

/* Eliminina el log si esta muy pesado*/
echo "" > storage/logs/laravel.log


/* MYSQL */

sudo systemctl status mysqld

sudo systemctl start mysqld

sudo systemctl restart mysqld

sudo systemctl stop mysqld

sudo systemctl enable mysqld

/* APACHE */

sudo systemctl status httpd

sudo systemctl start httpd

sudo systemctl restart httpd

sudo systemctl stop httpd

sudo systemctl enable httpd

/etc/httpd/conf.d/ssl.conf



filtro de aceite 
filtro de motor 
filtro de cabina
aceite de motor 
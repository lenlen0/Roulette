#!/bin/sh

echo -e "\n### Requirement ###"
echo -e "The root folder (project name) and the sql file (config/project.sql) have to hold the same name\n"

read -p "Press enter to continue or ctrl+c to quit"

echo -e "\nEnter your project name"
read name

echo -e "\nChoose your OS [0]:\n0: LAMP\n1: MAMP\n2: AMPPS"
read os
# Set default value
os=${os:-0}

##########
##########
echo -e "\n---SQL CONFIGURATION---"
##########
##########

if [ $os -eq 0 ]; then
	apt install git apache2 php php-mysql mysql-client mysql-server

	echo -e "\nDB_HOST [localhost]:"
	read host
	host=${host:-localhost}

	echo -e "\nDB_USER [root]:"
	read user
	user=${user:-root}

	echo -e "\nDB_PWD [root]:"
	read -s pwd
	pwd=${pwd:-root}

	MYSQL="mysql --host=$host -u$user -p$pwd"
	$MYSQL -e "DROP DATABASE IF EXISTS $name;"
	$MYSQL -e "CREATE DATABASE IF NOT EXISTS $name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
	$MYSQL $name < $name.sql
	
elif [ $os -eq 1 ]; then
	host="localhost"
	user="root"
	pwd="root"

	MYSQL="/Applications/MAMP/Library/bin/mysql --host=$host -u$user -p$pwd"
	$MYSQL -e "DROP DATABASE IF EXISTS $name;"
	$MYSQL -e "CREATE DATABASE IF NOT EXISTS $name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
	$MYSQL $name < $name.sql

elif [ $os -eq 2 ]; then
	host="localhost"
	user="root"
	pwd="mysql"

	MYSQL="/Applications/Ampps/mysql/bin/mysql --host=$host -u$user -p$pwd"
	$MYSQL -e "DROP DATABASE IF EXISTS $name;"
	$MYSQL -e "CREATE DATABASE IF NOT EXISTS $name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
	$MYSQL $name < $name.sql
fi

##########
##########
echo -e "\n---PHP CONFIGURATION---"
##########
##########

rm -f config.php
touch config.php
cat > config.php << EOF
<?php

	define('DB_HOST',    '$host');
	define('DB_NAME',    '$name');
	define('DB_USER',    '$user');
	define('DB_PWD',     '$pwd');
EOF

##########
##########
echo -e "\n---HTACCESS and HTPASSWD---"
##########
##########

if [ $os -eq 0 ]; then
	echo -e "\nDo you need to protect a page ? [0]:\n0: no\n1: yes"
	read protect
	protect=${protect:-0}

	if [ $protect -eq 1 ]; then
		echo -e "\nEnter the page name to protect [admin.php]"
		read pageName
		pageName=${pageName:-admin.php}

		echo -e "\nFor security, the .htpasswd file will be store in /etc/apache2/HTPASSWD/$name/"

		mkdir -p /etc/apache2/HTPASSWD/$name

		rm -f ../.htaccess
		touch ../.htaccess
		cat > ../.htaccess << EOF
<Files $pageName>
	AuthName "Page d'administration"
	AuthType Basic
	AuthUserFile "/etc/apache2/HTPASSWD/$name/.htpasswd"
	Require valid-user
</Files>
EOF
		echo -e "\nLogin [admin]:"
		read login
		login=${login:-admin}

		echo -e "\nPassword [admin]:"
		read password
		password=${password:-admin}

		MD5=`htpasswd -m -b -n $login $password`

		rm -f /etc/apache2/HTPASSWD/$name/.htpasswd
		touch /etc/apache2/HTPASSWD/$name/.htpasswd
		cat > /etc/apache2/HTPASSWD/$name/.htpasswd << EOF
# htpasswd Generator : https://hostingcanada.org/htpasswd-generator/
# Use MD5
$MD5
EOF
		rm -f /etc/apache2/sites-available/$name.conf
		touch /etc/apache2/sites-available/$name.conf
		cat > /etc/apache2/sites-available/$name.conf << EOF
<Directory /var/www/$name>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
</Directory>
EOF
		a2ensite $name.conf
		service apache2 restart
	fi

elif [ $os -eq 1 ]; then
	echo -e "\nNo configuration needed"

elif [ $os -eq 2 ]; then
	echo -e "\nNo configuration needed"
fi

##########
##########
echo -e "\n---RIGHTS CONFIGURATION---"
##########
##########

if [ $os -eq 0 ]; then
	chown -R www-data:www-data /var/www/$name

	find /var/www/$name -type d -exec chmod 750 {} \;
	find /var/www/$name -type f -exec chmod 640 {} \;

elif [ $os -eq 1 ]; then
	echo -e "\nNo configuration needed"

elif [ $os -eq 2 ]; then
	echo -e "\nNo configuration needed"
fi

##########
##########
# echo -e "\n---FOR THIS PROJECT---"
##########
##########
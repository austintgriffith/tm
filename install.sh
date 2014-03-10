 #!/bin/bash
sudo yum update -y
sudo yum install httpd php git -y
sudo rm -Rf /var/www/html
sudo chmod -R 0777 /var/www
cd /var/www
sudo chmod -R 0777 /var/www/html
sudo service httpd start
git clone https://github.com/austintgriffith/trustymusket.git html
php /var/www/html/startup.php > /var/www/html/startup.txt





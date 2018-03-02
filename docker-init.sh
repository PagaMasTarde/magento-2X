#!/bin/bash
ENVIROMENT=$1
echo 'Build docker images'
docker-compose down
docker-compose up -d --build magento2-${ENVIROMENT}
if [ $1 == 'dev' ]
then
docker-compose up -d phpmyadmin
fi
docker-compose up -d selenium
sleep 10

echo 'Install Magento'
docker-compose exec magento2-${ENVIROMENT} composer install
docker-compose exec magento2-${ENVIROMENT} install-magento
echo 'Install DigitalOrigin_Pmt'
#docker-compose exec --user=www-data magento2-${ENVIROMENT} mkdir -p /var/www/html/app/code/DigitalOrigin && \
#docker-compose exec --user=www-data magento2-${ENVIROMENT} ln -s /var/www/paylater /var/www/html/app/code/DigitalOrigin/Pmt && \
#docker-compose exec --user=www-data magento2-${ENVIROMENT} php /var/www/html/bin/magento module:enable DigitalOrigin_Pmt && \
echo 'Sample Data + DI + SetupUpgrade + Clear Cache'
docker-compose exec magento2-${ENVIROMENT} install-sampledata
docker-compose exec -u www-data magento2-${ENVIROMENT} /var/www/html/bin/magento cron:run
echo 'Build of Magento2 enviroment complete: http://magento2'

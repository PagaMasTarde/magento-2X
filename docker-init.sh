#!/bin/bash
ENVIROMENT=$1
echo 'Build docker images'
docker-compose down
docker-compose up -d --build magento2-${ENVIROMENT}
docker-compose up -d selenium
sleep 10

docker-compose exec magento2-${ENVIROMENT} docker-php-ext-install bcmath

echo 'Install Magento'
docker-compose exec magento2-${ENVIROMENT} install-magento
echo 'Install DigitalOrigin_Pmt'
if [ $1 == 'dev' ]
then
    docker-compose exec -u www-data magento2-${ENVIROMENT} mkdir -p /var/www/html/app/code/DigitalOrigin && \
    docker-compose exec -u www-data magento2-${ENVIROMENT} ln -s /var/www/paylater /var/www/html/app/code/DigitalOrigin/Pmt && \
    docker-compose exec -u www-data magento2-${ENVIROMENT} php /var/www/html/bin/magento module:enable DigitalOrigin_Pmt && \
    docker-compose exec magento2-${ENVIROMENT} chown -R www-data. /var/www/paylater
    docker-compose exec -u www-data magento2-${ENVIROMENT} composer install -d /var/www/paylater
else
    if [ ! -z "$TRAVIS_PULL_REQUEST_BRANCH" ]
    then
        echo "Esta es la rama del pull request" ${TRAVIS_PULL_REQUEST_BRANCH}
        package=${TRAVIS_PULL_REQUEST_BRANCH}'.x-dev'
    fi

    if [ ! -z "$TRAVIS_TAG" ]
    then
        echo "Esta es la rama del tag:" ${TRAVIS_TAG}
        package=${TRAVIS_TAG}
    fi
    if [ ! -z "$TRAVIS_BRANCH" ]
    then
        echo "Esta es la rama del branch:" ${TRAVIS_BRANCH}
        package=${TRAVIS_BRANCH}'.x-dev'
    fi
    if [ -z "$package" ]
    then
        echo "Esta es la rama master:" ${TRAVIS_TAG}
        package='dev-master'
    fi

    echo 'Package: '$package
    docker-compose exec -u www-data magento2-${ENVIROMENT} php /var/www/html/bin/magento cache:enable
    docker-compose exec -u www-data magento2-${ENVIROMENT} php /var/www/html/bin/magento deploy:mode:set production
    docker-compose exec -u www-data magento2-${ENVIROMENT} composer require pagamastarde/magento-2x:$package \
     -d /var/www/html
    docker-compose exec -u www-data magento2-${ENVIROMENT} php /var/www/html/bin/magento module:enable DigitalOrigin_Pmt
fi


echo 'Sample Data + DI + SetupUpgrade + Clear Cache'
docker-compose exec -u www-data magento2-${ENVIROMENT} composer config http-basic.repo.magento.com \
    5310458a34d580de1700dfe826ff19a1 \
    255059b03eb9d30604d5ef52fca7465d
docker-compose exec -u www-data magento2-${ENVIROMENT} php /var/www/html/bin/magento sampledata:deploy
docker-compose exec -u www-data magento2-${ENVIROMENT} php /var/www/html/bin/magento cron:run
docker-compose exec -u www-data magento2-${ENVIROMENT} php /var/www/html/bin/magento setup:upgrade
docker-compose exec -u www-data magento2-${ENVIROMENT} php /var/www/html/bin/magento setup:di:compile

containerPort=$(docker container port magento2${ENVIROMENT})
PORT=$(sed  -e 's/.*://' <<< $containerPort)
echo 'Build of Magento2 complete: http://magento2-'${ENVIROMENT}'.docker:'${PORT}

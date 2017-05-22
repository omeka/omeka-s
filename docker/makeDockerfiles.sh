#!/bin/bash

# Get to the parent path.
parent_path=$( cd "$(dirname "${BASH_SOURCE[0]}")" ; pwd -P )
cd "$parent_path"

### TO RUN, REMEMBER TO LOGIN.
# docker login -e $DOCKER_EMAIL -u $DOCKER_USER -p $DOCKER_PASS

DOCKER_USER='digirati'
PACKAGE_NAME='omeka-s'
PHP_VERSIONS=(7.0.18 7.1 5.6.30);
WEB_SERVERS=(fpm apache);

function buildDockerFile {
  echo -e "\033[00;32m========================================================";
  echo -e "Building Docker image...";
  echo -e "========================================================\033[0m";

  cd ../;
  echo -e " ===> Building Dockerfile: ${1} \n";
  DOCKERFILE="docker/build/${1}";
  docker build --file=${DOCKERFILE} -t ${PACKAGE_NAME}:${2} .
  docker tag ${PACKAGE_NAME}:${2} ${DOCKER_USER}/${PACKAGE_NAME}:${2}

  docker exec -i mysql mysql -uroot  <<< "create database IF NOT EXISTS omeka_test;"

  docker images
  
  echo -e "\033[00;32m ===> Spinning up a container running ${2} and attempting to run unit tests \033[0m\n";
  docker run -i -t --link mysql:mysql ${PACKAGE_NAME}:${2} /bin/sh -c "sed -i 's/^host.*/host = "mysql"/' application/test/config/database.ini && sed -i 's/^user.*/user = "root"/' application/test/config/database.ini && sed -i 's/^dbname.*/dbname = "omeka_test"/' application/test/config/database.ini &&./node_modules/gulp/bin/gulp.js test:php"

  docker exec -i mysql mysql -uroot  <<< "drop database omeka_test;"

  echo -e "\033[00;32m ===> Pushing to Dockerhub\033[0m\n";
  docker push ${DOCKER_USER}/${PACKAGE_NAME}:${2}
  cd -;
  echo -e "\033[00;32m====================S=U=C=C=E=S=S=======================\033[0m\n";
}

function startDB {

 docker run --name mysql -d -p 3306:3306 -e "MYSQL_ALLOW_EMPTY_PASSWORD=yes"  mysql:5.7
 until nc -z -v -w30 localhost 3306
 do
   echo "Waiting for database connection..."
   # wait for 5 seconds before check again
   sleep 5
 done

}

startDB



for php in "${PHP_VERSIONS[@]}"
do
  :
  for server in "${WEB_SERVERS[@]}"
  do
    :
    # Set up our docker ignore in the root for duration.
    cp ./.dockerignore ../.dockerignore

    DOCKER_TAG="${php}-${server}"
    BASE_TEMPLATE="./Dockerfile"
    PHP_TEMPLATE="./configs/php/${php}.sed"
    OUTFILE="${PACKAGE_NAME}-${php}-${server}.Dockerfile"
    sed -f ${PHP_TEMPLATE} ${BASE_TEMPLATE} > ./build/${OUTFILE}

    WEB_TEMPLATE="./configs/server/${server}.sed"
    sed -f ${WEB_TEMPLATE} -i.bak ./build/${OUTFILE}

    rm ./build/${OUTFILE}.bak

    buildDockerFile ${OUTFILE} ${DOCKER_TAG}

    # Remove ignore again.
    rm -f ../.dockerignore
  done
done

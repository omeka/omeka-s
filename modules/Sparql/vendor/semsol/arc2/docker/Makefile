default:
	make build
	make run
	docker exec -it --user arc2 docker_arc2_1 bash

build:
	docker-compose build

clean:
	-@docker ps -a -q | xargs docker stop
	-@docker ps -a -q | xargs docker rm
	-@docker volume ls -f dangling=true -q| xargs docker volume rm
	docker images --quiet --filter=dangling=true | xargs --no-run-if-empty docker rmi -f

run:
	docker-compose up --build -d --remove-orphans

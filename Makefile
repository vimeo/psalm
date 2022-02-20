WD:=/psalm
DIR:=$(shell dirname $(realpath $(firstword $(MAKEFILE_LIST))))
IM:=php:8.1-cli
composer:
	docker run --rm -it -v "${DIR}":"${WD}" -w "${WD}" composer  install
composer-shell:
	docker run --rm -it -v "${DIR}":"${WD}" -w "${WD}" composer bash
shell:
	docker run --rm -it -v "${DIR}":"${WD}" -w "${WD}" "${IM}"  bash
image:
	docker build --progress=plain . -t psalm
psalm:
	docker run --rm -it -v "${PWD}":/app -w /app psalm -c psalm.xml.dist
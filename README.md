Akeneo PIM Community Standard Edition
=====================================

Welcome to iFixit's install of Akeneo

Installation instructions
-------------------------

### Development Installation with Docker

## Requirements
 - Docker 19+
 - docker-compose >= 1.24
 - make

## Starting an empty Akeneo

```bash
$ cp .env.dev .env
$ make
$ make database-boostrap
$ docker-compose run -u www-data --rm php php bin/console pim:user:create
$ docker-compose run -u www-data --rm php php bin/console akeneo:elasticsearch:reset-indexes
```

The PIM will be available on http://localhost:8080/, with the credentials you
specified in the `pim:user:create` step from above.

To shutdown your PIM: `make down`

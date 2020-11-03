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
$ chown -R 1000 ./*
$ make
$ make bootstrap-database # DELETES ENTIRE DB, ONLY RUN ONCE!
```

The PIM will be available on http://localhost:8080/, with the credentials you
specified in the `boostrap-database` step from above.

To shutdown your PIM: `make down`

PWDSafe
=======
[![Build Status](https://travis-ci.org/PWDSafe/PWDSafe.svg?branch=master)](https://travis-ci.org/PWDSafe/PWDSafe)

Deploy with docker
------------------
You may deploy this application using docker or a container orchestrator like Kubernetes/OpenShift.
See latest image tags and documentation on [Docker Hub](https://hub.docker.com/r/pwdsafe/pwdsafe).

Prerequisite
-----------
* Webserver with support for PHP
* Access to a MySQL-database
* Composer

Installation
------------
* Run `composer install`
* Run `npm install && npm run prod`
* Copy .env.example to .env and modify it accordingly
* Run the database migrations with `php artisan migrate`
* Configure your webserver so it points to `public/`-folder. Make sure to redirect all requests where the file requested does not exist to index.php. Example configuration for nginx:
```Nginx
location / {
    try_files $uri $uri/ /index.php?$args;
}
```
* Browse to your site, register and login.
* Enjoy!

Upgrading from pre-Laravel
--------------------------
* Copy the `.env.example` to `.env` and modify it to point to your database.
* Run the following SQL in your database:
```SQL
create table migrations
(
	id int unsigned auto_increment
		primary key,
	migration varchar(255) not null,
	batch int not null
)
collate=utf8mb4_unicode_ci;

INSERT INTO migrations (id, migration, batch) VALUES (1, '2019_08_19_000000_create_failed_jobs_table', 1);
INSERT INTO migrations (id, migration, batch) VALUES (2, '2020_02_03_180732_create_credentials_table', 1);
INSERT INTO migrations (id, migration, batch) VALUES (3, '2020_02_03_180732_create_encryptedcredentials_table', 1);
INSERT INTO migrations (id, migration, batch) VALUES (4, '2020_02_03_180732_create_groups_table', 1);
INSERT INTO migrations (id, migration, batch) VALUES (5, '2020_02_03_180732_create_usergroups_table', 1);
INSERT INTO migrations (id, migration, batch) VALUES (6, '2020_02_03_180732_create_users_table', 1);
```
* Run any outstanding migrations by executing `php artisan migrate`

This application uses
---------------------
The following libraries/frameworks is used by the application:
- Laravel - https://laravel.com/
- Tailwind CSS - https://tailwindcss.com/


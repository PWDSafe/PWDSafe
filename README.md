PWDSafe
=======
[![Build Status](https://travis-ci.org/PWDSafe/PWDSafe.svg?branch=master)](https://travis-ci.org/PWDSafe/PWDSafe)

Deploy with docker
------------------
You may deploy this application using docker or a container orchestrator like Kubernetes/OpenShift.
See latest image tags and documentation on [Docker Hub](https://hub.docker.com/r/pwdsafe/pwdsafe).

Prerequisite
-----------
* Webserver with support for PHP 8.2 and modules:
  - ldap
  - openssl
  - json
  - mbstring
  - pdo_mysql
  - pdo_pgsql
* Access to a MySQL or PostgreSQL-database
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

Upgrading
--------------------------
* Pull down the latest tag from the repository
* Run `composer install`
* Run `npm install && npm run prod`
* Run any outstanding migrations by executing `php artisan migrate`

Extra configuration
-------------------
### LDAP
Authentication using Active Directory can be configured by setting `USE_LDAP` and the env-variables prefix with `AD_`.
`AD_USERCONTAINER` is used as a base for finding users in your AD. If your user is outside of the OU configured in `AD_USERCONTAINER`, they cannot login.

When using OpenLDAP, users must be directly in OU configured in `AD_USERCONTAINER`.

### Sentry
You can configure error tracking with Sentry by configuring the required env-variables. See `.env.example` for placeholders.


This application uses
---------------------
The following libraries/frameworks is used by the application:
- Laravel - https://laravel.com/
- Tailwind CSS - https://tailwindcss.com/
- Heroicons - https://heroicons.com/


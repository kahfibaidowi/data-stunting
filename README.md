# CARA INSTALL

## Requirements

PHP Version = 8.1

MySQL Version = 10.4.24-MariaDB - mariadb.org binary distribution

laravel/lumen-framework = 9.0

## Installation

Install package pendukung dengan composer

```bash
  composer install
```
    

## Ubah Variable .env

rename file .env.example menjadi .env dan ubah variable yg diperlukan

## Install Database

jalankan perintah di terminal

```bash
  php artisan migrate
```

jalankan seeder untuk user admin

```bash
  php artisan db:seed
```


## Jalankan

jalankan program dengan membuka __APP_URL/public. contoh http://localhost/stunting/public
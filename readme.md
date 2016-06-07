## Icecast Server Deploy to DigitalOcean - Test App

To start run the following commands:

```
git clone https://github.com/nikolalj/digital-ocean-test
cd digital-ocean-test
composer install
```

Rename your .env.example to .env file and generate the APP_Key:

```
php artisan key:generate
```

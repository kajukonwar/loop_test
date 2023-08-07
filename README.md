## Prerequisites
 - Make sure your php version is >= 8.1
 - Make sure all dependencies for laravel setup is fulfilled 

 A Dockerfile is also present in repo which can be used to setup a container

## How to setup?

Please do the following

 - Clone the repo
 - Run composer install command
 - Create .env file. Sample .env-example file is present in the repo. Please fill the values according to your setup
 - Run the command -- php artisan migrate:install
 - Run the command -- php artisan migrate

## Import data
 - Make sure the .env file has the necessary data filled
 - Run the command -- php artisan app:import-data . Follow the instructions shown in the terminal to import customer and product data

## Check the APIs
Make sure that Accept: application/json header is sent
Make sure that Content-type: application/json header is sent
There is no authentication required

  ## Create new order: 
   Sample curl attached below

    curl  -X POST \
    'http://localhost:8080/api/orders' \
    --header 'Accept: application/json' \
    --header 'Content-Type: application/json' \
    --data-raw '{
        "user_email":"Harvey_Thornton4640@hourpy.biz"
    }'

  ## View All orders
   Sample curl attached below

    curl  -X GET \
    'http://localhost:8080/api/orders' \
    --header 'Accept: application/json' \
    --header 'Content-Type: application/json'

  ## View a single order
    Sample curl attached below

    curl  -X GET \
    'http://localhost:8080/api/orders/1' \
    --header 'Accept: application/json' \
    --header 'Content-Type: application/json'

  ## Update an order
   
    Sample curl attached below

    curl  -X PUT \
    'http://localhost:8080/api/orders/1' \
    --header 'Accept: application/json' \
    --header 'Content-Type: application/json' \
    --data-raw '{
    "customer_id":2
    }'

  ## Add an product to order
    Sampple curl attached below

    curl  -X POST \
    'http://localhost:8080/api/orders/1/add' \
    --header 'Accept: application/json' \
    --header 'Content-Type: application/json' \
    --data-raw '{
    "product_id":2
    }'

  ##  Make payment
    Sample curl attached below

    curl  -X POST \
    'http://localhost:8080/api/orders/1/pay' \
    --header 'Accept: application/json' \
    --header 'Content-Type: application/json'

  ## Delete Order

    Sample curl attached below

     curl  -X DELETE \
    'http://localhost:8080/api/orders/1' \
    --header 'Accept: application/json' \
    --header 'Content-Type: application/json'
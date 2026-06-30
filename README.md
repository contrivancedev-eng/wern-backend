1. change app.baseURL in .env
2. change database config in .env
3. change baseURL in app/config/App.php
4. set constant values in Constants.php
5. change writable folder permission from terminal [DO NOT USE 775 or 777 FROM Filezila]
    sudo chown - R www-data:www-data writable/
6. add api routes in apiRoutes.php || web routes in webRoutes.php || admin routes in adminRoutes.php
7. 



Demo API
--- BASEURL/structure/api/test

Demo Web
--- BASEURL/structure/

Demo Admin
--- BASEURL/admin/dashboard
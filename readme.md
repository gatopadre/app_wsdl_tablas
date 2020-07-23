matronc
webdesa8

conectarse:
mysql -h localhost -u admdb -p

pass:
cast1301

create database wsdl_tablas character set utf8;


select * from tablas_parametros where limit 10;

SELECT tabla_path AS url FROM tablas_parametros WHERE tabla_path like "%ivr/Proveedores.parametros" LIMIT 1;

select id, tabla_path from tablas_parametros order by id desc limit 100;

select count(*) from tablas_parametros;


---
nohup python3.6 main.py

ps ax | grep "python3.6"
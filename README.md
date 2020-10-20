Epayco
=====
Exámen técnico

## Description

API para interacción con portal epayco carpeta ePayco e ingreso/registro de usuario carpeta epayFront

## Installation

### From GitHub

```bash
$ git clone https://github.com/dayan0101/epayco.git
```

## Uso

Modificar las siguientes variables para envío de email

```php
$kernel['mailer.smtp.server']   = ip_servidor;
$kernel['mailer.smtp.port']     = puerto;
$kernel['mailer.smtp.user']     = cuenta_correo;
$kernel['mailer.smtp.password'] = clave_correo;
```
Modificar los siguientes parámetros en ConnectionManager

```php
$cnx =  new ePayPdo(host, database,usuario,clave);
```
Para usar la Api ir a la siguiente documentacion

```swagger
https://app.swaggerhub.com/apis/dayan0101/ePayco/1.0.0
```
## Adicionales

Se crea funcion sms donde se envían mensajes de texto usando la Api de infobip se debe añadir la api key en la autenticación para hacer uso

Los diagramas de la aplicación se encuentran en la carpeta doc.


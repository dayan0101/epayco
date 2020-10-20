<?php
date_default_timezone_set('America/Bogota');

include dirname(__DIR__) . '/kernel/src/boot.php';

use Monolog\Logger;

$kernel = \Kernel::getInstance();

$kernel->cargarClases([
    'ePayco\\ePaycoPortal\\Controlador\\' => __DIR__ . '/Controlador',
    'ePayco\\ePaycoPortal\\Modelo\\' => __DIR__ . '/Modelo',
]);

$kernel['mailer.smtp.server']   = '192.168.0.5';
$kernel['mailer.smtp.port']     = 25;
$kernel['mailer.smtp.user']     = 'no-reply@ePay.co';
$kernel['mailer.smtp.password'] = 'D3s4rr0ll02020*';

$kernel['infobip.url'] = 'api.infobip.com';

$devFile = __DIR__ . '/dev.php';
if (file_exists($devFile)) {
    include $devFile;
}


$kernel->restMode();

$kernel->setControladorDefecto('/Portal/notFound');
$kernel->setNombreProyecto('ePaycoPortal');
$kernel->agregarDirPlantilla('tpl', __DIR__ . '/Vista');

$kernel->iniciar();

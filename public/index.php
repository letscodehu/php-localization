<?php
declare(strict_types=1);

session_start();
error_reporting(E_ALL);
ini_set("display_errors", "1");

require_once "../core/functions.php";
require_once "../vendor/autoload.php";

$locale= "hu_HU.utf8";

if (defined("LC_MESSAGES")) {
    setlocale(LC_MESSAGES, $locale);
    bindtextdomain("messages", "../i18n");
} else {
    putenv("LC_ALL={$locale}");
    bindtextdomain("messages", "..\i18n");
}

(new \Application(new ServiceContainer(include "../services.php")))->start(realpath(__DIR__. "/../"));


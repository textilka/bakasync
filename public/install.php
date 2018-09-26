<?php

$ob_out = "";
$continue = true;
$nogit = false;
$nocomposer = false;
$originuri = "https://github.com/textilka/bakasync";
$git_update = "git fetch --all && git reset --hard origin/master";

$requiredExtensions = ["openssl", "ldap", "pdo_sqlite", "sqlsrv", "pdo_sqlsrv"];

write("<b>Spouštím neinteraktivní instalační script BakaSync</b>");
write("Spouštím kontrolu prostředí");

write("Kontrola verze PHP >= 7.0.0");
write(phpversion(), "info");
if (substr(phpversion(), 0, 1) != "7") {
    write("Používáte nepodporovanou verzi PHP. Končím.", "danger");
    $continue = false;
}
ifterm();
write("PHP verze v pořádku, pokračuji");

write("Kontrola rozšíření");
foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        write("Rozšíření <b>" . $ext . "</b> nenalezeno", "danger");
        $continue = false;
    }
}

ifterm();
write("Všechna potřebná rozšíření nalezena, pokračuji");

write("Kontrola <b>exec</b> funkce");
if(!function_exists('exec')) {
    write("Funkce exec není povolena. Služby git deploy a composer auto-install nebudou k dispozici", "warn");
    $nogit = true;
    $nocomposer = true;
} else {
    write("Funkce exec povolena");
    write("Kontrola composer");
    if (substr(exec("composer --version"), 0, 8) != "Composer") {
        write("Composer nenalezen (nebo není v PATH). Instaluji..", "info");
        chdir("../");
        copy('https://getcomposer.org/installer', 'composer-setup.php');
        $composer_hash = file_get_contents("https://composer.github.io/installer.sig");
        if (hash_file('SHA384', 'composer-setup.php') === $composer_hash) {
            if (write_exec("php composer-setup.php") !== 0) {
                write("Instalace composer selhala, přeskakuji", "danger");
                $nocomposer = true;
            }
            unlink("composer-setup.php");
        }
        chdir("public");
    }
    ifterm();
    $nocomposer ? null : write("Composer nainstalován, pokračuji");
    write("Konstrola git");
    if (substr(exec("git --version"), 0, 3) != "git") {
        write("Git nenalezen (nebo není v PATH). Služba git deploy nebude k dispozici. Pokračuji", "warn");
        $nogit = true;
    } else {
        write("Git nainstalován, pokračuji");
    }
}
ifterm();
write("Kontrola prostředí dokončena");

if (!$nogit) {
    write("Kontroluji aktualizace");
    if(is_dir(__DIR__ . "/../.git")) {
        write("Jsme v git repu, kontroluji origin");
        if (write_exec("git remote get-url origin", null, $origin) !== 0) {
            write("Kontrola origin selhala, přeskakuji");
            goto endgit;
        }
        if ($origin[0] == $originuri || $origin[0] == $originuri . ".git") {
            write("Používáme oficiální repo, aktualizuji");
            if (write_exec($git_update) !== 0) {
                write("Aktualizace selhala, přeskakuji", $warn);
                goto endgit;
            }
        } else if (substr($origin[0], 0, 5) == "fatal") {
            write("Origin nenalezen, nastavuji", "info");
            if (write_exec("git remote add origin $originuri")) {
                write("Přidání origin selhalo, přeskakuji", $warn);
                goto endgit;
            }
            write("Aktualizuji");
            if (write_exec($git_update) !== 0) {
                write("Aktualizace selhala, přeskakuji", $warn);
                goto endgit;
            }
        } else {
            write("Používáme neoficiální repo, přeskakuji aktualizaci", "warn");
        }
    } else {
        write("Nejsme v repu, zakládám");
        chdir("../");
        if (write_exec("git init") !== 0) {
            write("Vytvoření repa selhalo, přeskakuji", $warn);
            chdir("public");
            goto endgit;
        }
        chdir("public");
        write("nastavuji origin", "info");
        if (write_exec("git remote add origin $originuri") !== 0) {
            write("Přidání origin selhalo, přeskakuji", $warn);
            goto endgit;
        }
        write("aktualizuji");
        if (write_exec($git_update) !== 0) {
            write("Aktualizace selhala, přeskakuji", $warn);
            goto endgit;
        }
    }
    ifterm();
    write("Aktualizace dokončena, pokračuji");
}
endgit:

if (!$nocomposer) {
    write("Instaluji závislosti");
    chdir("../");
    if (is_file(__DIR__ . "/composer.phar")) {
        write("Používám lokální composer");
        $composer_path = "php composer.phar";
    } else {
        write("Používám globální composer");
        $composer_path = "composer";
    }
    if (write_exec($composer_path . " install") !== 0) {
        write("Instalace závislostí selhala. Končím.", "danger");
        $continue = false;
    }
    chdir("public");
} else {
    if (is_dir(__DIR__ . "/../vendor")) {
        write("Nalezena složka vendor, předpokládám že závislosti byly manuálně nainstalovány");
    } else {
        write("Závislosti nelze nainstalovat. Končím.");
        $continue = false;
    }
}
ifterm();
write("Závislosti nainstalovány, pokračuji");

write("Odhaduji nastavení mod_rewrite");
$uri = $_SERVER['REQUEST_URI'];
$self = $_SERVER['PHP_SELF'];
if ($uri == $self) {
    write("Mod_rewrite patrně neaktivní. Doporučuji nasměrovat root serveru do složky /public", "warn");
    $urlbase = "";
} else {
    write("Mod_rewrite patrně aktivní. Odhaduji cestu");
    $urlbase = substr($uri, 0, strpos($uri, $self));
    write("Cesta odhadnuta na <info>$urlbase</info>, pokračuji");
}

write("Nastavuji databázi");
if (is_dir(__DIR__ . "/../db")) {
    write("Složka /db existuje, zachovávám stávající konfiguraci", "warn");
} else {
    write("Vytvářím databázi");
    mkdir(__DIR__ . "/../db");
    write("Připojuji se k databázi");
    try {
        $db = new PDO("sqlite:../db/config.db");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        write("Vytvářím tabulky");
        $db->exec("CREATE TABLE IF NOT EXISTS conf (id INTEGER PRIMARY KEY AUTOINCREMENT, field TEXT NOT NULL, val TEXT NOT NULL);");
        write("Osazuji tabulky");
        $db->exec("INSERT INTO conf (id, field, val) VALUES(1, '/url/app_base', '$urlbase');");

    } catch (PDOException $e) {
        write($e->getMessage(), "danger");
        write("Chyba databáze, Končím.");
        $continue = false;
    }
}
ifterm();
write("Nastavení databáze dokončeno, pokračuji");

$move_to = __DIR__ . "/../src";
write("Instalace dokončena, testuji nastavení");
if (array_key_exists('REDIRECT_BASE', $_SERVER))
    $linkbase = $_SERVER['REDIRECT_BASE'];
else if (array_key_exists('BASE', $_SERVER))
    $linkbase = $_SERVER['BASE'];
else 
    $linkbase = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], "install.php"));

$link = "http://" . $_SERVER['HTTP_HOST'] . $linkbase;
if (@file_get_contents("$link/test") == "OK") {
    write("Test nastavení byl úspěšný");
    write("Přesouvám instalační script do " . realpath($move_to) . "/install.php");
    rename(__FILE__, realpath($move_to) . "/install.php");
    write("<a href='$link/setup'>Pokračovat k nastavení</a>");
} else {
    write("Test nastavení nebyl úspěšný.", "danger");
    write("Server <b>$link/test" . "</b> odpověděl");
    write($http_response_header[0]);
    write("Manuálně nastavte URLBASE v /public/index.php");
}

$continue = false;
ifterm();

function write_exec($command, $sev = null, &$output = null) {
    write("$ " . $command, "code");
    exec($command, $output, $status);
    foreach ($output as $line) {
        write($line, $sev);
    }
    return $status;
}

function write($message = "", $surr = null) {
    global $ob_out;
    $ob_out .= "<time>" . date("y-m-d H:i:s") . "&gt;</time> " . (is_null($surr)?"":"<$surr>") . $message . (is_null($surr)?"":"</$surr>") . "<br />" . PHP_EOL;
}

function ifterm() {
    global $continue, $ob_out;
    if ($continue) return;
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>BakaSync Install script</title>
    <style>
    body {
        background-color: #000;
        color: #fff;
        font-family: monospace;
    }
    danger {color: #f00;}
    info {color: #26c6da;}
    time {color: #aaa;}
    warn {color: #ff0;}
    code {color: #0a0;}
    </style>
</head>
<body>
$ob_out
</body>
</html>
HTML;
    exit;
}

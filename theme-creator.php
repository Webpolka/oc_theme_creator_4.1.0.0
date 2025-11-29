<?php

if ($argc < 3) {
    die("
    Usage: php ./theme-creator.php [name] [out_dir|out_file] [-a author] [-u url] [-v version]
            name     - The name of the theme.
            out_dir  - The path to the system/storage/marketplace directory or another one if you wish.
            out_file - The path to the archived \${theme_name}.ocmod.zip file if ends up with .zip.
            author   - Name of the theme's author, default is \"Weblegko\".
            url      - Link to the website of the theme's author, default is https://Weblegko.ru/.
            version  - The version, default is 1.0.
    ");
}

$name = $argv[1];
$output = $argv[2];
$author_name = "Weblegko";
$author_link = "https://weblegko.ru/";
$version = "1.0";

for ($i = 3; $i < $argc; $i++) {
    switch ($argv[$i]) {
        case '-a':
            $author_name = $argv[++$i];
            break;
        case '-u':
            $author_link = $argv[++$i];
            break;
        case '-v':
            $version = $argv[++$i];
            break;
    }
}

$theme_name = 'oc_' . preg_replace('/^oc[\_]*/i', '', $name);
$Name = ucwords($name);
$ThemeName = str_replace('_', '', ucwords($theme_name, '_'));

$templateDir = __DIR__ . '/files';
$tmpDir = sys_get_temp_dir() . '/' . uniqid('oc_', true);

mkdir($tmpDir);
recurseCopy($templateDir, $tmpDir);
replacePlaceholders($tmpDir, $name, $Name, $theme_name, $ThemeName, $version, $author_name, $author_link);

$isOutputFile = substr($output, -4) === '.zip';
$zipFile = $isOutputFile ? $output : ($output . '/' . $theme_name . '.ocmod.zip');
archiveTheme($tmpDir, $zipFile, $theme_name);

deleteDirectory($tmpDir);
echo "Theme created successfully: $zipFile\n";

function recurseCopy($src, $dst) {
    $dir = opendir($src);
    if (!is_dir($dst)) mkdir($dst);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            $filePathSrc = $src . '/' . $file;
            $filePathDst = $dst . '/' . str_replace('%name%', $GLOBALS['name'], $file);
            if (is_dir($filePathSrc)) {
                recurseCopy($filePathSrc, $filePathDst);
            } else {
                copy($filePathSrc, $filePathDst);
            }
        }
    }
    closedir($dir);
}

function replacePlaceholders($dir, $name, $Name, $theme_name, $ThemeName, $version, $author_name, $author_link) {
    $fields = ['%name%', '%Name%', '%theme_name%', '%ThemeName%', '%version%', '%author_name%', '%author_link%'];
    $values = [$name, $Name, $theme_name, $ThemeName, $version, $author_name, $author_link];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $originalFile = $file->getPathname();
            $destFile = str_replace($fields, $values, $originalFile);
            $content = file_get_contents($originalFile);
            $content = str_replace($fields, $values, $content);
            file_put_contents($destFile, $content);
            if ($originalFile !== $destFile) {
                unlink($originalFile);
            }
        }
    }
}

function archiveTheme($src, $dst, $theme_name) {
    $src = realpath($src);
    $zip = new ZipArchive();
    $zip->open($dst, ZipArchive::CREATE);
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src), RecursiveIteratorIterator::LEAVES_ONLY);
    echo "Zipping $dst:\n";
    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($src) + 1);
            echo "  > $relativePath\n";
            $zip->addFile($filePath, $relativePath);
        }
    }
    $zip->close();
}

function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    return rmdir($dir);
}

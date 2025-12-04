<?php

$projectRoot = __DIR__ . '/../../';
$stubFilename='exports/ide-stubs-nyanorm.php';
$stubFile    = $projectRoot . $stubFilename;

$classes = array();


$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($projectRoot));

foreach ($rii as $file) {
    if ($file->isDir()) continue;
    if (substr($file->getFilename(), -4) !== '.php') continue;
    $content = file_get_contents($file->getPathname());
    if (preg_match_all('/new\s+(nya_[a-zA-Z0-9_]+)/', $content, $matches)) {
        foreach ($matches[1] as $className) {
            $classes[$className] = $file->getFilename();
        }
    }
}

$out = '<?php' . PHP_EOL;

foreach ($classes as $className => $fileName) {
    $out .= 'class '.$className.' extends NyanORM {}' . PHP_EOL;
    print($fileName.': '.$className . PHP_EOL);
}


file_put_contents($stubFile, $out);

print('Generated: ' . $stubFilename . PHP_EOL);
print('Classes found: ' . count($classes) . PHP_EOL);

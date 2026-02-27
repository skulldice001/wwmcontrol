<?php
$file = __DIR__ . '/public/assets/guild_war/images/map.png';
if (file_exists($file)) {
    $size = getimagesize($file);
    echo "SIZE: " . $size[0] . "x" . $size[1];
} else {
    echo "Not found: $file";
}

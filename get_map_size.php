<?php
$file = 'public/assets/guild_war/images/map.png';
if (file_exists($file)) {
    $info = getimagesize($file);
    echo json_encode(['width' => $info[0], 'height' => $info[1]]);
} else {
    echo "File not found";
}

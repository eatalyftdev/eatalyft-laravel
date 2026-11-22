<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=eatalyft_local', 'root', '');
foreach(['cities','amenities','bus_classes','branches','buses','bus_documents','maintenance_logs','crew','seat_layouts','seat_layout_cells'] as $t) {
    $pdo->exec("DROP TABLE IF EXISTS `$t`");
}
echo "done\n";

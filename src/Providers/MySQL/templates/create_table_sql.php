CREATE TABLE `<?=$tableName?>` (
<?php foreach ($rows as $i => $row): ?>
        <?=$row?><?=($i < (count($rows) - 1) ? ',' : '')?>

<?php endforeach ?>
    ) ENGINE=<?=$engine?> COLLATE=<?=$collate?>;
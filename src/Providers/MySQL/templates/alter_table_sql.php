ALTER TABLE `<?=$tableName?>`
<?php foreach ($columns as $i => $column): ?>
        <?=$column?><?=($i < (count($columns) - 1) ? ",\n" : ';')?>
<?php endforeach ?>
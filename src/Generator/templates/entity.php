<?= $phpOpener ?? '' ?>


namespace <?= $entityNamespace ?? '' ?>;

<?php if (!empty($useTableAttributes)) : ?>
use Composite\DB\Attributes\{<?= implode(', ', $useTableAttributes) ?>};
<?php endif; ?>
<?php if (!empty($useSyncAttributes)) : ?>
use Composite\Sync\Attributes\{<?= implode(', ', $useSyncAttributes) ?>};
<?php endif; ?>
<?php foreach($useNamespaces ?? [] as $namespace) : ?>
use <?=$namespace?>;
<?php endforeach; ?>

#[Table(connection: '<?= $connectionName ?? '' ?>', name: '<?= $tableName ?? '' ?>')]
<?php foreach($indexes ?? [] as $index) : ?>
<?=$index?>

<?php endforeach; ?>
class <?=$entityClassShortname??''?> extends AbstractEntity
{
<?php foreach($traits ?? [] as $trait) : ?>
    use <?= $trait ?>;

<?php endforeach; ?>
<?php foreach($properties ?? [] as $property) : ?>
<?php foreach($property['attributes'] as $attribute) : ?>
    <?= $attribute ?>

<?php endforeach; ?>
    <?= $property['var'] ?>;

<?php endforeach; ?>
    public function __construct(
<?php foreach($constructorParams ?? [] as $param) : ?>
<?php foreach($param['attributes'] as $attribute) : ?>
        <?= $attribute ?>

<?php endforeach; ?>
        <?= $param['var'] ?>,
<?php endforeach; ?>
    ) {}
}

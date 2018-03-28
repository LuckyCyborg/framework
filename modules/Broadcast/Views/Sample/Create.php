<section class="content-header">
    <h1><?= __d('broadcast', 'Broadcasting Tests'); ?></h1>
    <ol class="breadcrumb">
        <li><a href="<?= site_url('dashboard'); ?>"><i class="fa fa-dashboard"></i> <?= __d('broadcast', 'Dashboard'); ?></a></li>
        <li><?= __d('broadcast', 'Broadcasting Tests'); ?></li>
    </ol>
</section>

<!-- Main content -->
<section class="content">

<?= View::fetch('Partials/Messages'); ?>

<div class="box box-default">
    <div class="box-header with-border">
        <h3 class="box-title"><?= __d('broadcast', 'Tests'); ?></h3>
    </div>
    <div class="box-body">
        <a class="btn btn-success col-sm-2 pull-right" href="#"><?= __d('broadcast', 'Send a new Push'); ?></a>
    </div>
</div>

</section>

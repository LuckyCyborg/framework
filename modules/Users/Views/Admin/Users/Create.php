<section class="content-header">
    <h1><?= __d('users', 'Create User'); ?></h1>
    <ol class="breadcrumb">
        <li><a href="<?= site_url('admin/dashboard'); ?>"><i class="fa fa-dashboard"></i> <?= __d('users', 'Dashboard'); ?></a></li>
        <li><a href="<?= site_url('admin/users'); ?>"><?= __d('users', 'Users'); ?></a></li>
        <li><?= __d('users', 'Create User'); ?></li>
    </ol>
</section>

<!-- Main content -->
<section class="content">

<?= View::fetch('Partials/Messages'); ?>

<form class="form-horizontal" action="<?= site_url('admin/users'); ?>" method='POST' enctype="multipart/form-data" role="form">

<div class="box box-default">
    <div class="box-header with-border">
        <h3 class="box-title"><?= __d('users', 'Create a new User Account'); ?></h3>
    </div>
    <div class="box-body">
        <div class="col-md-8 col-md-offset-2 col-sm-8 col-sm-offset-2">
            <h4><?= __d('users', 'Account'); ?></h4>
            <hr>

            <div class="form-group">
                <label class="col-sm-4 control-label" for="username"><?= __d('users', 'Username'); ?> <font color="#CC0000">*</font></label>
                <div class="col-sm-8">
                    <input name="username" id="username" type="text" class="form-control" value="<?= Input::old('username'); ?>" placeholder="<?= __d('users', 'Username'); ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-4 control-label" for="password"><?= __d('users', 'Password'); ?> <font color="#CC0000">*</font></label>
                <div class="col-sm-8">
                    <input name="password" id="password" type="password" class="form-control" value="" placeholder="<?= __d('users', 'Password'); ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-4 control-label" for="password_confirmation"><?= __d('users', 'Confirm Password'); ?> <font color="#CC0000">*</font></label>
                <div class="col-sm-8">
                    <input name="password_confirmation" id="password_confirmation" type="password" class="form-control" value="" placeholder="<?= __d('users', 'Password confirmation'); ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-4 control-label" for="role"><?= __d('users','Roles'); ?> <font color="#CC0000">*</font></label>
                <div class="col-sm-8">
                    <?php $optRoles = Input::old('roles', array()); ?>
                    <select name="roles[]" id="roles" class="form-control select2" multiple="multiple" data-placeholder="<?= __d('users', 'Select a Role'); ?>" style="width: 100%;">
                        <?php foreach ($roles as $role) { ?>
                        <option value="<?= $role->id ?>" <?= in_array($role->id, $optRoles) ? 'selected' : ''; ?>><?= $role->name; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-4 control-label" for="first_name"><?= __d('users', 'First Name'); ?> <font color="#CC0000">*</font></label>
                <div class="col-sm-8">
                    <input name="first_name" id="first-name" type="text" class="form-control" value="<?= Input::old('first_name'); ?>" placeholder="<?= __d('users', 'First Name'); ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-4 control-label" for="last_name"><?= __d('users', 'First Name'); ?> <font color="#CC0000">*</font></label>
                <div class="col-sm-8">
                    <input name="last_name" id="last-name" type="text" class="form-control" value="<?= Input::old('last_name'); ?>" placeholder="<?= __d('users', 'First Name'); ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-4 control-label" for="email"><?= __d('users', 'E-mail'); ?> <font color="#CC0000">*</font></label>
                <div class="col-sm-8">
                    <input name="email" id="email" type="text" class="form-control" value="<?= Input::old('email'); ?>" placeholder="<?= __d('users', 'E-mail'); ?>">
                </div>
            </div>
            <div class="clearfix"></div>
            <h4><?= __d('users', 'Profile'); ?></h4>
            <hr>

            <?php foreach ($items as $item) { ?>
            <?php $type = $item->type; ?>
            <?php $name = str_replace('-', '_', $item->name); ?>
            <?php $id  = str_replace('_', '-', $item->name); ?>
            <?php $required = Str::contains($item->rules, 'required'); ?>
            <?php $options = $item->options ?: array(); ?>
            <?php $placeholder = array_get($options, 'placeholder') ?: $item->title; ?>

            <div class="form-group">
                <label class="col-sm-4 control-label" for="<?= $name; ?>">
                    <?= $item->title; ?>
                    <?php if ($required) { ?>
                    <span class="text-danger" title="<?= __d('contacts', 'Required field'); ?>">*</span>
                    <?php } ?>
                </label>
                <div class="col-sm-8">

                <?php if ($type == 'text') { ?>
                    <input type="text" class="form-control" name="<?= $name; ?>" id="<?= $id; ?>" value="<?= Input::old($name, array_get($options, 'default')); ?>" placeholder="<?= $placeholder; ?>" />
                <?php } else if ($type == 'password') { ?>
                    <input type="password" class="form-control" name="<?= $name; ?>" id="<?= $id; ?>" value="<?= Input::old($name); ?>" placeholder="<?= $placeholder; ?>" />
                <?php } else if ($type == 'textarea') { ?>
                    <textarea name="<?= $name; ?>" id="<?= $id; ?>" rows="<?= array_get($options, 'rows', 10); ?>" class="form-control" style="resize: none;" placeholder="<?= $placeholder; ?>"><?= Input::old($name, array_get($options, 'default')); ?></textarea>
                <?php } else if ($type == 'select') { ?>
                    <select name="<?= $name; ?>" id="<?= $id; ?>" placeholder="" data-placeholder="<?= array_get($options, 'placeholder') ?: __d('requests', '- Choose an option -'); ?>" class="form-control select2">
                        <option></option>
                        <?php $selected = Input::old($name, array_get($options, 'default')); ?>
                        <?php $choices = explode("\n", trim(array_get($options, 'choices'))); ?>
                        <?php foreach($choices as $choice) { ?>
                        <?php list ($value, $label) = explode(':', trim($choice)); ?>
                        <option value="<?= $value = trim($value); ?>" <?= ($value == $selected) ? 'selected="selected"' : ''; ?>><?= trim($label); ?></option>
                        <?php } ?>
                    </select>
                <?php } else if ($type == 'checkbox') { ?>
                    <?php $choices = explode("\n", trim(array_get($options, 'choices'))); ?>
                    <?php $multiple = (count($choices) > 1); ?>
                    <?php $checked = (array) Input::old($name); ?>
                    <?php foreach($choices as $choice) { ?>
                    <?php list ($value, $label) = explode(':', trim($choice)); ?>
                    <?php $checkId = $id .'-' .str_replace('_', '-', $value = trim($value)); ?>
                    <div class="checkbox icheck-primary">
                        <input type="checkbox" name="<?= $name; ?><?= $multiple ? '[]' : ''; ?>" id="<?= $checkId; ?>" value="<?= $value; ?>" <?= in_array($value, $checked) ? 'checked' : ''; ?>> <label for="<?= $checkId; ?>"><?= trim($label); ?></label>
                    </div>
                    <div class="clearfix"></div>
                    <?php } ?>
                <?php } else if ($type == 'radio') { ?>
                    <?php $checked = Input::old($name); ?>
                    <?php $choices = explode("\n", trim(array_get($options, 'choices'))); ?>
                    <?php foreach($choices as $choice) { ?>
                    <?php list ($value, $label) = explode(':', trim($choice)); ?>
                    <?php $checkId = $id .'-' .str_replace('_', '-', $value = trim($value)); ?>
                    <div class="radio icheck-primary">
                        <input type="radio" name="<?= $name; ?>" id="<?= $checkId; ?>" value="<?= $value; ?>" <?= ($value == $checked) ? 'checked' : ''; ?>> <label for="<?= $checkId; ?>"><?= trim($label); ?></label>
                    </div>
                    <div class="clearfix"></div>
                    <?php } ?>
                <?php } ?>

                </div>
            </div>

            <?php } ?>

            <div class="clearfix"></div>
            <br>
            <font color="#CC0000">*</font><?= __d('users', 'Required field'); ?>
            <hr>
            <div class="form-group">
                <div class="col-sm-12">
                    <input type="submit" name="submit" class="btn btn-success col-sm-3 pull-right" value="<?= __d('users', 'Save'); ?>">
                </div>
            </div>
        </div>
    </div>
</div>

<?= csrf_field(); ?>

</form>

<a class="btn btn-primary col-sm-2" href="<?= site_url('admin/users'); ?>"><?= __d('users', '<< Previous Page'); ?></a>

<div class="clearfix"></div>
<br>

</section>

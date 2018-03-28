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
        <p id="content" class="text-center"></p>
        <p id="status" class="text-muted"></p>
    </div>
</div>

<script src='https://cdnjs.cloudflare.com/ajax/libs/socket.io/2.0.4/socket.io.js'></script>

</section>

<script>

$(document).ready(function () {
    var channel = 'private-Modules.Users.Models.User.<?= Auth::id(); ?>';

    // The connection server.
    var socket = io('<?= site_url(); ?>' + ':2120');

    // Login after connecting.
    socket.on('connect', function () {
        $('#content') .html('Connected, socket_id：' + socket.id);

        $.ajax({
            url: "<?= site_url('broadcasting/auth'); ?>",
            type: "POST",
            headers: {
                '_token': '<?= csrf_token(); ?>',
                'X-Socket-ID': socket.id
            },
            data: {
                channel_name: channel,
                socket_id: socket.id
            },
            dataType: 'json',
            success: function (signature) {
                socket.emit('authenticate', channel, signature);
            },
            error: function(xhr, ajaxOptions, thrownError) {
                socket.disconnect();
            },
            timeout : 15000 // Timeout of the ajax call.
        });
    });

    // When the back-end pushes messages ...
    socket.on('message', function (message) {
         $('#content') .html('Received the notification：' + message);
    });
});

</script>


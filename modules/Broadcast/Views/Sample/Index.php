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

function socket_subscribe(socket, channel) {
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
        timeout : 15000,

        success: function (data) {
            if (typeof data.payload !== 'undefined') {
                socket.emit('subscribe', channel, data.auth, data.payload);
            } else {
                socket.emit('subscribe', channel, data.auth);
            }
        },
        error: function (xhr, ajaxOptions, thrownError) {
            socket.disconnect();
        }
    });
};

function socket_unsubscribe(socket, channel) {
    socket.emit('unsubscribe', channel);
};


$(document).ready(function () {
    var channel = 'presence-chat';

    // The connection server.
    var socket = io('<?= site_url(); ?>' + ':2120');

    // Login after connecting.
    socket.on('connect', function () {
        socket_subscribe(socket, channel);
    });

    socket.on('presence:subscribed', function (channel, members) {
        console.log('subscribed to channel: ' + channel);
        console.log(members);

        $('#content') .html('Subscribed to channel：<b>' + channel + '</b>, socketId: <b>' + socket.id + '</b>');
    });

    socket.on('presence:joining', function (channel, member) {
        console.log('joining the channel: ' + channel);
        console.log(member);
    });

    socket.on('presence:leaving', function (channel) {
        console.log('leaving the channel: ' + channel);
    });

    socket.on('disconnected', function () {
        console.log('disconnected');

        $('#content') .html('Disconnected, socket_id：' + socket.id);
    });

    // When the back-end pushes messages ...
    socket.on('message', function (message) {
         $('#content') .html('Received the notification：' + message);
    });
});

</script>


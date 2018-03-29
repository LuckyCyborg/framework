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
        <p id="content"></p>
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
    var userChannel = 'private-Modules.Users.Models.User.<?= Auth::id(); ?>';

    var chatChannel = 'presence-chat';

    // The connection server.
    var socket = io('<?= site_url(); ?>' + ':2120');

    (function () {
        var emit = socket.emit,
            onevent = socket.onevent;

        socket.emit = function () {
            console.log('***', 'emit', Array.prototype.slice.call(arguments));
            emit.apply(socket, arguments);
        };
        socket.onevent = function (packet) {
            console.log('***', 'on', Array.prototype.slice.call(packet.data || []));
            onevent.apply(socket, arguments);
        };
    }());

    // Login after connecting.
    socket.on('connect', function () {
        socket_subscribe(socket, userChannel);
        socket_subscribe(socket, chatChannel);
    });

    socket.on('private:subscribed', function (channel) {
        console.log('subscribed to private channel: ' + channel);

        $('#content') .append('<p>Subscribed to private channel：<b>' + channel + '</b></p>');
    });

    socket.on('presence:subscribed', function (channel, members) {
        console.log('subscribed to presence channel: ' + channel);
        console.log(members);

        $('#content') .append('<p>Subscribed to presence channel：<b>' + channel + '</b></p>');
    });

    socket.on('channel:joining', function (channel) {
        console.log('joining the channel: ' + channel);

        $('#content') .append('<p>Joining to channel：<b>' + channel + '</b></p>');
    });

    socket.on('presence:joining', function (channel, member) {
        console.log('joining the presence channel: ' + channel);
        console.log(member);
    });

    socket.on('presence:leaving', function (channel) {
        console.log('leaving the presence channel: ' + channel);
    });

    socket.on('disconnected', function () {
        console.log('disconnected');

        $('#content') .html('Disconnected, socket_id：' + socket.id);
    });

    // When the back-end pushes messages ...
    socket.on('message', function (message) {
         $('#content') .html('Received the notification：' + message);
    });

    socket.on('Modules.Broadcast.Events.Sample', function (data) {
        console.log('Received event: Modules.Broadcast.Events.Sample');
        console.log(data);

        $('#content') .append('<p>Received event: <b>Modules.Broadcast.Events.Sample</b></p>');
    });
});

</script>


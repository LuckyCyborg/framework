<?php

use Nova\Database\Schema\Blueprint;
use Nova\Database\Migrations\Migration;

use Modules\Platform\Database\ManagePermissionsTrait;


class MessagesUpdatePermissionsTable extends Migration
{
    use ManagePermissionsTrait;


    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->deletePermissions('messages');
    }
}
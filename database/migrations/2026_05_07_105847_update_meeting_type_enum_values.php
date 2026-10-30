<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // First, update any existing data to match new enum values
        DB::table('meetings')->where('meeting_type', 'online')->update(['meeting_type' => 'virtual']);
        DB::table('meetings')->where('meeting_type', 'offline')->update(['meeting_type' => 'in-person']);
        DB::table('meetings')->where('meeting_type', 'phone')->update(['meeting_type' => 'telephonic']);
        DB::table('meetings')->where('meeting_type', 'video')->update(['meeting_type' => 'virtual']);
        
        // Alter the column to use new enum values
        DB::statement("ALTER TABLE `meetings` MODIFY COLUMN `meeting_type` ENUM('virtual', 'in-person', 'telephonic') NULL");
    }

    public function down()
    {
        // Revert back to old enum values
        DB::statement("ALTER TABLE `meetings` MODIFY COLUMN `meeting_type` ENUM('online', 'offline', 'phone', 'video') NULL");
        
        // Revert data
        DB::table('meetings')->where('meeting_type', 'virtual')->update(['meeting_type' => 'online']);
        DB::table('meetings')->where('meeting_type', 'in-person')->update(['meeting_type' => 'offline']);
        DB::table('meetings')->where('meeting_type', 'telephonic')->update(['meeting_type' => 'phone']);
    }
};

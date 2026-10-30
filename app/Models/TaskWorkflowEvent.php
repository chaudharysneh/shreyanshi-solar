<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TaskWorkflowEvent extends Model {
    protected $guarded = ['id'];
    protected $casts = ['attachments'=>'array','check_in_at'=>'datetime','check_out_at'=>'datetime'];
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function task() { return $this->belongsTo(Task::class); }
}

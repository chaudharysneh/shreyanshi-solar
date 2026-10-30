<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('dashboard_document_versions',function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->foreignId('user_id')->constrained();
            $table->string('filename',180);
            $table->json('fields');
            $table->string('aadhaar_path')->nullable();
            $table->timestamps();
            $table->unique(['dashboard_document_id','version']);
        });
        DB::table('dashboard_documents')->orderBy('id')->chunkById(100,function ($documents) {
            foreach ($documents as $document) DB::table('dashboard_document_versions')->insert([
                'dashboard_document_id'=>$document->id,'version'=>1,'user_id'=>$document->user_id,
                'filename'=>$document->filename,'fields'=>$document->fields,'aadhaar_path'=>$document->aadhaar_path,
                'created_at'=>$document->updated_at,'updated_at'=>$document->updated_at,
            ]);
        });
    }
    public function down(): void { Schema::dropIfExists('dashboard_document_versions'); }
};

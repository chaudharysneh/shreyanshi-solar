<div class="col-12">
    <div class="row g-4">
        @include('crm.leads.partials.source-master', ['lead'=>$customer ?? null,'sources'=>\App\Models\LeadSource::orderBy('name')->get(),'sourceSideBySide'=>true])
    </div>
</div>

@if (trim((string) ($estimate->terms_conditions ?? '')) !== '')
    <section style="page-break-before: always; padding: 24px; font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.6;">
        <h2 style="font-size: 18px; margin-bottom: 16px;">Terms &amp; Conditions</h2>
        <div style="overflow-wrap: break-word;">{!! nl2br(e($estimate->terms_conditions)) !!}</div>
    </section>
@endif

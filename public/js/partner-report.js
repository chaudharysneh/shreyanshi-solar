document.addEventListener('DOMContentLoaded', function () {
    const data = window.partnerReportData;
    if (!data) return;
    ['partnerPaymentModal','partnerVoidModal'].forEach(id => {
        const modal = document.getElementById(id);
        if (modal) document.body.appendChild(modal);
    });
    if (window.jQuery?.fn.select2 && document.getElementById('payout_method')) {
        jQuery('#payout_method').select2({ theme:'bootstrap-5',width:'100%',dropdownParent:jQuery('#partnerPaymentModal'),minimumResultsForSearch:Infinity });
    }
    document.querySelectorAll('.partner-void-payment').forEach(button => button.addEventListener('click', () => {
        const form = document.getElementById('partnerVoidForm');
        form.action = button.dataset.url;
        form.reset();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('partnerVoidModal')).show();
    }));
    document.querySelectorAll('.partner-payment-form').forEach(form => form.addEventListener('submit', () => {
        form.querySelector('button[type="submit"]').disabled = true;
    }));
    function openLinkedTab() {
        const tab = Array.from(document.querySelectorAll('#partnerDashboardTabs button')).find(button => button.dataset.bsTarget === location.hash);
        if (tab) bootstrap.Tab.getOrCreateInstance(tab).show();
    }
    openLinkedTab();
    window.addEventListener('hashchange',openLinkedTab);
    if (!window.Chart) return;
    const dark = document.documentElement.dataset.theme === 'dark';
    const textColor = dark ? '#cbd5e1' : '#64748b';
    const common = { responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{color:textColor,usePointStyle:true,padding:20}}} };
    const emptyState = { id:'partnerEmpty',afterDraw(chart) {
        if (chart.data.datasets.some(set => set.data.some(value => Number(value)>0))) return;
        const {ctx,chartArea} = chart;
        ctx.save();ctx.fillStyle=textColor;ctx.font='14px sans-serif';ctx.textAlign='center';ctx.fillText('No data yet',(chartArea.left+chartArea.right)/2,(chartArea.top+chartArea.bottom)/2);ctx.restore();
    }};
    new Chart(document.getElementById('partnerLeadTrend'),{
        type:'bar',data:{labels:data.lead_chart.map(row=>row.label),datasets:[{label:'Leads given',data:data.lead_chart.map(row=>row.leads),backgroundColor:'#3b5bdb',borderRadius:5},{label:'Converted',data:data.lead_chart.map(row=>row.converted),backgroundColor:'#10b981',borderRadius:5}]},
        options:{...common,scales:{y:{beginAtZero:true,ticks:{precision:0,color:textColor}},x:{ticks:{color:textColor},grid:{display:false}}}},plugins:[emptyState],
    });
    new Chart(document.getElementById('partnerConversionChart'),{type:'doughnut',data:{labels:['Converted','Not converted'],datasets:[{data:[data.converted,Math.max(0,data.leads-data.converted)],backgroundColor:['#10b981','#dbe4f0'],borderWidth:0}]},options:{...common,cutout:'72%'},plugins:[emptyState]});
    new Chart(document.getElementById('partnerSettlementChart'),{type:'doughnut',data:{labels:['Paid','Pending'],datasets:[{data:[Math.min(data.paid,data.earned),data.pending],backgroundColor:['#10b981','#f59e0b'],borderWidth:0}]},options:{...common,cutout:'72%',plugins:{...common.plugins,tooltip:{callbacks:{label:context=>`${context.label}: ${Number(context.raw).toLocaleString(undefined,{minimumFractionDigits:2})} ${data.currency}`}}}},plugins:[emptyState]});
});

@php
    $capacityKwp = $value('total_capacity');
    $capacityWp = is_numeric($capacityKwp)
        ? rtrim(rtrim(number_format((float) $capacityKwp * 1000, 3, '.', ''), '0'), '.')
        : $capacityKwp;
@endphp
<h2>Work Completion Report for Solar Power Plant</h2>
<table><thead><tr><th style="width:8%">Sr. No</th><th style="width:57%">Component</th><th style="width:35%">Observation</th></tr></thead><tbody>
@foreach([
['1','Name',$value('consumer_name')],['2','Consumer number',$value('consumer_number')],
['3','Site/Location with Complete Address',$value('address')],['4','Category: Govt/Private Sector','Private Sector'],
['5','Sanction Number',$value('sanction_no')],['','Billing Unit',$value('billing_unit')],['','City',$value('city')],['','State',$value('state')],
['6','Sanctioned Capacity of solar PV System (KW) Installed',$value('sanctioned_capacity').' kW'],
['','Capacity of solar PV system (KW)',$value('capacity_solar').' kW'],
] as [$number,$label,$text])<tr><td>{{ $number }}</td><td>{{ $label }}</td><td>{{ $text }}</td></tr>@endforeach
<tr><th colspan="3">Specification of the Modules</th></tr>
@foreach([
['7','Type of modules (Mono/Poly)','Mono Crystalline Bifacial'],['','Make of Manufacturing',$value('make_of_solar')],
['','Wattage per module',$value('wattage_per_module').' Wp'],['','No. of PV Modules',$value('no_of_pv_module').' Nos'],
['','Total Capacity (KWP)',$capacityWp.' Wp'],['','Warrantee Details (Product + Performance)',$value('warranty')],
] as [$number,$label,$text])<tr><td>{{ $number }}</td><td>{{ $label }}</td><td>{{ $text }}</td></tr>@endforeach
<tr><th colspan="3">PCU</th></tr>
@foreach([
['8','Make & Model number of Inverter',$value('make_of_inverter')],['','Rating',$value('capacity_inverter').' kW'],
['','Type of charge controller/MPPT','0.1 kW'],['','Capacity of Inverter',$value('capacity_inverter').' kW'],
['','HPD','Inbuilt'],['','Year of manufacturing',$value('year_of_manufacturing')],
] as [$number,$label,$text])<tr><td>{{ $number }}</td><td>{{ $label }}</td><td>{{ $text }}</td></tr>@endforeach
<tr><th colspan="3">Earthing and Protection</th></tr>
<tr><td>9</td><td>No of Separate Earthing's with earth Resistance</td><td>3 Nos, &lt;5 Ohm</td></tr>
<tr><td></td><td colspan="2">It is certified that the Earth Resistance measure in presence of Licensed Electrical Contractor/Supervisor and found in order i.e. &lt; 5 Ohms as per MNRE OM Dtd. 07.06.24 for CFA Component.</td></tr>
<tr><td></td><td>Lightening Arrester</td><td>1 No</td></tr>
</tbody></table>
<p>We <b>SHREYANSHI ENTERPRISES</b> &amp; <b>{{ $value('consumer_name') }}</b> bearing Consumer Number: <b>{{ $value('consumer_number') }}</b> Ensured structural stability of installed solar power plant and obtained requisite permissions from the concerned authority. If in future, by virtue of any means due to collapsing or damage to installed solar power plant, MSEDCL will not be held responsible for any loss to property or human life, if any.</p>
<p>This is to Certified above Installed Solar PV System is working properly with electrical safety &amp; Islanding switch in case of any presence of backup inverter an arrangement should be made in such way the backup inverter supply should never be synchronized with the solar inverter to avoid any electrical accident due to back feeding. We will be held responsible for non-working of islanding mechanism and back feed to the de-energized grid.</p>
<table class="signatures"><tr><td><b>Signature (Customer)</b></td><td><b>Signature (Vendor)</b></td></tr></table>

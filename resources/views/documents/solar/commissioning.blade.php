<table><thead><tr><th colspan="3">Renewable Energy Generating System<br>Annexure-I<br><small>(Commissioning Report for RE System)</small></th></tr><tr><th style="width:8%">SNo.</th><th style="width:47%">Particulars</th><th style="width:45%">As Commissioned</th></tr></thead><tbody>
@foreach([
['1','Name of the Consumer',$value('consumer_name')],['2','Consumer Number',$value('consumer_number')],['3','Mobile Number',$value('mobile_number')],
['4','E-mail','yogeshjadhav101925@gmail.com'],['5','Address of installation',$value('address')],['6','RE Arrangement Type','Net Metering Arrangement'],
['7','RE Source','Solar'],['8','Sanctioned Capacity (kW)',$value('sanctioned_capacity').' kW'],['9','Capacity Type','5-30A/1Ph'],
['10','Project Model','On-Grid'],['11','RE Installed Capacity (Rooftop) (kW)',$value('capacity_solar').' kW'],
['12','RE Installed Capacity (Rooftop + Ground) (kW)',$value('total_capacity').' kW'],['13','RE Installed Capacity (Ground) (kW)','NA'],
['14','Installation date',$date],['15','Inverter Capacity (kW)',$value('capacity_inverter').' kW'],['','Inverter Make',$value('make_of_inverter')],
['','No. of PV Modules',$value('no_of_pv_module').' Nos'],['','Module Capacity (Wp)',$value('wattage_per_module').' Wp'],
] as [$number,$label,$text])<tr><td>{{ $number }}</td><td>{{ $label }}</td><td>{{ $text }}</td></tr>@endforeach
<tr><td></td><td>Panel Serial Numbers</td><td class="serials">{{ $value('panel_serial_numbers') }}</td></tr>
<tr><td></td><td>Inverter Serial Number</td><td class="serials">{{ $value('inverter_serial_number') }}</td></tr>
</tbody></table>
<h2>Proforma-A</h2><p class="center">COMMISSIONING REPORT (PROVISIONAL) FOR GRID CONNECTED SOLAR<br>PHOTOVOLTAIC POWER PLANT (with Net-metering facility)</p>
<p>Certified that a Grid Connected SPV Power Plant of <b>{{ $value('capacity_solar') }} kWp</b> capacity has been installed at the site <b>{{ $value('address') }}</b>, District <b>{{ $value('city') }}</b> of <b>{{ $value('state') }}</b> which has been installed by M/S <b>SHREYANSHI ENTERPRISES</b> on <b>{{ $date }}</b> as per MNRE specifications. The system has been checked for its performance and found in order for further commissioning.</p>
<table class="signatures"><tr><td>Signature of the beneficiary</td><td>Signature of the agency with<br>name, seal and date</td></tr></table>
<p>The above RTS installation has been inspected by me for Pre-Commissioning Testing of Roof Top Solar Connection at dt. {{ $date }} as per guidelines issued by the office of The Chief Engineer vide letter no. {{ $value('sanction_no') }} and found in order for commissioning.</p>
<table class="signatures"><tr><td>Name: <b>{{ $value('consumer_name') }}</b><br>Consumer No.: <b>{{ $value('consumer_number') }}</b></td><td>Signature of the MSEDCL Officer Name,<br>Designation Date and seal</td></tr></table>
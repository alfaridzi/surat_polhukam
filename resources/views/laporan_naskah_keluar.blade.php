 <link href="{{ asset('assets/vendors/bootstrap/dist/css/bootstrap.min.css') }}" rel="stylesheet">
<style type="text/css">
	* {
		font-size: 12px;
	} .table-striped > tbody > tr:nth-child(2n+1) > td, .table-striped > tbody > tr:nth-child(2n+1) > th { background-color: #74b9ff; }
</style>


<h3 class="text-center" style="font-weight:700">Laporan Naskah Keluar</h3>

<?php
$dari = date('d-m-Y', strtotime($_GET['dari']));
$sampai = date('d-m-Y', strtotime($_GET['dari']));

?>
<h4 class="text-center">{{ $dari }} s/d {{ $sampai }}</h4>

	<table class="table table-bordered table-striped" id="table-naskah-masuk">
		<thead>
			<tr>
				<th>No</th>
				<th>Tanggal Naskah</th>
				<th>Nomor Naskah</th>
				<th>Perihal</th>
				<th>Asal Naskah</th>
				<th>Tujuan Naskah</th>
				<th>Tanggal Naskah Keluar</th>
				<th>Keterangan</th>
			</tr>
		</thead>
		<tbody>

			@if($naskah->count() < 1)
			<tr>
				<td colspan="8" class="text-center"> Tidak ada data</td>
			</tr>
			@endif

			@foreach($naskah as $data)
			<tr>
				<td>{{ $no++ }}</td>
				<td>{{ $data->tanggal_naskah }}</td>
				<td>{{ $data->nomor_naskah }}</td>
				<td>{{ $data->hal }}
				<td>{{ Auth::user()->nama }}</td>
				<td>
					@foreach($data->axis_xs() as $penerimas)
               		{{ $penerimas->tujuan_kirim->jabatan->jabatan }}
                    @endforeach
                </td>
				<td>{{ $data->tanggal_registrasi }}</td>
				<th> {{ $data->get_tipe_registrasi() }}</th>
				
				
				


	
			</tr>
			@endforeach
		</tbody>
	</table>

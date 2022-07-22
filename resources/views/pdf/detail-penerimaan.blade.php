<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Detail penerimaan</title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<style>
		p{
			font-size: 13px;
		}

		body{
			margin: 0;
			padding: 0;
		}

		.table-border td, th {
			border: solid 1px black;
			padding: 5px;
		}
	</style>
</head>
<body>
	<div class="row">
		<div class="col-md-6">
			<table width="100%">
				<tr>
					<td valign="top">
						<p>
                            <b>PT. KEMBAR PUTRA MAKMUR</b><br>
                            JL. Raya Kapal, Br. Tegal Saat Gang Anggrek I No. 2 <br>
                            Badung - Bali <br>
                            TELP. 0361 - 9006481, 9006482 <br>
                            FAX. 0361 9006482 <br>
                        </p>
					</td>
					<td><h4 style="text-align: center;">RECEIVING FORM</h4></td>
				</tr>
			</table>
		</div>
	</div>
	<hr style="border-bottom: 0.5px solid #000;">
	<table width="100%">
		<tr>
			<td>
				<p>
					Supplier ID<br>
					Supplier<br>
					No DO<br>
					No SPB<br>
					Tgl Kirim<br>
					Ekspedisi
				</p>
			</td>
			<td>
				<p>
					: <br>
					: <br>
					: <br>
					: <br>
					: <br>
					: 
				</p>
			</td>
			<td>
				<p>
					{{ $penerimaan->principal_id }} <br>
					{{ $penerimaan->nama_principal }} <br>
					{{ $penerimaan->no_do }} <br>
					{{ $penerimaan->no_spb }} <br>
					{{ \Carbon\Carbon::parse($penerimaan->tgl_kirim)->format('d M Y') }} <br>
					{{ $penerimaan->transporter }}
				</p>
			</td>
			<td valign="top">
				<p>
					Number <br>
					No. PB Gudang <br>
					Rec. Date<br>
					Operator<br>
					Outlet
				</p>
			</td>
			<td valign="top">
				<p>
					:<br>
					:<br>
					:<br>
					:<br>
					:
				</p>
			</td>
			<td valign="top">
				<p>
					{{ $penerimaan->id }} <br>
					- <br>
					{{ \Carbon\Carbon::parse($penerimaan->created_at)->format('d M Y') }} <br>
					{{ $user->name }} <br>
					{{ $penerimaan->nama_gudang }}
				</p>
			</td>
		</tr>
	</table>
	<table width="100%" class="table-border">
		<tr>
			<th><p>No</p></th>
			<th><p>Stock Code</p></th>
			<th><p>Description</p></th>
			<th><p>Quantity</p></th>
		</tr>
		@php $i = 1; @endphp
		@foreach($r_detail as $d)
		<tr>
			<td><p>{{ $i }}</p></td>
			<td><p>{{ $d->kode_barang }}</p></td>
			<td><p>{{ $d->nama_barang }}</p></td>
			<td><p>{{ $d->qty }}</p></td>
		</tr>

		@php $i++; @endphp
		@endforeach
		<tr>
			<td colspan="3"></td>
			<td>{{ $r_penerimaan->total_qty }}</td>
		</tr>
	</table>
	<br>
	<hr style="border-bottom: 0.5px solid #000;">
	<table width="100%">
		<tr>
			<td valign="top" width="70%">
				<strong><p>Note : </p></strong><br><br><br>
			</td>
			<td style="text-align: center;">
				<strong><p>Receive By</p></strong>
				<br><br>
				<strong><p>( {{ $penerimaan->create_by_name }} )</p></strong>
			</td>
		</tr>
	</table>
</body>
</html>
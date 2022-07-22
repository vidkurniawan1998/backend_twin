<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Detail mutasi barang</title>
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
					<td><h4 style="text-align: center;">DO TRANSFER</h4></td>
				</tr>
			</table>
		</div>
	</div>
	<hr style="border-bottom: 0.5px solid #000;">
	<table width="100%">
		<tr>
			<td>
				<p>
					Dari <br>
					Ke <br>
					Driver
				</p>
			</td>
			<td>
				<p>
					: <br>
					: <br>
					: 
				</p>
			</td>
			<td>
				<p>
					{{ $dari->nama_gudang }} <br>
					{{ $ke->nama_gudang }} <br>
					-
				</p>
			</td>
			<td>
				<p>
					No. Do Mutasi <br>
					No. PO <br>
					Tanggal
				</p>
			</td>
			<td>
				<p>
					:<br>
					:<br>
					:
				</p>
			</td>
			<td>
				<p>
					{{ $mutasi->id }} <br>
					- <br>
					{{ \Carbon\Carbon::parse($r_mutasi->tanggal_mutasi)->format('d M Y') }}
				</p>
			</td>
		</tr>
	</table>
	<p>
		Keterangan :
	</p>
	<table width="100%" class="table-border">
		<tr>
			<th><p>No</p></th>
			<th><p>Stock Code</p></th>
			<th><p>Description</p></th>
			<th><p>Quantity</p></th>
		</tr>
		@php $i = 1; @endphp
		@foreach($r_detail_mutasi as $d)
		<tr>
			<td><p>{{ $i }}</p></td>
			<td><p>{{ $d->kode_barang }}</p></td>
			<td><p>{{ $d->nama_barang }}</p></td>
			<td><p>{{ $d->qty }} / {{ $d->qty_pcs }}</p></td>
		</tr>
		@php $i++; @endphp
		@endforeach
		<tr>
			<td colspan="3" style="text-align: right;"><p>Grand total</p></td>
			<td><p>{{ $r_mutasi->total_qty }} / {{ $r_mutasi->total_pcs }}</p></td>
		</tr>
	</table>
	<br>
	<table class="table-border" width="100%" style="text-align: center;">
		<tr>
			<td><p>Dibuat Oleh</p></td>
			<td><p>Disetujui Oleh</p></td>
			<td><p>Diketahui Oleh</p></td>
			<td colspan="2"><p>Yang menyerahkan</p></td>
			<td><p>Pengirim</p></td>
			<td><p>Penerima</p></td>
		</tr>
		<tr>
			<td>
				<br><br><br>
			</td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
		</tr>
		<tr>
			{{-- <td><p>{{ $user->name }}</p></td> --}}
			<td><p>SM/SPV</p></td>
			<td><p>Acct.</p></td>
			<td><p>Kep. Gdg</p></td>
			<td><p>Checker</p></td>
			<td><p>Driver</p></td>
			<td><p>Tuan/Toko</p></td>
		</tr>
	</table>
</body>
</html>
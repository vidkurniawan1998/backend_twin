<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>Detail penjualan</title>
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
	<table style="width: 100%">
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
			<td valign="top">
				<p>	
					KPD YTH : {{ $penjualan->nama_toko }} <br>
					{{ $penjualan->alamat_toko }} <br>
					ACCOUNT : {{ $penjualan->no_acc }} <br>
					TGL PO : {{ \Carbon\Carbon::parse($r_penjualan->tanggal)->format('d M y') }}
				</p>
			</td>
			<td valign="top" style="text-align: right;">
				<div>
					<h4>
						<strong>INVOICE</strong>
					</h4>
					<p>
						{{ $id }} <br>
						<!--TGL : {{ \Carbon\Carbon::now()->format('d M y') }} <br>-->
						<!--Hal 1 dari 1-->
					</p>
				</div>
			</td>
		</tr>
	</table>
	<br>
	<table style="width: 100%" class="table-border">
		<tr>
			<td>
				<p>No</p>
			</td>
			<td>
				<p>Kode</p>
			</td>
			<td>
				<p>Nama Barang</p>
			</td>
			<td>
				<p>Jml</p>
			</td>
			<td>
				<p>Harga</p>
			</td>
			<td>
				<p>Pot</p>
			</td>
			<td>
				<p>Subtotal</p>
			</td>
		</tr>
		@php $i = 1; @endphp
		@foreach($r_detail_penjualan as $d)
		<tr>
			<td>
				<p>{{ $i }}</p>
			</td>
			<td>
				<p>{{ $d->kode_barang }}</p>
			</td>
			<td>
				<p>{{ $d->nama_barang }}</p>
			</td>
			<td>
				<p>{{ $d->qty }} / {{ $d->qty_pcs }}</p>
			</td>
			<td>
				<p>{{ number_format($d->harga_barang->harga) }}</p>
			</td>
			<td>
				<p>{{ number_format($d->discount) }}</p>
			</td>
			<td>
				<p>{{ number_format($d->subtotal) }}</p>
			</td>
		</tr>
		@php $i++; @endphp
		@endforeach
		<tr>
			<td></td>
			<td></td>
			<td style="text-align: right;" valign="top">
				<p>Total Qty</p>
			</td>
			<td valign="top">
				<p>{{ $r_penjualan->total_qty }} / {{ $r_penjualan->total_pcs }}</p>
			</td>
			<td>
				<p>
					Subtotal <br>
					Diskon <br>
					PPn <br>
					Grand total
				</p>
			</td>
			<td>
				<p>
					: <br>
					: <br>
					: <br>
					: 
				</p>
			</td>
			<td>
				<p>
					{{ number_format($r_penjualan->total) }} <br>
					{{ number_format($r_penjualan->disc_total) }} <br>
					{{ number_format($r_penjualan->ppn) }} <br>
					{{ number_format($r_penjualan->grand_total) }}
				</p>
			</td>
		</tr>
	</table>
	<br>
	<table style="width: 100%; text-align: center;">
		<tr>
			<td>
				<p>Bag. Invoice</p>
			</td>
			<td>
				<p>Spv. Sales</p>
			</td>
			<td>
				<p>Bag. Gudang</p>
			</td>
			<td>
				<p>Pengirim</p>
			</td>
			<td>
				<p>Penerima</p>
			</td>
		</tr>
		<tr style="height: 20px;">
			<td valign="bottom">
			    <p>( {{ $user->name }} )</p></td>
			<td>
				<p>(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</p>
			</td>
			<td>
				<p>(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</p>
			</td>
			<td>
				<p>(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</p>
			</td>
			<td>
				<p>(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</p>
			</td>
		</tr>
	</table>
</body>
</html>
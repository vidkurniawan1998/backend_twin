<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>Detail penjualan</title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<style>
		p{
			font-size: 10px;
		}
        
        table{
            font-size: 10px;
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
                    <table>
                        <tr>
                            <td>GUDANG</td><td>:</td>
                            <td class="text-uppercase">{{ $pengiriman->gudang->nama_gudang }}</td>
                        </tr>
                        <tr>
                            <td>DRIVER</td><td>:</td>
                            <td class="text-uppercase">{{ $pengiriman->driver->user->name }}</td>
                        </tr>
                        <tr>
                            <td>KENDARAAN</td><td>:</td>
							<td class="text-uppercase">{{ $pengiriman->kendaraan->body_no }} - {{ $pengiriman->kendaraan->no_pol_kendaraan }}</td>
                    	</tr>
                    </table>
				</p>
			</td>
			<td valign="top" style="text-align: right;">
				<div>
					<h4>
                        <strong>PENGIRIMAN</strong><br>
					</h4>
					<p>
						{{ $pengiriman->id }} <br>
						{{ Carbon\Carbon::parse($pengiriman->tgl_pengiriman)->format('d F Y') }}
						<!--Hal 1 dari 1-->
					</p>
				</div>
			</td>
		</tr>
	</table>
	

	<strong><p>INVOICE</p></strong>
	<table style="width: 100%" class="table-border">
		<tr class="text-center">
			<td>
                No
			</td>
			<td>
                No Invoice
			</td>
			<td>
                Toko
			</td>
			<td>
                Alamat
			</td>
			<td>
                Qty
			</td>
		</tr>
		@php
			$total_qty = 0;
			$total_pcs = 0;
		@endphp
		@foreach($list_penjualan as $d)
		<tr>
			<td class="text-center">
                {{ $loop->iteration }}
			</td>
			<td class="text-center">
                {{ $d->id }}
			</td>
			<td class="text-center">
                {{ strtoupper($d->toko->nama_toko) }}
			</td>
			<td>
                {{ ucfirst($d->toko->alamat) }}
			</td>
			<td class="text-center">
				@php
					$qty = $d->detail_penjualan->sum('qty');
					$pcs = $d->detail_penjualan->sum('qty_pcs');
					$total_qty = $total_qty + $qty;
					$total_pcs = $total_pcs + $pcs;
				@endphp
                {{ $qty }} / {{ $pcs }}
			</td>
		</tr>
		@endforeach
		<tr>
			<td valign="top" colspan="4">
                Catatan : {{ $pengiriman->keterangan }}
			</td>
			<td valign="top" class="text-center">
				Total<br>
				{{ $total_qty }} / {{ $total_pcs }}
			</td>
        </tr>
        
	</table>
	<br><br>

	<strong><p>BARANG</p></strong>
	<table style="width: 100%" class="table-border">
		<tr class="text-center">
			<td>
                No
			</td>
			<td>
				Kode Barang
			</td>
			<td>
                Nama Barang
			</td>
			<td>
                Qty
			</td>
		</tr>
		@php
			$sum_qty = 0;
			$sum_pcs = 0;
		@endphp
		@foreach($list_detail_pengeluaran_barang->sortBy('kode_barang') as $d)
			@php
				$sum_qty = $sum_qty + $d->total_qty;
				$sum_pcs = $sum_pcs + $d->total_pcs;
			@endphp
		<tr>
			<td class="text-center">
                {{ $loop->iteration }}
			</td>
			<td>
                {{ strtoupper($d->kode_barang) }}
			</td>
			<td>
                {{ strtoupper($d->nama_barang) }}
			</td>

			<td class="text-center">
                {{ $d->total_qty }} / {{ $d->total_pcs }}
			</td>
		</tr>
		@endforeach
		<tr>
			<td valign="top" colspan="3" class="text-right">
                Total
			</td>
			<td valign="top" class="text-center">
				{{ $sum_qty }} / {{ $sum_pcs }}
			</td>
        </tr>
        
	</table>

	<br><br>
	
	<p style="font-size: 8px;"><i>Dicetak oleh {{$generated_by}} pada {{ $now->format('d F Y H:i') }}.</i></p>
	{{-- <table style="width: 100%; text-align: center;">
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
			    <p>( {{ $generated_by }} )</p>
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
	</table> --}}
</body>
</html>
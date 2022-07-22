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
					JL. Raya Kapal, Br. Tegal Saat Gang Anggrek I No. 2<br>
					Badung - Bali <br>
					TELP. 0361 - 9006481, 9006482 <br>
					FAX. 0361 9006482 <br>
				</p>
			</td>
			<td valign="top">
				<p>
                    <table>
                        <tr>
                            <td>KPD YTH</td><td>:</td>
                            <td>{{ $res->toko->nama_toko }}</td>
                        </tr>
                        <tr>
                            <td></td><td></td>
                            <td> {{ $res->toko->alamat }}</td>
                        </tr>
                        <tr>
                            <td>ACCOUNT</td><td>:</td>
                            <td>{{ $res->toko->no_acc }} | {{ $res->toko->cust_no }}</td>
						</tr>
						<tr>
                            <td>No PO</td><td>:</td>
                            <td>{{ $res->id }}</td>
                    	</tr>
                        <tr>
                            <td>TGL PO</td><td>:</td>
                            <td>{{ \Carbon\Carbon::parse($res->tanggal)->format('d F Y') }}</td>
                    	</tr>
                        <tr>
                            <td>SALESMAN</td><td>:</td>
                            <td>{{ $res->salesman->tim->nama_tim }} - {{ $res->salesman->user->name }}</td>
						</tr>
						{{-- <tr>
							<td>PENGIRIM</td><td>:</td>
							<td>
								{{ $res->pengiriman->kendaraan->body_no }} - {{ $res->pengiriman->kendaraan->no_pol_kendaraan }}
							</td>
						</tr>
						<tr>
							<td></td><td></td>
							<td>{{ $res->pengiriman->driver->user->name }}</td>
						</tr> --}}
                    </table>
				</p>
			</td>
			<td valign="top" style="text-align: right;">
				<div>
					<h4>
                        <strong>INVOICE</strong><br>
					</h4>
					<p>
						{{-- {{ $res->id }} <br> --}}
						{{ $res->no_invoice }} <br>
						{{-- {{ $res->printed_at->format('d F Y, H:i') }} <br> --}}
						{{ \Carbon\Carbon::now()->format('d F Y, H:i') }} <br>
						<!--Hal 1 dari 1-->
					</p>
					<h4>
                        <strong>{{ strtoupper($res->tipe_pembayaran) }}</strong><br>
					</h4>
				</div>
			</td>
		</tr>
	</table>
    <br>
	<table style="width: 100%" class="table-border">
		<tr class="text-center">
			<td>
                No
			</td>
			<td>
                Kode
			</td>
			<td>
                Nama Barang
			</td>
			<td>
                Jml
			</td>
			<td>
                Harga
			</td>
			<td>
                Disc
			</td>
			<td>
                Subtotal
			</td>
		</tr>
		@foreach($res->detail_penjualan_terkirim->sortBy('kode_barang') as $d)
		<tr>
			<td class="text-center">
                {{ $loop->iteration }}
			</td>
			<td>
                 {{ strtoupper($d->stock->barang->kode_barang) }} {{-- {{ $d->stock->barang->isi }} --}}
			</td>
			<td>
                {{ strtoupper($d->stock->barang->nama_barang) }}
			</td>
			<td class="text-center">
                {{ $d->qty }} / {{ $d->qty_pcs }}
			</td>
			<td class="text-right">
                {{ number_format($d->harga_barang->harga / 1.1) }}
			</td>
			<td class="text-right">
				{{ number_format($d->discount) }}
			</td>
			<td class="text-right">
				{{ number_format($d->subtotal) }}
			</td>
		</tr>
		@endforeach
		<tr>
			<td valign="top" colspan="3" rowspan="2">
                Catatan : {{ $res->keterangan }}
			</td>
			<td valign="top" rowspan="2" class="text-center">
				Total<br>
                {{ $res->total_qty }} / {{ $res->total_pcs }}
			</td>
			<td class="text-justify" colspan="2">
                Subtotal<br>
                Diskon<br>
                DPP<br>
                PPn
			</td>
			{{-- <td class="text-center">:<br>:<br>:<br>:</td> --}}
			<td class="text-right">
                {{ number_format($res->total) }} <br>
                {{ number_format($res->disc_total) }} <br>
                {{ number_format($res->grand_total - $res->ppn) }} <br>
                {{ number_format($res->ppn) }}
			</td>
        </tr>
        <tr>
            <td class="text-justify" colspan="2">
                Grand total
            </td>
            {{-- <td class="text-center">:</td> --}}
            <td class="text-right">
                {{ number_format($res->grand_total) }}
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
	</table>
</body>
</html>
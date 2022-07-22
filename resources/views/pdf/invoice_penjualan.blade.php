<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>INV PENJUALAN - {{ $res->id }} -- {{ $res->no_invoice }}</title>
	
	<!--<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">-->
	<!--<link rel="stylesheet" href="https://kpm-api.kembarputra.com/bootstrap/css/bootstrap.4.3.1.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">-->
	
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <!--<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>-->
	
	<style>
		p{
			/* font-size: 10px; */
			font-size: 12px;
		}
        
        table{
            /* font-size: 10px; */
			font-size: 12px;
		}

		body{
			margin: 0;
			padding: 0;
			line-height: 14px;
		}

		.table-border td, th {
			border: solid 1px black;
			padding: 5px;
		}
		
		.smaller-text{
			font-size: 10px;
		}

		.bigger-text{
			font-size: 14px;
		}
	</style>
</head>
<body>

	<table style="width: 100%">
		<tr>
			<td valign="top">
				<p>
					<b class="bigger-text">PT. KEMBAR PUTRA MAKMUR</b><br>
					<span class="smaller-text">JL. Raya Kapal, Br. Tegal Saat Gg. Anggrek I No. 2<br>
					Badung - Bali <br>
					TELP. 0361 - 9006481, 9006482 <br>
					FAX. 0361 9006482 <br></span>
				</p>
			</td>
			<td valign="top">
				<p>
                    <table>
                        <tr>
                            <td>Kpd Yth</td><td>:</td>
                            <td>{{ $res->toko->nama_toko }}</td>
                        </tr>
                        <tr>
                            <td></td><td></td>
                            <td> {{ $res->toko->alamat }}</td>
                        </tr>
                        <tr>
                            <td>Account</td><td>:</td>
                            <td>{{ $res->toko->no_acc }} | {{ $res->toko->cust_no }}</td>
						</tr>
						{{-- <tr>
                            <td>No PO</td><td>:</td>
                            <td>{{ $res->id }}</td>
                    	</tr>
                        <tr>
                            <td>Tgl PO</td><td>:</td>
                            <td>{{ \Carbon\Carbon::parse($res->tanggal)->format('d F Y') }}</td>
						</tr> --}}
						<tr>
                            <td>PO</td><td>:</td>
                            <td>{{ $res->id }} ({{ \Carbon\Carbon::parse($res->tanggal)->format('d F Y') }})</td>
                    	</tr>
                        <tr>
                            <td>Salesman</td><td>:</td>
                            <td>{{ $res->salesman->tim->nama_tim }} - {{ $res->salesman->user->name }}</td>
						</tr>
						<tr>
                            <td>Gudang</td><td>:</td>
                            {{-- <td>{{ strtoupper($res->salesman->tim->depo->gudang->nama_gudang) }}</td> --}}
                            <td>{{ strtoupper($res->nama_gudang) }}</td>
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
						{{-- <strong>{{ $res->no_invoice }}</strong><br> --}}
						<div class="bigger-text">
							<strong>{{ $res->no_invoice }}</strong><br>
						</div>
						{{-- {{ $res->printed_at->format('d F Y, H:i') }} <br> --}}
						{{ \Carbon\Carbon::now()->format('d F Y, H:i') }} <br>
						<!--Hal 1 dari 1-->
					</p>
					<h4>
						<br>
                        <strong>{{ strtoupper($res->tipe_pembayaran) }}</strong><br>
					</h4>
				</div>
			</td>
		</tr>
	</table>

	<table style="width: 100%" class="table-border">
		<tr class="text-center">
			<td class="smaller-text">
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
                Disk
			</td>
			<td>
                Subtotal
			</td>
		</tr>
		@foreach($res->detail_penjualan_terkirim->sortBy('kode_barang') as $d)
		<tr>
			<td class="text-center smaller-text">
                {{ $loop->iteration }}
			</td>
			<td>
                 <strong>{{ strtoupper($d->stock->barang->kode_barang) }}</strong>
			</td>
			<td class="smaller-text">
                {{ strtoupper($d->stock->barang->nama_barang) }}
			</td>
			<td class="text-center">
                <strong>{{ number_format($d->qty, 0 , "," , ".") }}</strong> / <strong>{{ number_format($d->qty_pcs, 0 , "," , ".") }}</strong>
			</td>
			<td class="text-right">
                {{ number_format($d->harga_barang->harga / 1.1, 2 , "," , ".") }}
			</td>
			<td class="text-right">
				{{ number_format($d->discount, 2 , "," , ".") }}
			</td>
			<td class="text-right">
				{{ number_format($d->subtotal, 2 , "," , ".") }}
			</td>
		</tr>
		@endforeach
		<tr>
			{{-- <td valign="top" colspan="3" rowspan="2"> --}}
			<td valign="top" colspan="3">
                Catatan : {{ $res->keterangan }}
			</td>
			{{-- <td valign="top" rowspan="2" class="text-center"> --}}
			<td valign="top" class="text-center">
				Total<br>
                <strong>{{ number_format($res->total_qty, 0 , "," , ".") }}</strong> / <strong>{{ number_format($res->total_pcs, 0 , "," , ".") }}</strong>
			</td>
			<td class="text-justify" colspan="2">
                Subtotal<br>
                Diskon<br>
                DPP<br>
				PPn<br>
				<strong>Grand Total</strong>
			</td>
			{{-- <td class="text-center">:<br>:<br>:<br>:</td> --}}
			<td class="text-right">
                {{ number_format($res->total, 2 , "," , ".") }} <br>
                {{ number_format($res->disc_total, 2 , "," , ".") }} <br>
                {{ number_format($res->grand_total - $res->ppn, 2 , "," , ".") }} <br>
				{{ number_format($res->ppn, 2 , "," , ".") }} <br>
				<strong class="bigger-text">{{ number_format($res->grand_total, 2 , "," , ".") }}</strong>
			</td>
        </tr>
        {{-- <tr>
            <td class="text-justify" colspan="2">
                Grand total
            </td>
            <td class="text-right">
                <strong>{{ number_format($res->grand_total, 0 , "," , ".") }}</strong>
            </td>
        </tr> --}}
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
		<tr>
			<td colspan="5">
				<p></p>
			</td>
		</tr>
		<tr style="height: 20px;">
			<td valign="bottom">
				<p>( {{ $generated_by }} )</p>
				{{-- <p>(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</p> --}}
			<td>
				<p>(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</p>
			</td>
			<td>
				<p>(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</p>
			</td>
			<td>
				<p>(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</p>
			</td>
			<td>
				<p>(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</p>
			</td>
		</tr>
	</table>
</body>
</html>
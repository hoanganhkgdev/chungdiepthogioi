@extends('layouts.app', ['bare' => $isPrint, 'noSidebar' => true])
@php
    $image = $o->image ? '/uploads/official/' . $o->image : 'https://placehold.co/80x105';
@endphp
@section('content')
<div class="container" style="margin-top:20px">
<div id="viewdetail" class="detail-2 ">
	<div class="lang-info">
		<h1 class="m-bottom hidden-print center" style="text-align:center">Thông tin chứng điệp </br> {{ $o->fullname }} - {{ $o->phapdanh }}</h1>
	</div>
	<div class="clearfix"></div>
	<div>
		<div class="btn-detail-2 hidden-print">
			<button class="btn-in" onclick="window.open('{{ url()->current() }}?print=1','','width=740,height=600,scrollbars=yes');">
				<em class="fa fa-print fa-lg">&nbsp;</em>In
			</button>
			<!-- <input id="View" type="button" value="Xuất file ảnh" />
			<a id="button" href="#">Tải ảnh</a> -->
		</div>
		
		<div id="preview" style="text-align: center;"></div>
	<div id="chungdiepthogioi">	
		<div class="card" id="in-card">
		<img class="card-img hidden-print" style="width: 800px; height: 564px;" src="/uploads/bg-card.png" />
			<table style="border-collapse: collapse; width: 100%; height:100%">
				<tbody>
					<tr>
						<td style="width: 50%;">
							<div class="card-left">
								<ul>
									<li class="thanhphangioisu">THÀNH PHẦN GIỚI SƯ</li>
									<li class="daigioidan">Đại giới đàn: <span>{{ $o->daigioidan }}</span></li>
									<img class="khung-vien" src="/uploads/khung-vien-1.png" />
									<li class="htdandau center line-height">Hòa thượng đàn đầu: <br><span>{{ $o->htdandau }}</span></li>
									<li class="yetma center line-height">Yết ma A-Xà-Lê: <br><span>{{ $o->yetma }}</span></li>
									<li class="giaotho center line-height">Giáo thọ A-Xà-Lê: <br><span>{{ $o->giaotho }}</span></li>
									<li class="tonchungtanggia center">Tôn chứng Tăng-già:</li>
									<ul class="thong-tin-3">
										<table width="100%"> 
											<tr>
												<td width="22%"><li class="denhat">Đệ nhất: </li></td>
												<td><li><span>{{ $o->denhat }}</span></li></td>
											</tr>
											<tr>
												<td><li class="denhi">Đệ nhị: </li></td>
												<td><li><span>{{ $o->denhi }}</span></li></td>
											</tr>
											<tr>
												<td><li class="denhi">Đệ tam: </li></td>
												<td><li><span>{{ $o->detam }}</span></li></td>
											</tr>
											<tr>
												<td><li class="denhi">Đệ tứ: </li></td>
												<td><li><span>{{ $o->detu }}</span></li></td>
											</tr>
											<tr>
												<td><li class="denhi">Đệ ngũ: </li></td>
												<td><li><span>{{ $o->dengu }}</span></li></td>
											</tr>
											<tr>
												<td><li class="denhi">Đệ lục: </li></td>
												<td><li><span>{{ $o->deluc }}</span></li></td>
											</tr>
											<tr>
												<td><li class="denhi">Đệ thất: </li></td>
												<td><li><span>{{ $o->dethat }}</span></li></td>
											</tr>
										</table>
									</ul>
									
									<li class="image-01"><img class="img-1" src="https://placehold.co/80x105" /><img class="img-2" width="80px" height="105px" src="{{ $image }}" /></li>
									<li class="chungdiepso">Chứng điệp số: <span>{{ $o->chungdiepso }} </span>CĐ/TS/TƯGH</span></li>
								</ul>
							</div>
						</td>
						<td style="width: 50%;">
							<div class="card-right">
								<ul>
									<li class="chungnhangioitu">CHỨNG NHẬN GIỚI TỬ</li>
									<li class="duongline"><img width="130" src="/uploads/duong-line.png" /></li>
									<li class="qrcode"><img src="https://api.qrserver.com/v1/create-qr-code/?size=90x90&data={{ $o->qrUrl() }}&choe=UTF-8" title="{{ $o->fullname }}" /></li>
									<li class="masogioitu">Mã số giới tử: <span>{{ $o->masothe }}</span></li>
									<ul class="thong-tin">
										<li>Đạo hiệu (pháp danh): <span class="phapdanh">{{ $o->phapdanh }}</span></li>
										<li>Họ và tên: <span class="fullname">{{ $o->fullname }}</span></li>
										<li>Ngày và nơi sinh: <span>{{ $o->namsinh }} - {{ $o->noisinh }}</span></li>
										<li>Ngày và nơi xuất gia: <span>{{ $o->ngayxuatgia }}</span></li>
										<li class="center"><span class="text">{{ $o->noixuatgia }}</span></li>
										<li>Nơi thường trú: <span id="ovde" style="text-align: center;">{{ $o->diachi }}</span></li>
										<li>Đã thọ giới: <span class="thogioi" id="thogioi">{{ $o->thogioi }}</span></li>
										<li class="gioidan">Giới đàn do BTS GHPGVN: <span>{{ $o->part->title ?? '' }}</span></li>
										<li class="tochucngay">Tổ chức ngày: <span>{{ $o->tochucngay }}</span></li>
										<li class="diachigioidan">Tại: <span class="text-1">{{ $o->diachigioidan }}</span></li>
									</ul>
									<ul id="ht-thien-nhon"><img width="180" src="/uploads/chu-ky-ht-thien-nhon.png"></ul>
									<ul id="ht-thien-phap"><img width="180" src="/uploads/chu-ky-ht-thien-phap.png"></ul>
									<ul id="dong-moc"><img width="140" src="/uploads/con-dau.png"></ul>
									<ul class="thong-tin-2">
										<li class="capngay">Cấp ngày <span>{{ $o->ngay_cap }}</span> tháng <span>{{ $o->thang_cap }}</span> năm <span>{{ $o->nam_cap }}</span></li>
										@if($o->signedByThienNhon())
										<li class="banthuongtruc" id="banthuongtruc">TUN. BAN THƯỜNG TRỰC HĐTS GHPGVN </br> TRƯỞNG BAN TĂNG SỰ TƯ</li>
										<li class="ht" id="ht">Hòa thượng THÍCH THIỆN NHƠN</li>
										@else
										<li class="banthuongtruc" id="banthuongtruc">TUN. BAN THƯỜNG TRỰC HĐTS GHPGVN </br> P. TRƯỞNG BAN TT BAN TĂNG SỰ TƯ</li>
										<li class="ht" id="ht">Hòa thượng THÍCH THIỆN PHÁP</li>
										@endif
									</ul>
								</ul>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
	</div>
</div>
<style type="text/css">
@media print {
	html, body {
		margin: 0 !important;
		padding: 0 !important;
		width: 210mm;
		height: 147mm;
		overflow: hidden;
		background: #fff !important;
		-webkit-print-color-adjust: exact;
		print-color-adjust: exact;
	}
	.container, #viewdetail, #chungdiepthogioi {
		width: auto !important;
		max-width: none !important;
		margin: 0 !important;
		padding: 0 !important;
	}
	.card {
		margin: 0 auto !important;
		zoom: 0.98;
		page-break-inside: avoid;
		break-inside: avoid;
	}
	.hidden-print {
		display: none !important;
	}
	.card-right li{
		color: #c57917 !important;
	}
	.card-right span {
		color: #000000;
	}
	.card-left li{
		color: #c57917 !important;
	}
	.card-left span {
		color: #000000;
	}		
	.card-right #dong-moc {
		position: absolute;
    	padding: 0;
   	 	margin-top: -50px;
    	margin-left: 60px;
    	display: none;
	}
	.card-right #ht-thien-nhon {
		position: absolute;
    	padding: 0;
    	margin-top: 40px;
    	margin-left: 88px;
    	display: none !important;
	}

	.card-right #ht-thien-phap {
		position: absolute;
    	padding: 0;
    	margin-top: 40px;
    	margin-left: 108px;
    	display: none !important;
	}
	.card-left .image-01 .img-2 {
		display: none;
	}
}
@page {
	size: A5 landscape;
	margin: 0;
}
.btn-detail-2 {
     margin: auto;
     width: 100%;
     padding: 20px;
     text-align: center;
}
@font-face {
	font-family: alexander;
	src: url('/assets/fonts/alexander.woff') format('woff');
}
.card {
	margin: auto;
	width: 800px;
	height: 564px;
	background: url('/uploads/1.png');
	font-family: "alexander";
	font-size: 14px;
	line-height: 1.42857;
	color: #000;
}
.card ul, .card ol, .card li {
	list-style: none;
	margin-left: 0;
}
.card table, .card td {
	background: transparent;
	border: 0;
	padding: 0;
}
.card span { font-weight: normal; }
#viewdetail { margin-bottom: 10px; }
.btn-in {
	cursor: pointer;
	border-radius: 24px !important;
	border: 1px solid var(--maroon) !important;
	background: var(--maroon) !important;
	color: #fff !important;
	padding: 9px 28px !important;
	font-family: 'Inter', sans-serif;
	font-weight: 600;
	box-shadow: 0 2px 8px rgba(110,19,19,.25);
}
.btn-in:hover { background: var(--gold) !important; border-color: var(--gold) !important; }
.lang-info h1 { margin-bottom: 14px; line-height: 1.4; }
.center {
	text-align: center;
}
.line-height {
	line-height: 17px;
}

#button {
	padding: 12px 20px;
    border: solid 1px #000;
    border-radius: 3px;
    background: #e2e2e2;
    color: #000000;
}
#button:hover {
	background: #ffcccc;
}
#View {
	padding: 10px 20px;
    border: solid 1px #000;
    border-radius: 3px;
    background: #e2e2e2;
    color: #000000;
    line-height: normal !important;
}
#View:hover {
	background: #ffcccc;
}
.btn-in {
	padding: 10px 20px;
    border: solid 1px #000;
    border-radius: 3px;
    background: #e2e2e2;
    color: #000000;
    line-height: normal !important;
}
.btn-in:hover {
	background: #ffcccc;
}

.text {
  display: block;
  width: 300px;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.text-1 {
  display: inline-flex;
  width: 273px;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

/* Card Right */
.card-right {
	padding: 0 40px 0 45px;
	height: 100%;
}
.card-right li{
	color: #c57917;
}
.card-right span {
		color: #000000;
}
.card-right ul {
	margin: 0;
	padding: 36px 0 0 0;
}
.card-right .chungnhangioitu {
	text-align: center;
	font-size: 22px;
}
.card-right .duongline {
	text-align: center;
	padding: 0;
	margin: 0;
}
.card-right .image-01 {
	width: 80px;
	height: 105px;
	margin: auto;
}
.card-right .image-01 .img-1 {
	position: absolute;
}
.card-right .image-01 .img-2 {
	position: absolute;
}
.card-right .masogioitu {
	text-align: center;
	margin-top: 7px;
}
.card-right .fullname, .phapdanh, .thogioi {
	text-transform: uppercase;
}
.card-right .noisinh, .noixuatgia {
	text-align: center;
}
.card-right ul.thong-tin {
	padding: 0;
	line-height: 18px;
}
.card-right .thong-tin-2 {
	padding: 0px 0 0 55px;
}
.card-right .thong-tin-2 .capngay {
	text-align: center;
	font-size: 10px;
	font-style: italic;
}
.card-right .thong-tin-2 .banthuongtruc {
	text-align: center;
	font-size: 12px;
}
.card-right .thong-tin-2 .ht {
	text-align: center;
	margin-top: 55px;
}
.card-right #dong-moc {
	position: absolute;
    padding: 0;
    margin-top: -20px;
    margin-left: 48px;
}
.card-right #ht-thien-nhon {
	position: absolute;
    padding: 0;
    margin-top: 48px;
    margin-left: 100px;
    display: none
}

.card-right #ht-thien-phap {
	position: absolute;
    padding: 0;
    margin-top: 40px;
    margin-left: 108px;
    display: none
}
.card-right .qrcode {
	text-align: center;
}
/* Card Left */

.card-left {
	padding: 0 45px 0 60px;
	height: 100%;
}
.card-left ul {
	padding: 36px 0 0 0;
	margin: 0;
}
.card-left li{
	color: #c57917;
}
.card-left span{
	color: #000000;
}
.card-left .thanhphangioisu {
	text-align: center;
	font-size: 22px;
}
.card-left .daigioidan {
	margin-top: 5px;
    margin-bottom: 3px;
    text-align: center;
}
.card-left .daigioidan span {
	text-transform: uppercase;
}
.card-left .tonchungtanggia {
	margin-top: 5px;
}
.card-left ul.thong-tin-3 {
	padding: 0 0 0 20px;
    margin: 0;
    line-height: 21px;
}
.card-left .duongline {
	text-align: center;
	padding: 0;
	margin: 0;
}

.card-left .chungdiepso {
	text-align: center;
	font-size: 12px;
	margin-top: 5px;
}
.khung-vien {
	position: absolute;
	width: 290px;
}
.card-left .htdandau {
	margin-top: 8px;
}
.card-left .image-01 {
	width: 80px;
	height: 105px;
	margin: auto;
	margin-top: 7px;
}
.card-left .image-01 .img-1 {
	position: absolute;
}
.card-left .image-01 .img-2 {
	position: absolute;
}
.card-img {
	position: absolute;
	width: 800px;
	z-index: -999;
}

</style>
</div>
@endsection
@push('scripts')
@if($isPrint)
<script>$(function () { window.print(); });</script>
@endif
<script>
// Hòa thượng ký (tên và chức danh đã được dựng sẵn ở trên theo thọ giới): Tỳ Kheo, Tỳ Kheo Ni -> Thích Thiện Nhơn; còn lại -> Thích Thiện Pháp.
$('#ht-thien-nhon').toggle({{ $o->signedByThienNhon() ? 'true' : 'false' }});
$('#ht-thien-phap').toggle({{ $o->signedByThienNhon() ? 'false' : 'true' }});
</script>
@endpush

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Event reminder</title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
	<style>
		.banner{
			width: 100%;
			max-height: 140px;
			margin-bottom: 30px;
			background-color:#C3C9D6;
			padding:12px;
			position:relative;
			text-align:center;
		}
		.banner img{
			width: auto;
			margin:auto;
			height: 60px;
		}
		h3{
			margin-bottom: 10px;
			margin-top: 10px;
		}
		table{
			margin-top: 30px;
			margin-bottom:30px
		}	
		table tr th, table tr td{
		    text-align: left;
		}
		table tr th{
		    padding-right: 18px;
		}
		.caption{
		    color: #333;
		    font-weight: 600;
			margin-top: 15px;
			font-size: 18px;
		}
		.greetings{
			font-size: 15px;
			font-weight: 400;
		}
		.body{
			font-size: 16px;
			margin-bottom:25px;
		}
		.footer{
			margin-bottom:20px;
			padding-top:20px;
			margin-top:35px;
			border-top: 1px solid #eeefff;
		}
		.footer p{
			font-size:14px;
			font-style: italic;
		}
		.footer h6{
			font-size:14px;
			margin-top:2px;
		}
		.footer h3{
			font-size:20px;
			font-weight:600;
			margin-top:15px;
			margin-bottom:0px
		}
		.footer .logo{
			width: 60px;
			height: auto;
			margin-top:20px
		}
	</style>

</head>
<body>
	<div class="banner">
		<img src="{{$message->embed(asset('img/fob_logo.png'))}}">
	</div>
	
	<h5 class="greetings"> Hello {{ $name }}, <h5>
	
	<p class="body">
		Thank you for contacting FiberOne, one of our Representatives will reach out to you soon
	</p>

	
	<div class="footer">
		<p class="signature">Best Regards</p>
	</div>


	<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
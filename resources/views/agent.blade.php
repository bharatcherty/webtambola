<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Agent-List|Booking</title>
    <link rel="stylesheet" href="{{ URL::asset('css/user-page.css') }}">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
   

</head>
<body>


<div class="agents" id="agents_list">
    <button class="a-list" style="text-align:center;">BOOKING AGENT LIST</button>
</div>

	<script>

	var agents = @json($agents, JSON_PRETTY_PRINT);

	var agents_list=document.getElementById('agents_list')

	for(key in agents){
		var agent=agents[key];
		var single_agent=document.createElement('div');
		single_agent.className="agent-list";
		var button=document.createElement('button');
		button.innerHTML=agent['agent_name'];
		single_agent.appendChild(button);
		var somei=document.createElement('i');
		somei.className="fa fa-phone icons caller";
		somei.style="font-size: 28px; float: right; margin-right: 2rem;"
		// somei.innerHTML="call";
		somei.id=agent['agent_phone'];
		single_agent.appendChild(somei);
		var somew=document.createElement('i');
		somew.className="fa fa-whatsapp icons whatsapp";
		somew.style="font-size: 28px; float: right; margin-right: 2rem;"
		// somew.innerHTML="whatsapp";
		somew.id='https://wa.me/+91'+agent['agent_whatsapp'];
		single_agent.appendChild(somew);
		agents_list.appendChild(single_agent);
	}

	var callButton = document.querySelectorAll('.caller');
	callButton.forEach(el => el.addEventListener('click', event => {
			// num += event.target.innerHTML;
			// console.log(num);
			document.location.href = "tel:+91"+event.target.id;
			// addDisplayNumber(event.target.innerHTML);

			// document.getElementById("abcd").innerHTML = num;
		}));


		var whatsappButton = document.querySelectorAll('.whatsapp');
		whatsappButton.forEach(el => el.addEventListener('click', event => {
			// num += event.target.innerHTML;
			// console.log(num);
			window.open(event.target.id, '_blank').focus();
			// document.location.href = "tel:+91"+event.target.id;
			// addDisplayNumber(event.target.innerHTML);

			// document.getElementById("abcd").innerHTML = num;
		}));

	// <div class="agent-list">
    //     <button>Aryan Raj</button>
    //     <span class="iconify" data-inline="false" data-icon="eva:phone-call-fill" style="font-size: 28px; float: right; margin-right: 2rem;"></span>
    // </div>  

	// console.log(agents);

	</script>

</body>
</html>
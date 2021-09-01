<?php

namespace App\Classes;

class GenerateTicket{


	function generate_tickets(){
		$tickets = [];


		$ar = [1, 2, 3, 4, 5, 6, 7, 8, 9];
		$ticket = [
		[-1, 0, -1, 0, -1, -1, -1, 0, 0, -1, 0, 0, 0, -1, 0, -1, 0, -1],
		[0, -1, 0, -1, -1, 0, -1, -1, 0, 0, -1, 0, -1, -1, -1, 0, -1, 0],
		[-1, 0, -1, -1, 0, -1, 0, -1, -1, -1, 0, -1, -1, 0, 0, -1, 0, 0],
		[0, -1, 0, 0, -1, 0, -1, 0, -1, 0, -1, -1, 0, 0, -1, -1, -1, -1],
		[-1, 0, -1, -1, 0, -1, 0, -1, 0, -1, -1, 0, 0, -1, 0, 0, -1, -1],
		[0, -1, -1, 0, -1, 0, -1, -1, -1, 0, 0, -1, -1, 0, -1, -1, 0, 0],
		[-1, -1, 0, -1, 0, -1, -1, 0, 0, -1, 0, 0, -1, -1, 0, 0, -1, -1],
		[0, 0, -1, -1, 0, 0, 0, -1, -1, -1, -1, -1, 0, -1, -1, 0, -1, 0],
		[-1, -1, 0, 0, -1, -1, 0, 0, -1, 0, -1, -1, -1, 0, -1, -1, 0, -1]
		];
	
		$k = 0;
		shuffle($ar);
		for($j=0;$j<18;$j++){
			if($ticket[0][$j]==-1){
				$ticket[0][$j]=$ar[$k++];
			}
		}

		array_push($ar,0);

		for($i=1;$i<9;$i++){
			if($i==8){
				array_push($ar,10);
			}
			$k=0;
			shuffle($ar);

			for($j=0;$j<18;$j++){
				if($ticket[$i][$j]==-1){
					$ticket[$i][$j]=$ar[$k++]+10*$i;
				}
			}
		}

		for($i=0;$i<9;$i++){
			for($j=0;$j<6;$j++){
				if($ticket[$i][$j*3]>$ticket[$i][$j*3+2]&&$ticket[$i][$j*3+2]!=0){
					$t=$ticket[$i][$j*3];
					$ticket[$i][$j*3]=$ticket[$i][$j*3+2];
					$ticket[$i][$j*3+2]=$t;
				}
				if($ticket[$i][$j*3]>$ticket[$i][$j*3+1]&&$ticket[$i][$j*3+1]!=0){
					$t=$ticket[$i][$j*3];
					$ticket[$i][$j*3]=$ticket[$i][$j*3+1];
					$ticket[$i][$j*3+1]=$t;
				}
				if($ticket[$i][$j*3+1]>$ticket[$i][$j*3+2]&&$ticket[$i][$j*3+2]!=0){
					$t=$ticket[$i][$j*3+1];
					$ticket[$i][$j*3+1]=$ticket[$i][$j*3+2];
					$ticket[$i][$j*3+2]=$t;
				}

			}
		}

		$fullTickets=[];
		for($z=0;$z<6;$z++){
			$tickets=[];
			for($i=3*$z;$i<3*$z+3;$i++){
				$temp=[];
				for($j=0;$j<9;$j++){
					array_push($temp,$ticket[$j][$i]);
				}
				array_push($tickets,$temp);
			}
			array_push($fullTickets,$tickets);
		}

		shuffle($fullTickets);

		$tickets=[];
		for($z=0;$z<6;$z++){
			$ticket="";
			for($j=0;$j<3;$j++){
				for($i=0;$i<9;$i++){
					if($fullTickets[$z][$j][$i]==0){
						$ticket=$ticket.' ,';
					}else{
						$ticket=$ticket.$fullTickets[$z][$j][$i].',';
					}
				}
			}
			array_push($tickets,$ticket);
		}
		
		return $tickets;
	}
	 

}
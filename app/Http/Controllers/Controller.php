<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


	public function test(){


		$firstCloumn=[];
    $selectedColumnPos=[];
    
    $column=0;
    
    for($i=0;$i<18;$i++){
        array_push($firstCloumn,($i*9)+$column);
    }
    
    shuffle($firstCloumn);
    shuffle($firstCloumn);
    shuffle($firstCloumn);
    // $selectedFirstColumn=array_rand($firstCloumn,9);
	$selectedFirstColumn=array_slice($firstCloumn,0,9);
	sort($selectedFirstColumn);

	return $selectedFirstColumn;

		// return $this->generateTicket();
	}

	public function generateTicket(){
		$numbers=array();

		for($i=1;$i<=90;$i++){
			array_push($numbers,$i);
		}

		shuffle($numbers);

		$positions=array();
		for($i=0;$i<9;$i++){
			array_push($positions,$i);
		}

		shuffle($positions);

		$selectedPosition=array_slice($positions,0,5);
		$leftOver=array_slice($positions,5,4);

		unset($positions);
		$positions=array();

		for($i=9;$i<18;$i++){
			if($i!=$leftOver[0]+9&&$i!=$leftOver[1]+9){
				array_push($positions,$i);
			}
		}

		shuffle($positions);
		$positions=array_slice($positions,0,3);
		array_push($positions,$leftOver[0]+9);
		array_push($positions,$leftOver[1]+9);

		shuffle($positions);

		$selectedPosition=array_merge($selectedPosition,$positions);

		
		unset($positions);
		$positions=array();

		for($i=18;$i<27;$i++){
			if($i!=$leftOver[2]+18&&$i!=$leftOver[3]+18){
				array_push($positions,$i);
			}
		}

		shuffle($positions);
		$positions=array_slice($positions,0,3);
		array_push($positions,$leftOver[2]+18);
		array_push($positions,$leftOver[3]+18);

		shuffle($positions);

		$selectedPosition=array_merge($selectedPosition,$positions);

		$ticket="";
		$resultingNumbers=array();

		// for($j=0;$j<6;$j++){
			for($i=0;$i<27;$i++){
				if(in_array($i,$selectedPosition)){
					$rem=$i%9;
					$resultingNumbers=$this->getRandomNumber($rem,$resultingNumbers);
					// Log::debug(''.$resultingNumbers);
				}else{
					array_push($resultingNumbers," ");
				}
			}
		// }
		

		foreach($resultingNumbers as $st){
			$ticket=$ticket.$st.",";
		}

		return $ticket;
	}

	public function getRandomNumber($rem,$resultingNumber){
		$randNum=-1;

		do{
			if($rem==0){
				$randNum=rand(1,9);
			}else if($rem==8){
				$randNum=rand(80,90);
			}else{
				$randNum=rand($rem*10,$rem*10+9);
			}

		}while(in_array($randNum,$resultingNumber));

		$divisor=floor(sizeof($resultingNumber)/9);

		if($divisor==1){

			if($resultingNumber[sizeof($resultingNumber)%9]!=" "){
				if($resultingNumber[sizeof($resultingNumber)%9]>$randNum){
					$temp=$resultingNumber[sizeof($resultingNumber)%9];
					$resultingNumber[sizeof($resultingNumber)%9]=$randNum;
					array_push($resultingNumber,$temp);
				}else{
					array_push($resultingNumber,$randNum);
				}
			}else{
				array_push($resultingNumber,$randNum);
			}

		}else if($divisor==2){

			if($resultingNumber[sizeof($resultingNumber)-9]!=" "){
				if($resultingNumber[sizeof($resultingNumber)-9]>$randNum){
					$temp=$resultingNumber[sizeof($resultingNumber)-9];
					$resultingNumber[sizeof($resultingNumber)-9]=$randNum;
					array_push($resultingNumber,$temp);

					if($resultingNumber[sizeof($resultingNumber)-19]!=" "&&$resultingNumber[sizeof($resultingNumber)-10]!=" "){
						if($resultingNumber[sizeof($resultingNumber)-19]>$resultingNumber[sizeof($resultingNumber)-10]){
							$temp2=$resultingNumber[sizeof($resultingNumber)-19];
							$resultingNumber[sizeof($resultingNumber)-19]=$resultingNumber[sizeof($resultingNumber)-10];
							$resultingNumber[sizeof($resultingNumber)-10]=$temp2;
						}
					}
				}else{
					array_push($resultingNumber,$randNum);
				}
			}else{
				if($resultingNumber[sizeof($resultingNumber)-9]!=" "){
					if($resultingNumber[sizeof($resultingNumber)-18]>$randNum){
						$temp=$resultingNumber[sizeof($resultingNumber)-18];
						$resultingNumber[sizeof($resultingNumber)-18]=$randNum;
						array_push($resultingNumber,$temp);
					}else{
						array_push($resultingNumber,$randNum);
					}
				}else{
					array_push($resultingNumber,$randNum);
				}
			}

		}else{
			array_push($resultingNumber,$randNum);
		}

		return $resultingNumber;
	}

}

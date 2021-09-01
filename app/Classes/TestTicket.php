<?php

namespace App\Classes;

use Illuminate\Support\Facades\Log;

class TestTicket{

	//Get random number within range
	function getRandom($min,$max){
		return rand($min,$max);
	}

	function getNumberOfElementsInSet($set){
		$count=0;
		for($i=0;$i<count($set);++$i){
			// Log::debug($count);
			$count=$count+count($set[$i]);
		}
		return $count;
	}

	function getRowCount($house,$rowIndex){
		$count=0;
		for($i=0;$i<count($house[$rowIndex]);++$i){
			if($house[$rowIndex][$i]!=0){
				++$count;
			}
		}
		return $count;
	}

	function getEmptyFullTicket(){
		$houses=[];
		for($houseNo=0;$houseNo<6;++$houseNo){
			$house=[];
			for($rowNo=0;$rowNo<3;++$rowNo){
				$row=[0,0,0,0,0,0,0,0,0];
				array_push($house,$row);
			}
			$houses[$houseNo]=$house;
		}
		return $houses;
	}

	function generate(){
		$col1=[];$col2=[];
		$col3=[];$col4=[];
		$col5=[];$col6=[];
		$col7=[];$col8=[];
		$col9=[];

		for($i=1;$i<=9;++$i){
			array_push($col1,$i);
		}

		for($i=10;$i<=19;++$i){
			array_push($col2,$i);
		}

		for($i=20;$i<=29;++$i){
			array_push($col3,$i);
		}

		for($i=30;$i<=39;++$i){
			array_push($col4,$i);
		}

		for($i=40;$i<=49;++$i){
			array_push($col5,$i);
		}

		for($i=50;$i<=59;++$i){
			array_push($col6,$i);
		}

		for($i=60;$i<=69;++$i){
			array_push($col7,$i);
		}

		for($i=70;$i<=79;++$i){
			array_push($col8,$i);
		}

		for($i=80;$i<=90;++$i){
			array_push($col9,$i);
		}

		$columns=[$col1,$col2,$col3,$col4,$col5,$col6,$col7,$col8,$col9];

		$set1=[];$set2=[];
		$set3=[];$set4=[];
		$set5=[];$set6=[];

		for($i=0;$i<9;++$i){
			array_push($set1,[]);
			array_push($set2,[]);
			array_push($set3,[]);
			array_push($set4,[]);
			array_push($set5,[]);
			array_push($set6,[]);
		}

		$sets=[$set1,$set2,$set3,$set4,$set5,$set6];

		for($i=0;$i<9;++$i){
			$col=&$columns[$i];
			for($j=0;$j<6;++$j){
				$randomNumIndex=$this->getRandom(0,count($col)-1);
				$randomNum=&$col[$randomNumIndex];
				$set=&$sets[$j][$i];
				array_push($set,$randomNum);
				array_splice($col,$randomNumIndex,1);
			}
		}

		$lastCol=&$columns[count($columns)-1];
		$randomNumIndex=$this->getRandom(0,count($lastCol)-1);
		$randomNum=&$lastCol[$randomNumIndex];
		$randomSetIndex=$this->getRandom(0,count($sets)-1);
		$randomSet=&$sets[$randomSetIndex][8];
		array_push($randomSet,$randomNum);
		array_splice($lastCol,$randomNumIndex,1);

		for($pass=0;$pass<3;++$pass){
			for($j=0;$j<9;++$j){
				$col=&$columns[$j];
				if(count($col)==0)
					continue;
				$randomNumIndex=$this->getRandom(0,count($col)-1);
				$randomNum=&$col[$randomNumIndex];
				$vacantSetFound=false;
				while($vacantSetFound==false){
					$randomSetIndex=$this->getRandom(0,count($sets)-1);
					$randomSet=&$sets[$randomSetIndex];
					if($this->getNumberOfElementsInSet($randomSet)==15
						||count($randomSet[$j])==2)
						continue;
					$vacantSetFound=true;
					array_push($randomSet[$j],$randomNum);
					array_splice($col,$randomNumIndex,1);
				}
			}
		}

		//Last Pass
		for($j=0;$j<9;++$j){
			$col=&$columns[$j];
			if(count($col)==0)
				continue;
			$randomNumIndex=$this->getRandom(0,count($col)-1);
			$randomNum=&$col[$randomNumIndex];
			$vacantSetFound=false;
			while($vacantSetFound==false){
				$randomSetIndex=$this->getRandom(0,count($sets)-1);
				$randomSet=&$sets[$randomSetIndex];
				if($this->getNumberOfElementsInSet($randomSet)==15
					||count($randomSet[$j])==3)
					continue;
				$vacantSetFound=true;
				array_push($randomSet[$j],$randomNum);
				array_splice($col,$randomNumIndex,1);
			}
		}

		for($i=0;$i<6;++$i){
			for($j=0;$j<9;++$j){
				sort($sets[$i][$j]);
			}
		}
		return $sets;
	}

	function putElements($set,$house){
		for($i=0;$i<9;++$i){
			if(count($set[$i])==3){
				for($j=0;$j<3;++$j){
					$house[$j][$i]=&$set[$i][$j];
				}
			}
		}


		$counter=0;
		$columnIndicesWithTwoNums=[];
		for($i=0;$i<9;++$i){
			if(count($set[$i])==2){
				array_push($columnIndicesWithTwoNums,$i);
			}
		}



		$lenColumnsWithTwoNums=count($columnIndicesWithTwoNums);
		for($i=0;$i<$lenColumnsWithTwoNums;++$i){
			$randomColumnIndexInArray=$this->getRandom(0,count($columnIndicesWithTwoNums)-1);
			$actualRandomColumnIndex=&$columnIndicesWithTwoNums[$randomColumnIndexInArray];
			$preComp=[[0,1],[0,2],[1,2],];

			$indices=&$preComp[$counter%3];
			$house[$indices[0]][$actualRandomColumnIndex]=
				&$set[$actualRandomColumnIndex][0];
			$house[$indices[1]][$actualRandomColumnIndex]=
				&$set[$actualRandomColumnIndex][1];
				array_splice($columnIndicesWithTwoNums,$randomColumnIndexInArray,1);
				++$counter;
		}


		for($i=0;$i<9;++$i){
			if(count($set[$i])==1){
				$randomIndex=$this->getRandom(0,2);
				while($house[$randomIndex][$i]!=0||
					$this->getRowCount($house,$randomIndex)==5){
						$randomIndex=$this->getRandom(0,2);
					}
				$house[$randomIndex][$i]=$set[$i][0];
			}
		}
		return $house;
	}

	function generateTicket($numHouses){
		

		// sleep(3);
		// return $fullTicket;
	
		do{
		$falsyCheck=false;

		$sets=$this->generate();

		$fullTicket=$this->getEmptyFullTicket();
	
		for($i=0;$i<$numHouses;++$i){
			$fullTicket[$i]=$this->putElements($sets[$i],$fullTicket[$i]);
		}
			$tickets=[];
			for($i=0;$i<$numHouses&&$falsyCheck===false;++$i){
				$ticket="";
				// echo("\nTicket " + $i + " \n");
				for($j=0;$j<3&&$falsyCheck===false;++$j){
					$row="";
					for($z=0;$z<9;++$z){
						if($z==8){
							$row=$row.$fullTicket[$i][$j][$z];
						}else{
							$row=$row.$fullTicket[$i][$j][$z].',';
						}
						if($fullTicket[$i][$j][$z]==0){
							$ticket=$ticket.' ,';
						}else{
							$ticket=$ticket.$fullTicket[$i][$j][$z].',';
						}
					}
					if($this->startsWith($row,"0,0,0,0")||$this->endsWith($row,"0,0,0,0")
					||($this->startsWith($row,"0,")&&$this->endsWith($row,",0,0,0"))
					||($this->startsWith($row,"0,0,0")&&$this->endsWith($row,",0"))
					||($this->startsWith($row,"0,0")&&$this->endsWith($row,"0,0"))){
						$falsyCheck=true;
					}
					Log::debug("Falsy check ".$falsyCheck);
					// echo($fullTicket[$i][$j]+"\t");
				}
				array_push($tickets,$ticket);
			}

		}while($falsyCheck===true);
		

		

		return $tickets;
	}
	function startsWith($haystack, $needle) {
		return substr_compare($haystack, $needle, 0, strlen($needle)) === 0;
	}
	function endsWith($haystack, $needle) {
		return substr_compare($haystack, $needle, -strlen($needle)) === 0;
	}
	
}
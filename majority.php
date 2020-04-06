<?php
header('Content-Type: application/json; charset=utf-8');

require_once('config.php');

	// New Connection
	$db = new mysqli($ADRES, $USER, $MDP, $BASE);
	mysqli_set_charset($db,"utf8");
	
	// Check for errors
	if(mysqli_connect_errno()){
	echo mysqli_connect_error();
	}
try
{
	
	
	
$reponse = $db->query("CALL API_MajorityQuestionHasard();");
	
	if($reponse){
		 // Cycle through results
		$data = $reponse->fetch_assoc();

		$questionADecoudre = $data["question"];
		
		$noThisId = $data["IDQuestion"];
		
		// Free result set
		$reponse->close();
		$db->next_result();
			
	$motsToSearchForReponseAleatoire = explode(" ", $questionADecoudre);
	$motCleaned = "";
	
	$listeMotsClef = [];
	
	foreach ( $motsToSearchForReponseAleatoire as $currentMot)
	{
		$motCleaned = str_replace('"','',$currentMot);
		$motCleaned = str_replace('?','',$motCleaned);
		$motCleaned = str_replace('!','',$motCleaned);
		$motCleaned = str_replace('.','',$motCleaned);
		$motCleaned = str_replace(',','',$motCleaned);
		$motCleaned = str_replace(':','',$motCleaned);
		
		if (strtoupper($motCleaned) != "QUEL" && 
		strtoupper($motCleaned) != "EST" && 
		strtoupper($motCleaned) != "C'EST" && 
		strtoupper($motCleaned) != "POUR" && 
		strtoupper($motCleaned) != "PAR" && 		
		strtoupper($motCleaned) != "LE" && 
		strtoupper($motCleaned) != "LES" && 
		strtoupper($motCleaned) != "LA" && 
		strtoupper($motCleaned) != "UN" && 
		strtoupper($motCleaned) != "UNE" && 
		strtoupper($motCleaned) != "DE" && 
		strtoupper($motCleaned) != "DES" && 
		strtoupper($motCleaned) != "D'" && 
		strtoupper($motCleaned) != "L'" && 
		strtoupper($motCleaned) != "T'" && 
		strtoupper($motCleaned) != "A" &&	
		strtoupper($motCleaned) != "EU" &&			
		strtoupper($motCleaned) != "MAIS" && 
		strtoupper($motCleaned) != "OU" &&
		strtoupper($motCleaned) != "OÙ" &&	
				   $motCleaned  != "où" &&			
		strtoupper($motCleaned) != "ET" && 
		strtoupper($motCleaned) != "DONC" && 
		strtoupper($motCleaned) != "OR" && 
		strtoupper($motCleaned) != "NI" && 
		strtoupper($motCleaned) != "CAR" && 		
		strtoupper($motCleaned) != "QUELLES" && 
		strtoupper($motCleaned) != "QUELLE" &&	
		strtoupper($motCleaned) != "COMMENT" && 
		strtoupper($motCleaned) != "POURQUOI" &&	
		strtoupper($motCleaned) != "SUR" && 	
		strtoupper($motCleaned) != "DANS" &&	
		strtoupper($motCleaned) != "DU" &&	
		strtoupper($motCleaned) != "AU" && 	
		strtoupper($motCleaned) != "AVOIR" && 
		strtoupper($motCleaned) != "ÊTRE" &&
		strtoupper($motCleaned) != "QUE" &&
		strtoupper($motCleaned) != "QU'EST-CE" &&
				   $motCleaned  != "à" &&   
		strtoupper($motCleaned) != "")
		{
			array_push($listeMotsClef, $motCleaned);
		}

	}

	$cptQuestionsPrisesEnCompte = 0;
	$cptMotsPrisEnCompte = 0;
	
	$listeIDs = [];
	
	//echo json_encode($listeMotsClef, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

	shuffle($listeMotsClef);
	
	do{
		$reponseCourante = $db->query("CALL API_MajorityGetQuestionByMotClef('$listeMotsClef[$cptMotsPrisEnCompte]', $noThisId);");
		
		if($reponseCourante){			
			$dataListeQuestionsPretendantes = $reponseCourante->fetch_assoc();
			
			if (!in_array($dataListeQuestionsPretendantes['IDQuestion'], $listeIDs))
			{
				array_push($listeIDs, $dataListeQuestionsPretendantes['IDQuestion']);
				$cptQuestionsPrisentEnCompte++;
				$reponseCourante->close();
				$db->next_result();	
			}
		}
		else
		{
			array_push($listeIDs, 0);
		}
		
		$cptMotsPrisEnCompte++;
		if ($cptMotsPrisEnCompte == 4) break;

	} while($cptMotsPrisEnCompte < count($listeMotsClef));
	
	//echo json_encode($listeIDs);
	
	shuffle ($listeIDs);
	
	$listeOfReponses = [];
	
	$cptAnswer = 0;
	
	foreach ($listeIDs as $idCurrent)
	{

		$reponseCouranteAnswer = $db->query("CALL API_MajorityAnswersOfQuestion(".$idCurrent.");");
		if($reponseCouranteAnswer){
			$dataReponseToExplode = $reponseCouranteAnswer->fetch_assoc();			
			$reponsesToExplode = $dataReponseToExplode['answers'];			
			array_push($listeOfReponses, explode("|",$reponsesToExplode)[0]);
		}
		else
		{
			array_push($listeOfReponses,"#ERROR#");
		}
			$reponseCouranteAnswer->close();
			$db->next_result();
			
	$cptAnswer++;
	}
	
	shuffle($listeOfReponses);
	
	$jsonAChier = array(
    question  => $questionADecoudre,
    answers => array($listeOfReponses[1],$listeOfReponses[0],$listeOfReponses[3]));

	exit (json_encode($jsonAChier, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

	}
}
catch (Exception $ex)
{
	echo 'ERROR : '.$ex->getMessage();
}

	// Close connection
	$db->close();

?>
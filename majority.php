<?php
header('Content-Type: application/json; charset=utf-8');
require_once('config.php');

// Suppression des mots banals
function isProhibitedWord($word)
{
    $PROHIBITED_WORDS = ["QUEL", "EST", "C'EST", "POUR", "PAR","LE", "LES", "LA", "UN", "UNE", "DE", "DES", "D'", "L'", "T'", 
                        "A", "EU" ,"MAIS", "OU" ,"OÙ","ET", "DONC", "OR", "NI", "CAR", "QUELLES", "QUELLE", "COMMENT",
                        "POURQUOI", "SUR", "DANS", "DU", "AU", "AVOIR", "ÊTRE","QUE", "QU'EST-CE", "À",""];

    foreach ($PROHIBITED_WORDS as $prohibitedWord) {
        if (trim(strtoupper($word)) == $prohibitedWord)
            return (true);
    }
    return (false);
}

// Nettoyage et constitution de l'array de mots clés propres
function clearWords($sentence)
{
    $output = [];
    $motsToSearchForReponseAleatoire = explode(" ", $sentence);
    
    foreach ($motsToSearchForReponseAleatoire as $currentMot) {
        $motCleaned = str_replace('"','',$currentMot);
        $motCleaned = str_replace('?','',$motCleaned);
        $motCleaned = str_replace('!','',$motCleaned);
        $motCleaned = str_replace('.','',$motCleaned);
        $motCleaned = str_replace(',','',$motCleaned);
        $motCleaned = str_replace(':','',$motCleaned);

        if (!isProhibitedWord($motCleaned)) {
            array_push($output," ".$motCleaned);
        }
    }
    return ($output);
}

	// Connexion à la BDD
	$db = new mysqli($ADRES, $USER, $MDP, $BASE);
	mysqli_set_charset($db,"utf8");
	
	// Check des erreurs
	if(mysqli_connect_errno()){
	echo mysqli_connect_error();
	}
	
try
{
	
	
// FAIRE TANT QUE PAS 3 REPONSES :
	
	do{
		
		a:
		
		// 1) Tirage d'une question au hasard	
		$reponse = $db->query("CALL API_MajorityQuestionHasard();");
		
		if($reponse){
				 // Cycle through results
				$data = $reponse->fetch_assoc();

				$questionADecoudre = $data["question"];
				
				$noThisId = $data["IDQuestion"];
				
				// Free result set
				$reponse->close();
				$db->next_result();
			
				// 2) récupération des mots de la question nettoyés et dans un array
				$listeMotsClef = clearWords($questionADecoudre);
				
				$cptMotsPrisEnCompte = 0;
				$cptMot = 0;
				$listeIDs = [];
				$isPush = false;
				
				// 3) Mélange de l'ordre des mots clés
				shuffle($listeMotsClef);
				
				$motsClefVirgule = implode("|", $listeMotsClef);
				
				//exit(json_encode($listeMotsClef));
				
				// 4) Recherche de chacun des ID questions qui contient maximum chacun des 8 premiers mots clés (optimisation) - Question qui ne doit pas être celle d'origine : $NOTHISID !
				
					
						$reponseCourante = $db->query("CALL API_MajorityGetQuestionByMotClef('".addslashes($motsClefVirgule)."', $noThisId);");
						if($reponseCourante){			
								while ($dataListeQuestionPretendantes = $reponseCourante->fetch_assoc())
								{
									array_push($listeIDs, $dataListeQuestionPretendantes['IDQuestion']);
								}
								$reponseCourante->close();
								$db->next_result();							
						}

				
				// 5) Mélange des questions (ID's)
				shuffle ($listeIDs);
				
				$listeOfReponses = [];
				
				$cptAnswer = 0;
				
				// 6) Récupération de la réponse de chacune des questions avec limitation aux 3 premières questions du mélange (optimisation)
				foreach ($listeIDs as $idCurrent)
				{

					$reponseCouranteAnswer = $db->query("CALL API_MajorityAnswersOfQuestion(".$idCurrent.");");
					if($reponseCouranteAnswer){
							$dataReponseToExplode = $reponseCouranteAnswer->fetch_assoc();			
							$reponsesToExplode = $dataReponseToExplode['answers'];			
							array_push($listeOfReponses, explode("|",$reponsesToExplode)[0]);
							$reponseCouranteAnswer->close();
						}
						$db->next_result();
						
					$cptAnswer++;
					if ($cptAnswer == 3) break;
				}
				
				// == Pas de nouveau mélange car les ID des questions/réponses ont déjà été mélangés précédemment ==
				
				$jsonFinalAvecQuestionEtReponses = array(
				question  => $questionADecoudre,
				answers => $listeOfReponses);
			
			}
		
	} while (count($listeOfReponses) < 3); // On recommence l'opération si pas assez de réponses (exemple : Quel est la capital de la France --> Capitale (1) / France (2).
	
		exit (json_encode($jsonFinalAvecQuestionEtReponses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

	
}
catch (Exception $ex)
{
	//exit (json_encode('{"ERROR" : "'.$ex->getMessage()'"}', JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

	// On ferme quand même la connexion à la DB, on sait jamais ce qu'il peut arriver sinon...
	$db->close();

?>